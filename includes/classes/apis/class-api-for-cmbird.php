<?php

namespace CommerceBird\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_REST_Response;
use WP_REST_Server;
use WP_REST_Controller;

class CMBird_APIs extends WP_REST_Controller {

	protected $prefix = 'wc/v3';
	protected $rest_base = 'cmbird';

	public function __construct() {
		register_rest_route(
			$this->prefix,
			'/' . $this->rest_base . '/products-skus/',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_all_product_with_skus' ),
				'permission_callback' => array( $this, 'permission_check' ),
			)
		);
	}

	/**
	 * Check if a given request has access to get items.
	 */
	public function permission_check() {
		return current_user_can( 'manage_woocommerce' );
	}

	public function get_all_product_with_skus(): WP_REST_RESPONSE {
		$rest_response = new WP_REST_Response();
		$rest_response->set_data( $this->empty_response );
		$rest_response->set_status( 200 );

		global $wpdb;

		// Query to fetch product IDs and SKUs directly from the database
		$results = $wpdb->get_results( "
        SELECT p.ID as product_id, pm.meta_value as sku, p.post_type as product_type
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_sku'
        WHERE p.post_type IN ('product', 'product_variation') AND p.post_status = 'publish'
    " );

		$rest_response->set_data( $results );

		return rest_ensure_response( $rest_response );
	}
}
