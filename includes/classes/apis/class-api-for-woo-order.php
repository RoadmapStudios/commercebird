<?php

namespace RMS\API;

use WP_REST_Response;

defined( 'RMS_PLUGIN_NAME' ) || exit();

class CreateOrderWebhook {

	use Api;

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
			'order_status',
			'paid_status',
			'discount',
			'discount_total',
			'notes',
		);

		$order_data = array_intersect_key( $order_data['salesorder'], array_flip( $allowed_keys ) );

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
		// $customer = get_user_by( 'email', $order_data['billing_address']['email'] );
		// create shipping object
		$shipping = new \WC_Order_Item_Shipping();
		$shipping->set_method_title( $order_data['delivery_method'] );
		$order = wc_create_order();
		// if ( $customer ) {
		//  $order->set_customer_id( $customer->ID );
		// }
		foreach ( $order_data['line_items'] as $order_data_item ) {
			$order->add_product( wc_get_product( $line_items[ $order_data_item['item_id'] ] ), $order_data_item['quantity'] );
		}
		$order->set_address( $this->format_address( $order_data['billing_address'] ), 'billing' );
		$order->set_address( $this->format_address( $order_data['shipping_address'] ), 'shipping' );
		$order->set_currency( $order_data['currency_code'] );
		$order->set_status( $this->map_status( $order_data['order_status'] ) );
		$order->set_discount_total( $order_data['discount'] );
		$order->add_item( $shipping );
		$order->calculate_totals();
		$order->set_customer_note( $order_data['notes'] );
		$order->save();
		$order->update_meta_data( 'zi_salesorder_id', $order_data['salesorder_id'] );
		$response->set_data( $order->get_data() );
		return $response;
	}


	private function map_status( $status ): string {
		switch ( $status ) {
			case 'confirmed':
				return 'wc-completed';
			default:
				return 'wc-pending';
		}
	}


	private function get_items( $line_items ): array {
		$meta_ids    = array_column( $line_items, 'item_id' );
		$product_ids = $this->get_products( $meta_ids );
		if ( count( $product_ids ) !== count( $meta_ids ) ) {
			return array( 'not_found' => array_diff( $meta_ids, array_keys( $product_ids ) ) );
		}

		return $product_ids;
	}

	private function get_products( array $meta_ids ): array {
		global $wpdb;
		$placeholders = implode( ',', array_fill( 0, count( $meta_ids ), '%d' ) );

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID AS product_id, pm.meta_value AS zoho_id FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type = 'product' AND pm.meta_key = 'zi_item_id' AND pm.meta_value IN ($placeholders)",
				$meta_ids
			)
		);
		return wp_list_pluck( $results, 'product_id', 'zoho_id' );
	}
}
