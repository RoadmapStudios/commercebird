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
	 * Register routes for purchase orders.
	 * Override the parent method to add a custom route for creating a purchase order.
	 * @since 1.0.0
	 */
	public function register_routes() {
		parent::register_routes();
		register_rest_route(
			$this->namespace,
			$this->rest_base,
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array( $this, 'cmbird_create_purchase_order' ),
				'permission_callback' => array( $this, 'cmbird_rest_api_permissions_check' ),
			)
		);
		// register the route for updating a purchase order
		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}/(?P<id>[\d]+)",
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array( $this, 'cmbird_update_purchase_order' ),
				'permission_callback' => array( $this, 'cmbird_rest_api_permissions_check' ),
			)
		);
		// register the route for deleting a purchase order
		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}/(?P<id>[\d]+)",
			array(
				'methods' => WP_REST_Server::DELETABLE,
				'callback' => array( $this, 'cmbird_delete_purchase_order' ),
				'permission_callback' => array( $this, 'cmbird_rest_api_permissions_check' ),
			)
		);
		// register route to update the warehouse data in settings
		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}/warehouse",
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array( $this, 'cmbird_update_warehouse' ),
				'permission_callback' => array( $this, 'cmbird_rest_api_permissions_check' ),
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
	 * Prepares a single purchase order for creation or update.
	 *
	 * @since 3.1.0
	 *
	 * @param WP_REST_Request $request  Request object.
	 * @param bool            $creating If the request is for creating a new object.
	 * @return WP_Error|WC_Purchase_Order
	 */
	public function prepare_object_for_database( $request, $creating = false ) {
		$id = isset( $request['id'] ) ? absint( $request['id'] ) : 0;
		$purchase = new WC_Purchase_Order( $id );

		// prepare the purchase order data
		$purchase->set_props( $request->get_json_params() );

		/**
		 * Filters an object before it is inserted via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->post_type`,
		 * refers to the object type slug.
		 *
		 * @param WC_Subscription $subscription The subscription object.
		 * @param WP_REST_Request $request      Request object.
		 * @param bool            $creating     If is creating a new object.
		 */
		return apply_filters( "woocommerce_rest_pre_insert_{$this->post_type}_object", $purchase, $request, $creating );
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
		$billing = isset( $params['billing'] ) ? $params['billing'] : array();
		$customer_note = isset( $params['customer_note'] ) ? $params['customer_note'] : '';
		$currency = isset( $params['currency'] ) ? $params['currency'] : '';

		// Create a new WooCommerce order with the type 'shop_purchase'
		$order = new WC_Purchase_Order( 0 );

		if ( is_wp_error( $order ) ) {
			return new WP_REST_Response( array( 'message' => 'Error creating order' ), 400 );
		}

		// Set the vendor (customer) for the order
		$order->set_customer_id( $vendor );

		// Set the customer note for the order
		$order->set_customer_note( $customer_note );

		// set the currency
		$order->set_currency( $currency );

		// Add line items (products) to the order
		foreach ( $items as $item ) {
			$product_id = $item['product_id'];
			$variation_id = $item['variation_id'];
			$quantity = $item['quantity'];
			if ( $variation_id ) {
				$product = wc_get_product( $variation_id );
				$cost_price = get_post_meta( $variation_id, '_cost_price', true );
				if ( $product ) {
					$order->add_product( $product, $quantity, array( 'subtotal' => $cost_price ) );
				}
			} else {
				$product = wc_get_product( $product_id );
				$cost_price = get_post_meta( $product_id, '_cost_price', true );
				if ( $product ) {
					$order->add_product( $product, $quantity, array( 'subtotal' => $cost_price ) );
				}
			}
		}

		// set billing address
		$order->set_address( $billing, 'billing' );

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
				'state' => substr( get_option( 'woocommerce_default_country' ), 3 ),
				'postcode' => get_option( 'woocommerce_store_postcode' ),
				'country' => substr( get_option( 'woocommerce_default_country' ), 0, 2 ),
			);
		} else {
			// Set the shipping address for the order
			$order->set_address( $shipping, 'shipping' );
		}

		// Calculate totals for the order
		$order->calculate_totals();

		// Save the order
		$order_id = $order->save();

		if ( ! is_wp_error( $order_id ) && (int) $order_id > 0 ) {
			return new WP_REST_Response( $order->get_data(), 200 );
		} else {
			$error_message = $order->get_error_message();
			return new WP_REST_Response( array( 'message' => $error_message ), 400 );
		}
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

		// get billing from customer
		$billing = $order->get_billing_address();
		// set the billing
		$order->set_address( $billing, 'billing' );

		// Remove existing line items from the order
		$order->remove_order_items();

		// Add line items (products) to the order
		foreach ( $items as $item ) {
			$product_id = $item['product_id'];
			$variation_id = $item['variation_id'];
			$quantity = $item['quantity'];
			if ( $variation_id ) {
				$product = wc_get_product( $variation_id );
				$cost_price = get_post_meta( $variation_id, '_cost_price', true );
				if ( $product ) {
					$order->add_product( $product, $quantity, array( 'subtotal' => $cost_price ) );
				}
			} else {
				$product = wc_get_product( $product_id );
				$cost_price = get_post_meta( $product_id, '_cost_price', true );
				if ( $product ) {
					$order->add_product( $product, $quantity, array( 'subtotal' => $cost_price ) );
				}
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
		} else {
			$order->set_address( $shipping, 'shipping' );
		}

		// Calculate totals for the order
		$order->calculate_totals();

		// Save the order
		$order_id = $order->save();

		if ( ! is_wp_error( $order_id ) ) {
			// Return the updated order as the API response
			return new WP_REST_Response( $order->get_data(), 200 );
		} else {
			$error_message = $order->get_error_message();
			return new WP_REST_Response( array( 'message' => $error_message ), 400 );
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
