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
		// register_rest_route(
		// 	$this->prefix,
		// 	'/' . $this->rest_base . '/invoice-detail/',
		// 	array(
		// 		'methods' => WP_REST_Server::CREATABLE,
		// 		'callback' => array( $this, 'get_zi_invoice_detail' ),
		// 		'permission_callback' => array( $this, 'permission_check' ),
		// 	)
		// );
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
	 *
	 * @return WP_Error|bool
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
        SELECT p.ID as product_id, pm.meta_value as sku
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_sku'
        WHERE p.post_type = 'product' AND p.post_status = 'publish'
    " );

		$rest_response->set_data( $results );

		return rest_ensure_response( $rest_response );

		// connection
		// $zoho_inventory_oid = get_option( 'cmbird_zoho_inventory_oid' );
		// $zoho_inventory_url = get_option( 'cmbird_zoho_inventory_url' );
		// $get_url = $zoho_inventory_url . 'inventory/v1/organizations/' . $zoho_inventory_oid . '?organization_id=' . $zoho_inventory_oid;

		// $execute_curl_call_handle = new CMBIRD_API_Handler_Zoho();
		// $json = $execute_curl_call_handle->execute_curl_call_get( $get_url );
		// $code = $json->code;
		// if ( 0 === (int) $code ) {
		// 	$response['zi_org_name'] = $json->organization->name;
		// 	$response['zi_org_email'] = $json->organization->email;
		// 	$response['zi_org_id'] = $zoho_inventory_oid;
		// 	$response['zi_api_url'] = $zoho_inventory_url;

		// 	$rest_response->set_data( $response );
		// 	$rest_response->set_status( 200 );

		// } else {
		// 	$rest_response->set_data( 'connection is not yet setup' );
		// 	$rest_response->set_status( 400 );
		// }

		// return rest_ensure_response( $rest_response );
	}

	public function handle_get_api_request( $get_url, $data_key, $response_key ): WP_REST_RESPONSE {
		$rest_response = new WP_REST_Response();
		$rest_response->set_data( $this->empty_response );
		$rest_response->set_status( 400 );
		$execute_curl_call_handle = new CMBIRD_API_Handler_Zoho();
		$json = $execute_curl_call_handle->execute_curl_call_get( $get_url );
		$code = $json->code;
		if ( 0 === (int) $code ) {
			$response[ $response_key ] = $json->$data_key;
			$response['url'] = $get_url;
			$rest_response->set_data( $response );
			$rest_response->set_status( 200 );
		} else {
			$rest_response->set_data( $json );
			$rest_response->set_status( 400 );
		}
		return rest_ensure_response( $rest_response );
	}

	public function get_zi_invoices(): WP_REST_Response {
		$zoho_inventory_oid = get_option( 'cmbird_zoho_inventory_oid' );
		$zoho_inventory_url = get_option( 'cmbird_zoho_inventory_url' );
		$get_url = $zoho_inventory_url . "inventory/v1/invoices/?organization_id=$zoho_inventory_oid";
		return $this->handle_get_api_request( $get_url, 'invoices', 'invoices' );
	}
}
