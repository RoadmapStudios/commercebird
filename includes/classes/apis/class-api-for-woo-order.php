<?php

namespace CommerceBird\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CommerceBird\Admin\Traits\LogWriter;
use WP_REST_Response;

class CreateOrderWebhook {

	use Api;
	use LogWriter;

	private static string $endpoint = 'create-woo-order';


	public function __construct() {
		register_rest_route(
			self::$namespace,
			self::$endpoint,
			array(
				'methods' => 'POST',
				'callback' => array( $this, 'handle' ),
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
			$names = explode( ' ', $address['attention'] );
			$first_name = $names[0] ?? '';
			$last_name = $names[1] ?? '';
		}
		return array(
			'first_name' => $first_name ?? '',
			'last_name' => $last_name ?? '',
			'address_1' => $address['address'] ?? '',
			'address_2' => $address['street2'] ?? '',
			'city' => $address['city'] ?? '',
			'state' => $address['state_code'] ?? '',
			'postcode' => $address['zip'] ?? '',
			'country' => $address['country_code'] ?? '',
			'phone' => $address['phone'] ?? '',
		);
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

		$order_data = array_intersect_key( $order_data['salesorder'], array_flip( $allowed_keys ) );
		$billing_address = $this->format_address( $order_data['billing_address'] );
		$shipping_address = $this->format_address( $order_data['shipping_address'] );
		if ( isset( $order_data['contact_person_details'][0]['email'] ) ) {
			$customer_data = $order_data['contact_person_details'][0];
			$customer_mail = $customer_data['email'];
			$customer = get_user_by( 'email', $customer_mail );
			if ( empty( $customer ) ) {
				$customer_id = wc_create_new_customer( $customer_mail );
				$customer = get_user_by( 'id', $customer_id );
			}
		}

		if ( empty( $order_data['line_items'] ) ) {
			// translators: 1: order number, 2: Store name
			$message = sprintf( __( 'Zoho order #%1$s could not be created in your store %2$s because of missing line items.', 'commercebird' ), $order_data['salesorder_number'], get_bloginfo( 'name' ) );
			cmbird_error_log_api_email( __( 'Zoho Order Sync', 'commercebird' ), $message );
			$response->set_status( 500 );
			$response->set_data( $message );
			return $response;
		}

		$line_items = $this->get_items( $order_data['line_items'] );

		if ( ! empty( $line_items['not_found'] ) ) {
			// translators: 1: order number, 2: Store name, 3: missing items
			$message = sprintf( __( 'Zoho order #%1$s could not be created in your store %2$s because of missing items: %3$s', 'commercebird' ), $order_data['salesorder_number'], get_bloginfo( 'name' ), implode( ', ', $line_items['not_found'] ) );
			cmbird_error_log_api_email( __( 'Zoho Order Sync', 'commercebird' ), $message );
			$response->set_status( 500 );
			$response->set_data( $message );
			return $response;
		}
		// create shipping object
		$shipping = new \WC_Order_Item_Shipping();
		$shipping->set_method_title( $order_data['delivery_method'] );
		$shipping->set_total( $order_data['shipping_charges']['item_total'] );
		$id = $this->get_order_id( $order_data['salesorder_id'] );
		$existing_order = wc_get_order( $id );
		if ( ! empty( $existing_order ) ) {
			$existing_order->set_address( $shipping_address, 'shipping' );

			// Get existing order items
			$order_items = array_column( $order_data['line_items'], 'quantity', 'sku' );
			$existing_order->remove_order_items( 'line_item' );
			foreach ( $order_items as $sku => $quantity ) {
				$product_id = wc_get_product_id_by_sku( $sku );
				if ( empty( $product_id ) ) {
					continue;
				}
				$product = wc_get_product( $product_id );
				if ( empty( $product_id ) ) {
					continue;
				}
				$existing_order->add_product( $product, $quantity );
			}
			// Save the changes to the order
			$existing_order->set_customer_note( isset( $order_data['notes'] ) ? $order_data['notes'] : '' );
			$existing_order->update_status( $this->map_status( $order_data['paid_status'] ) );
			$existing_order->calculate_totals();
			$existing_order->save();
			wc_delete_shop_order_transients( $existing_order );
			$response->set_data(
				array(
					'id' => $existing_order->get_id(),
					'items' => array_map(
						function ($item) {
							return array(
								'product_id' => $item->get_product_id(),
								'quantity' => $item->get_quantity(),
								'price' => $item->get_total(),
							);
						},
						$existing_order->get_items()
					),
					'total' => $existing_order->get_total(),
				)
			);
		} else {
			$order = wc_create_order();
			if ( $customer ) {
				$order->set_customer_id( $customer->ID );
			}
			foreach ( $order_data['line_items'] as $order_data_item ) {
				$product_id = wc_get_product_id_by_sku( $order_data_item['sku'] );
				if ( empty( $product_id ) ) {
					continue;
				}
				$product = wc_get_product( $product_id );
				if ( empty( $product_id ) ) {
					continue;
				}
				$order->add_product( $product, $order_data_item['quantity'] );
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
			$response->set_data(
				array(
					'id' => $order->get_id(),
					'items' => array_map(
						function ($item) {
							return array(
								'product_id' => $item->get_product_id(),
								'quantity' => $item->get_quantity(),
								'price' => $item->get_total(),
							);
						},
						$order->get_items()
					),
					'total' => $order->get_total(),
				)
			);
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
		$meta_ids = array_column( $line_items, 'sku' );
		$product_ids = array();
		foreach ( $line_items as $item ) {
			$product_id = wc_get_product_id_by_sku( $item['sku'] );
			if ( $product_id ) {
				$product_ids[] = $product_id;
			}
		}
		if ( count( $product_ids ) !== count( $meta_ids ) ) {
			return array( 'not_found' => array_diff( $meta_ids, array_keys( $product_ids ) ) );
		}

		return $product_ids;
	}

	private function get_order_id( $zi_id ): int {
		// Define your meta query arguments
		$args = array(
			'meta_query' => array(
				array(
					'key' => 'zi_salesorder_id',
					'value' => $zi_id,
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
