<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WC_REST_Orders_Controller;

/**
 * Class to extend WC REST API for custom order type 'shop_purchase'
 */
class WC_REST_Shop_Purchase_Controller extends WC_REST_Orders_Controller {

	protected $post_type = 'shop_purchase';
	protected $namespace = 'wc/v3';
	protected $rest_base = 'purchases';

	/**
	 * Constructor to set custom order type.
	 */
	public function __construct() {
		// Set custom post type to shop_purchase.
		$this->post_type = 'shop_purchase';
		$this->namespace = 'wc/v3';
		$this->rest_base = 'purchases';
	}

    /**
     * Register routes for orders.
	 * Override the parent method to add a custom route for creating a purchase order.
	 * @since 1.0.0
     */
	public function register_routes() {
		parent::register_routes();
		register_rest_route(
			'wc/v3',
			'/purchases',
			array(
				'methods' => 'POST',
				'callback' => 'cmbird_create_purchase_order',
				'permission_callback' => 'cmbird_rest_api_permissions_check',
			)
		);
		// register the route for updating a purchase order
		register_rest_route(
			'wc/v3',
			'/purchases/(?P<id>[\d]+)',
			array(
				'methods' => 'PUT',
				'callback' => 'cmbird_update_purchase_order',
				'permission_callback' => 'cmbird_rest_api_permissions_check',
			)
		);
		// register the route for deleting a purchase order
		register_rest_route(
			'wc/v3',
			'/purchases/(?P<id>[\d]+)',
			array(
				'methods' => 'DELETE',
				'callback' => 'cmbird_delete_purchase_order',
				'permission_callback' => 'cmbird_rest_api_permissions_check',
			)
		);
		// register route to update the warehouse data in settings
		register_rest_route(
			'wc/v3',
			'/purchases/warehouse',
			array(
				'methods' => 'POST',
				'callback' => 'cmbird_update_warehouse_data',
				'permission_callback' => 'cmbird_rest_api_permissions_check',
			)
		);
	}

	/**
	 * Check if a given request has access to create a purchase order.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|bool
	 * @since 1.0.0
	 */
	public function cmbird_rest_api_permissions_check( $request ) {
		// Check if the user has permission to create a purchase order.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to create purchase orders.', 'commercebird' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

	/**
	 * Create a purchase order.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 * @since 1.0.0
	 */
	public function cmbird_create_purchase_order( $request ) {
		// Get request parameters (sent via the API)
		$params = $request->get_json_params();

		// Extract order data from the request
		$vendor = isset( $params['customer_id'] ) ? $params['customer_id'] : '';
		$items = isset( $params['line_items'] ) ? $params['line_items'] : array();
		$shipping = isset( $params['shipping'] ) ? $params['shipping'] : array();
		$customer_note = isset( $params['customer_note'] ) ? $params['customer_note'] : '';

		// Create a new WooCommerce order with the type 'shop_purchase'
		$order = wc_create_order( array( 'type' => 'shop_purchase' ) );

		if ( is_wp_error( $order ) ) {
			return new WP_REST_Response( array( 'message' => 'Error creating order' ), 400 );
		}

		// Set the vendor (customer) for the order
		$order->set_customer_id( $vendor );

		// Set the customer note for the order
		$order->set_customer_note( $customer_note );

		// Add line items (products) to the order
		foreach ( $items as $item ) {
			$product_id = $item['product_id'];
			$quantity = $item['quantity'];
			// for subtotal use the cost_price meta field of the product
			$cost_price = get_post_meta( $product_id, '_cost_price', true );
			$product = wc_get_product( $product_id );
			if ( $product ) {
				$order->add_product( $product, $quantity, array( 'subtotal' => $cost_price ) );
			}
		}

		// Set the store's address as the shipping address (or use the passed shipping address)
		if ( empty( $shipping ) ) {
			// Default to store address
			$shipping = array(
				'first_name' => 'Store',
				'last_name' => 'Address',
				'company' => get_option( 'blogname' ),
				'address_1' => get_option( 'woocommerce_store_address' ),
				'address_2' => get_option( 'woocommerce_store_address_2' ),
				'city' => get_option( 'woocommerce_store_city' ),
				'state' => get_option( 'woocommerce_default_country' ),
				'postcode' => get_option( 'woocommerce_store_postcode' ),
				'country' => get_option( 'woocommerce_default_country' ),
			);
		}

		// Set the shipping address for the order
		$order->set_address( $shipping, 'shipping' );

		// Calculate totals for the order
		$order->calculate_totals();

		// Save the order
		$order_id = $order->save();

		// Return the created order as the API response
		return new WP_REST_Response( wc_get_order( $order_id ), 200 );
	}

	/**
	 * Update a purchase order.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 * @since 1.0.0
	 */
	public function cmbird_update_purchase_order( $request ) {
		// Get request parameters (sent via the API)
		$params = $request->get_json_params();

		// Extract order data from the request
		$order_id = $request['id'];
		$vendor = isset( $params['customer_id'] ) ? $params['customer_id'] : '';
		$items = isset( $params['line_items'] ) ? $params['line_items'] : array();
		$shipping = isset( $params['shipping'] ) ? $params['shipping'] : array();
		$customer_note = isset( $params['customer_note'] ) ? $params['customer_note'] : '';

		// Get the order object
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return new WP_REST_Response( array( 'message' => 'Order not found' ), 404 );
		}

		// Set the vendor (customer) for the order
		$order->set_customer_id( $vendor );

		// Set the customer note for the order
		$order->set_customer_note( $customer_note );

		// Remove existing line items from the order
		$order->remove_order_items();

		// Add line items (products) to the order
		foreach ( $items as $item ) {
			$product_id = $item['product_id'];
			$quantity = $item['quantity'];
			// for subtotal use the cost_price meta field of the product
			$cost_price = get_post_meta( $product_id, '_cost_price', true );
			$product = wc_get_product( $product_id );
			if ( $product ) {
				$order->add_product( $product, $quantity, array( 'subtotal' => $cost_price ) );
			}
		}

		// Set the store's address as the shipping address (or use the passed shipping address)
		if ( empty( $shipping ) ) {
			// Default to store address
			$shipping = array(
				'first_name' => 'Store',
				'last_name' => 'Address',
				'company' => get_option( 'blogname' ),
				'address_1' => get_option( 'woocommerce_store_address' ),
				'address_2' => get_option( 'woocommerce_store_address_2' ),
				'city' => get_option( 'woocommerce_store_city' ),
				'state' => get_option( 'woocommerce_default_country' ),
				'postcode' => get_option( 'woocommerce_store_postcode' ),
				'country' => get_option( 'woocommerce_default_country' ),
			);

			// Set the shipping address for the order
			$order->set_address( $shipping, 'shipping' );

			// Calculate totals for the order
			$order->calculate_totals();

			// Save the order
			$order_id = $order->save();

			// Return the updated order as the API response
			return new WP_REST_Response( wc_get_order( $order_id ), 200 );

		} else {
			return new WP_REST_Response( array( 'message' => 'Error updating order' ), 400 );
		}
	}

	/**
	 * Delete a purchase order.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 * @since 1.0.0
	 */
	public function cmbird_delete_purchase_order( $request ) {
		// Get request parameters (sent via the API)
		$order_id = $request['id'];

		// Get the order object
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return new WP_REST_Response( array( 'message' => 'Order not found' ), 404 );
		}

		// Delete the order if status is draft or pending
		if ( in_array( $order->get_status(), array( 'draft', 'pending' ) ) ) {
			$order->delete();
		} else {
			return new WP_REST_Response( array( 'message' => 'Order cannot be deleted' ), 400 );
		}

		// Return the deleted order as the API response
		return new WP_REST_Response( array( 'message' => 'Order deleted' ), 200 );
	}

	/**
	 * Update the warehouse data in woocommerce settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 * @since 1.0.0
	 */
	public function cmbird_update_warehouse_data( $request ) {
		// Get request parameters (sent via the API)
		$params = $request->get_json_params();

		// return error if no warehouse data is provided
		if ( empty( $params ) ) {
			return new WP_REST_Response( array( 'message' => 'No warehouse data provided' ), 400 );
		}

		// Save the array of warehouse data to the settings
		update_option( 'cmbird_warehouse_data', $params, false );

		// Return the updated warehouse data as the API response
		return new WP_REST_Response( array( 'message' => 'Warehouse data updated' ), 200 );
	}
}
