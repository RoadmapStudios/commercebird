<?php

namespace RMS\API;

use RMS\Admin\Traits\LogWriter;
use WP_REST_Response;

defined( 'RMS_PLUGIN_NAME' ) || exit();

class CreateOrderWebhook {

	use Api;
	use LogWriter;

	private static string $endpoint = 'create-woo-order';


	public function __construct() {
		register_rest_route(
			self::$namespace,
			self::$endpoint,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => array( $this, 'permission_check' ),
			)
		);
	}

	/**
	 * @param $address
	 *
	 * @return array
	 */
	public function format_address( $address ): array {
		if ( array_key_exists( 'attention', $address ) ) {
			$names      = explode( ' ', $address['attention'] );
			$first_name = $names[0] ?? '';
			$last_name  = $names[1] ?? '';
		}
		return array(
			'first_name' => $first_name ?? '',
			'last_name'  => $last_name ?? '',
			'address_1'  => $address['address'] ?? '',
			'address_2'  => $address['street2'] ?? '',
			'city'       => $address['city'] ?? '',
			'state'      => $address['state_code'] ?? '',
			'postcode'   => $address['zip'] ?? '',
			'country'    => $address['country_code'] ?? '',
			'phone'      => $address['phone'] ?? '',
		);
	}

	private function get_product_from_order_items_by_id( $product_id ) {
		global $wpdb;

		// Table name for order items
		$order_items_table = $wpdb->prefix . 'woocommerce_order_items';

		// Query to retrieve product from order items by product ID
		$query = $wpdb->prepare(
			"SELECT order_item_id, order_id, order_item_name, order_item_type
        FROM $order_items_table
        WHERE order_item_type = 'line_item'
        AND order_item_name LIKE %s",
			'%' . $wpdb->esc_like( $product_id ) . '%'
		);

		// Execute the query
		$results = $wpdb->get_results( $query );

		// Return the product details
		return $results;
	}

	private function process( array $order_data ): WP_REST_Response {
		$response = new WP_REST_Response();

		if ( empty( $order_data ) || empty( $order_data['salesorder'] ) ) {
			$response->set_data( $this->empty_response );
			$response->set_status( 404 );
			return $response;
		}

		$allowed_keys = array(
			'salesorder_id',
			'salesorder_number',
			'customer_id',
			'billing_address',
			'shipping_address',
			'delivery_method',
			'currency_code',
			'line_items',
			'contact_person_details',
			'shipping_charges',
			'order_status',
			'paid_status',
			'discount',
			'discount_total',
			'notes',
		);

		$order_data       = array_intersect_key( $order_data['salesorder'], array_flip( $allowed_keys ) );
		$billing_address  = $this->format_address( $order_data['billing_address'] );
		$shipping_address = $this->format_address( $order_data['shipping_address'] );
		if ( isset( $order_data['contact_person_details'][0]['email'] ) ) {
			$customer_data = $order_data['contact_person_details'][0];
			$customer_mail = $customer_data['email'];
			$customer      = get_user_by( 'email', $customer_mail );
			if ( empty( $customer ) ) {
				$customer_id = wc_create_new_customer( $customer_mail );
				$customer    = get_user_by( 'id', $customer_id );
			}
		}

		if ( empty( $order_data['line_items'] ) ) {
			$message = sprintf( __( 'Zoho order #%1$s could not be created in your store %2$s because of missing line items.', 'commercebird' ), $order_data['salesorder_number'], get_bloginfo( 'name' ) );
			error_log_api_email( __( 'Zoho Order Sync', 'commercebird' ), $message );
			$response->set_status( 500 );
			$response->set_data( $message );
			return $response;
		}

		$line_items = $this->get_items( $order_data['line_items'] );

		if ( ! empty( $line_items['not_found'] ) ) {
			$message = sprintf( __( 'Zoho order #%1$s could not be created in your store %2$s because of missing items: %3$s', 'commercebird' ), $order_data['salesorder_number'], get_bloginfo( 'name' ), implode( ', ', $line_items['not_found'] ) );
			error_log_api_email( __( 'Zoho Order Sync', 'commercebird' ), $message );
			$response->set_status( 500 );
			$response->set_data( $message );
			return $response;
		}
		// create shipping object
		$shipping = new \WC_Order_Item_Shipping();
		$shipping->set_method_title( $order_data['delivery_method'] );
		$shipping->set_total( $order_data['shipping_charges']['item_total'] );
		$id             = $this->get_order_id( $order_data['salesorder_id'] );
		$existing_order = wc_get_order( $id );
		// $existing_order->delete();
		if ( $existing_order ) {
			$existing_order->set_address( $shipping_address, 'shipping' );

			// Get existing order items
			$existing_items = $existing_order->get_items();
			$order_items    = wp_list_pluck( $order_data['line_items'], 'quantity', 'sku' );

			// Create an array to track existing SKUs
			$existing_skus = array();

			foreach ( $existing_items as $item_id => $item ) {
				$product_id = $item->get_product_id();
				$product    = wc_get_product( $product_id );
				$sku        = $product->get_sku();
				$quantity   = $order_items['quantity'] ?? 0;

				// Update existing item quantity.
				if ( in_array( $sku, $order_items['sku'], true ) ) {
					$item->set_quantity( $quantity );
					$item->set_total( $product->get_price() * $quantity );
					$existing_skus[] = $sku;
				} else {
					// Remove item if quantity is zero
					$existing_order->remove_item( $item_id );
				}
			}
			// Add new items to the order
			// foreach ( $order_data['line_items'] as $order_data_item ) {
			// 	$sku = $order_data_item['sku'];

			// 	if ( ! in_array( $sku, $existing_skus, true ) ) {
			// 		$product = wc_get_product( $line_items[ $sku ] );

			// 		if ( $product ) {
			// 			$quantity = $order_data_item['quantity'];
			// 			$existing_order->add_product( $product, $quantity );
			// 		}
			// 	}
			// }
			// Save the changes to the order
			$existing_order->set_customer_note( isset( $order_data['notes'] ) ? $order_data['notes'] : '' );
			$existing_order->set_status( $this->map_status( $order_data['paid_status'] ) );
			$existing_order->calculate_totals();
			$existing_order->save();
			$response->set_data(
				$existing_order->get_data()
			);
		} else {
			$order = wc_create_order();
			if ( $customer ) {
				$order->set_customer_id( $customer->ID );
			}
			foreach ( $order_data['line_items'] as $order_data_item ) {
				$order->add_product( wc_get_product( $line_items[ $order_data_item['sku'] ] ), $order_data_item['quantity'] );
			}
			$order->set_address( $billing_address, 'billing' );
			$order->set_address( $shipping_address, 'shipping' );
			$order->set_currency( $order_data['currency_code'] );
			$order->set_status( $this->map_status( $order_data['paid_status'] ) );
			$order->set_discount_total( $order_data['discount'] );
			$order->add_item( $shipping );
			$order->calculate_totals();
			$order->set_shipping_tax( $order_data['shipping_charges']['tax_total_fcy'] ?? 0 );
			$order->set_customer_note( $order_data['notes'] );
			$order->save();
			$order->update_meta_data( 'zi_salesorder_id', $order_data['salesorder_id'] );
			$order->save();
			$response->set_data( $order->get_data() );
		}

		return $response;
	}


	private function map_status( $status ): string {
		switch ( $status ) {
			case 'paid':
				return 'wc-completed';
			default:
				return 'wc-pending';
		}
	}


	private function get_items( $line_items ): array {
		$fd = fopen( __DIR__ . '/get_items.txt', 'w+' );

		$meta_ids    = array_column( $line_items, 'sku' );
		$product_ids = array();
		foreach ( $line_items as $item ) {
			$product_id = wc_get_product_id_by_sku( $item['sku'] );
			if ( $product_id ) {
				$product_ids[] = $product_id;
			}
		}
		fwrite( $fd, PHP_EOL . print_r( $product_ids, true ) );

		fclose( $fd );
		if ( count( $product_ids ) !== count( $meta_ids ) ) {
			return array( 'not_found' => array_diff( $meta_ids, array_keys( $product_ids ) ) );
		}

		return $product_ids;
	}

	// private function get_products_by_id( array $meta_ids ): array {
	//  global $wpdb;
	//  $placeholders = implode( ',', array_fill( 0, count( $meta_ids ), '%d' ) );

	//  $results = $wpdb->get_results(
	//      $wpdb->prepare(
	//          "SELECT p.ID AS product_id, pm.meta_value AS sku FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type in ('product', 'product_variation') AND pm.meta_key = '_sku' AND pm.meta_value IN ($placeholders)",
	//          $meta_ids
	//      )
	//  );
	//  return wp_list_pluck( $results, 'product_id', 'sku' );
	// }

	private function get_order_id( $zi_id ): int {
		// Define your meta query arguments
		$args = array(
			'meta_query' => array(
				array(
					'key'     => 'zi_salesorder_id',
					'value'   => $zi_id,
					'compare' => '=',
				),
			),
		);

		// Get orders based on meta query
		$orders = wc_get_orders( $args );
		if ( count( $orders ) > 0 ) {
			return $orders[0]->get_id();
		}
		return 0;
	}
}
