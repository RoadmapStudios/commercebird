<?php

namespace RMS\API;

use ExecutecallClass;
use WP_REST_Response;
use WP_REST_Server;
use WP_REST_Controller;

defined( 'RMS_PLUGIN_NAME' ) || exit();

class Zoho extends WP_REST_Controller {

	protected $prefix = 'wc/v3';
	protected $rest_base = 'zoho-inventory';

	public function __construct() {
		register_rest_route(
			$this->prefix,
			'/' . $this->rest_base . '/active/',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_zi_token' ),
				'permission_callback' => array( $this, 'permission_check' ),
			)
		);
		register_rest_route(
			$this->prefix,
			'/' . $this->rest_base . '/invoices/',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_zi_invoices' ),
				'permission_callback' => array( $this, 'permission_check' ),
			)
		);
		register_rest_route(
			$this->prefix,
			'/' . $this->rest_base . '/invoice-detail/',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array( $this, 'get_zi_invoice_detail' ),
				'permission_callback' => array( $this, 'permission_check' ),
			)
		);
		register_rest_route(
			$this->prefix,
			'/' . $this->rest_base . '/purchaseorders/',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_zi_purchase_orders' ),
				'permission_callback' => array( $this, 'permission_check' ),
			)
		);
		register_rest_route(
			$this->prefix,
			'/' . $this->rest_base . '/purchase-detail/',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array( $this, 'get_zi_purchase_detail' ),
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

	public function get_zi_token(): WP_REST_RESPONSE {
		$rest_response = new WP_REST_Response();
		$rest_response->set_data( $this->empty_response );
		$rest_response->set_status( 400 );

		// connection
		$zoho_inventory_oid = get_option( 'zoho_inventory_oid' );
		$zoho_inventory_url = get_option( 'zoho_inventory_url' );
		$get_url = $zoho_inventory_url . 'inventory/v1/organizations/' . $zoho_inventory_oid . '?organization_id=' . $zoho_inventory_oid;

		$execute_curl_call_handle = new ExecutecallClass();
		$json = $execute_curl_call_handle->execute_curl_call_get( $get_url );
		$code = $json->code;
		if ( 0 === (int) $code ) {
			$response['zi_org_name'] = $json->organization->name;
			$response['zi_org_email'] = $json->organization->email;
			$response['zi_org_id'] = $zoho_inventory_oid;
			$response['zi_api_url'] = $zoho_inventory_url;

			$rest_response->set_data( $response );
			$rest_response->set_status( 200 );

		} else {
			$rest_response->set_data( 'connection is not yet setup' );
			$rest_response->set_status( 400 );
		}

		return rest_ensure_response( $rest_response );
	}

	public function get_zi_invoices(): WP_REST_Response {
		$rest_response = new WP_REST_Response();
		$rest_response->set_data( $this->empty_response );
		$rest_response->set_status( 400 );

		// connection
		$zoho_inventory_oid = get_option( 'zoho_inventory_oid' );
		$zoho_inventory_url = get_option( 'zoho_inventory_url' );
		$get_url = $zoho_inventory_url . 'inventory/v1/invoices/' . '?organization_id=' . $zoho_inventory_oid;

		$execute_curl_call_handle = new ExecutecallClass();
		$json = $execute_curl_call_handle->execute_curl_call_get( $get_url );
		$code = $json->code;
		if ( 0 === (int) $code ) {
			$response['invoices'] = $json->invoices;
			$rest_response->set_data( $response );
			$rest_response->set_status( 200 );

		} else {
			$rest_response->set_data( 'connection is not yet setup' );
			$rest_response->set_status( 400 );
		}

		return rest_ensure_response( $rest_response );
	}

	public function get_zi_invoice_detail( $request ): WP_REST_RESPONSE {
		$rest_response = new WP_REST_Response();
		$rest_response->set_data( $this->empty_response );
		$rest_response->set_status( 400 );

		// Get invoice id from the request
		$invoice_id = $request['invoice_id'];

		if ( empty( $invoice_id ) ) {
			$rest_response->set_data( 'invoice_id is required' );
			$rest_response->set_status( 400 );
			return rest_ensure_response( $rest_response );
		}
		// connection
		$zoho_inventory_oid = get_option( 'zoho_inventory_oid' );
		$zoho_inventory_url = get_option( 'zoho_inventory_url' );
		$get_url = $zoho_inventory_url . 'inventory/v1/invoices/' . $invoice_id . '?organization_id=' . $zoho_inventory_oid;

		$execute_curl_call_handle = new ExecutecallClass();
		$json = $execute_curl_call_handle->execute_curl_call_get( $get_url );
		$code = $json->code;
		if ( 0 === (int) $code ) {
			$response['invoice'] = $json->invoice;
			$rest_response->set_data( $response );
			$rest_response->set_status( 200 );

		} else {
			$rest_response->set_data( 'connection is not yet setup' );
			$rest_response->set_status( 400 );
		}

		return rest_ensure_response( $rest_response );
	}

	public function get_zi_purchase_orders(): WP_REST_Response {
		$rest_response = new WP_REST_Response();
		$rest_response->set_data( $this->empty_response );
		$rest_response->set_status( 400 );

		// connection
		$zoho_inventory_oid = get_option( 'zoho_inventory_oid' );
		$zoho_inventory_url = get_option( 'zoho_inventory_url' );
		$get_url = $zoho_inventory_url . 'inventory/v1/purchaseorders/' . '?organization_id=' . $zoho_inventory_oid;

		$execute_curl_call_handle = new ExecutecallClass();
		$json = $execute_curl_call_handle->execute_curl_call_get( $get_url );
		$code = $json->code;
		if ( 0 === (int) $code ) {
			$response['purchaseorders'] = $json->purchaseorders;
			$rest_response->set_data( $response );
			$rest_response->set_status( 200 );

		} else {
			$rest_response->set_data( 'connection is not yet setup' );
			$rest_response->set_status( 400 );
		}

		return rest_ensure_response( $rest_response );
	}

	public function get_zi_purchase_detail( $request ): WP_REST_RESPONSE {
		$rest_response = new WP_REST_Response();
		$rest_response->set_data( $this->empty_response );
		$rest_response->set_status( 400 );

		// Get invoice id from the request
		$purchaseorder_id = $request['purchaseorder_id'];

		if ( empty( $purchaseorder_id ) ) {
			$rest_response->set_data( 'purchaseorder_id is required' );
			$rest_response->set_status( 400 );
			return rest_ensure_response( $rest_response );
		}

		// connection
		$zoho_inventory_oid = get_option( 'zoho_inventory_oid' );
		$zoho_inventory_url = get_option( 'zoho_inventory_url' );
		$get_url = $zoho_inventory_url . 'inventory/v1/purchaseorders/' . $purchaseorder_id . '?organization_id=' . $zoho_inventory_oid;

		$execute_curl_call_handle = new ExecutecallClass();
		$json = $execute_curl_call_handle->execute_curl_call_get( $get_url );
		$code = $json->code;
		if ( 0 === (int) $code ) {
			$response['purchase_order'] = $json->purchase_order;
			$response['url'] = $get_url;
			$rest_response->set_data( $response );
			$rest_response->set_status( 200 );
		} else {
			$rest_response->set_data( 'connection is not yet setup' );
			$rest_response->set_status( 400 );
		}

		return rest_ensure_response( $rest_response );
	}
}
