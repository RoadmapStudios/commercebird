<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WC_REST_Orders_Controller;

/**
 * Class to extend WC REST API for custom order type 'shop_purchase'
 */
class WC_REST_Shop_Purchase_Controller extends WC_REST_Orders_Controller {

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
     */
	public function register_routes() {
		parent::register_routes();
	}

}

/**
 * Add custom meta fields to the REST API response for shop_purchase orders.
 *
 * @param WP_REST_Response $response The response object.
 * @param WC_Order $object The order object.
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response Modified response object.
 */
function cmbird_custom_meta_to_shop_purchase_rest_api( $response, $object, $request ) {
	if ( 'shop_purchase' === $object->get_type() ) {
		// Example: Add custom meta field '_purchase_details' to the response.
		$response->data['purchase_details'] = get_post_meta( $object->get_id(), '_purchase_details', true );
	}

	return $response;
}

add_filter( 'woocommerce_rest_prepare_shop_purchase_object', 'cmbird_custom_meta_to_shop_purchase_rest_api', 10, 3 );