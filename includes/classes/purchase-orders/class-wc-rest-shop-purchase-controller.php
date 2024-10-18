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
		$schema = parent::get_item_schema();
		$data_keys = array_keys( array_filter( $schema['properties'], array( $this, 'filter_writable_props' ) ) );

		// Handle all writable props.
		foreach ( $data_keys as $key ) {
			$value = $request[ $key ];

			if ( ! is_null( $value ) ) {
				switch ( $key ) {
					case 'coupon_lines':
					case 'status':
						// Change should be done later so transitions have new data.
						break;
					case 'billing':
					case 'shipping':
						parent::update_address( $purchase, $value, $key );
						break;
					case 'line_items':
					case 'shipping_lines':
					case 'fee_lines':
						if ( is_array( $value ) ) {
							foreach ( $value as $item ) {
								if ( is_array( $item ) ) {
									if ( parent::item_is_null( $item ) || ( isset( $item['quantity'] ) && 0 === $item['quantity'] ) ) {
										$purchase->remove_item( $item['id'] );
									} else {
										parent::set_item( $purchase, $key, $item );
									}
								}
							}
						}
						break;
					case 'meta_data':
						if ( is_array( $value ) ) {
							foreach ( $value as $meta ) {
								$purchase->update_meta_data( $meta['key'], $meta['value'], isset( $meta['id'] ) ? $meta['id'] : '' );
							}
						}
						break;
					default:
						if ( is_callable( array( $purchase, "set_{$key}" ) ) ) {
							$purchase->{"set_{$key}"}( $value );
						}
						break;
				}
			}
		}
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
