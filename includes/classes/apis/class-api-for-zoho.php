<?php

namespace RMS\API;

use ExecutecallClass;
use WP_REST_Response;
use WP_REST_Server;

defined( 'RMS_PLUGIN_NAME' ) || exit();

class Zoho {
	use Api;


	public function __construct() {
		register_rest_route(
			$this->namespace,
			'/zoho-active/',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array($this, 'get_zi_token'),
				'permission_callback' => array($this, 'check_origin'),
			)
		);
		register_rest_route(
			$this->namespace,
			'/zoho-invoices/',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_zi_invoices' ),
				'permission_callback' => array( $this, 'check_origin' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/zoho-invoice-detail/',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_zi_invoice_detail' ),
				'permission_callback' => array( $this, 'check_origin' ),
			)
		);
	}

	public function get_zi_token(): WP_REST_RESPONSE {
		$rest_response = new WP_REST_Response();
		$rest_response->set_data($this->empty_response);
		$rest_response->set_status(400);

		// connection
		$zoho_inventory_oid = get_option('zoho_inventory_oid');
		$zoho_inventory_url = get_option('zoho_inventory_url');
		$get_url = $zoho_inventory_url . 'api/v1/organizations/' . $zoho_inventory_oid . '?organization_id=' . $zoho_inventory_oid;

		$executeCurlCallHandle = new ExecutecallClass();
		$json = $executeCurlCallHandle->ExecuteCurlCallGet($get_url);
		$code = $json->code;
		if (0 === (int) $code) {
			$response['zi_org_name'] = $json->organization->name;
			$response['zi_org_email'] = $json->organization->email;
			$response['zi_org_id'] = $zoho_inventory_oid;
			$response['zi_api_url'] = $zoho_inventory_url;

			$rest_response->set_data($response);
			$rest_response->set_status(200);

		} else {
			$rest_response->set_data('connection is not yet setup');
			$rest_response->set_status(400);
		}

		return rest_ensure_response($rest_response);
	}

	public function get_zi_invoices(): WP_REST_Response {
		$rest_response = new WP_REST_Response();
		$rest_response->set_data( $this->empty_response );
		$rest_response->set_status( 400 );

		// connection
		$zoho_inventory_oid = get_option( 'zoho_inventory_oid' );
		$zoho_inventory_url = get_option( 'zoho_inventory_url' );
		$get_url            = $zoho_inventory_url . 'api/v1/invoices/' . '?organization_id=' . $zoho_inventory_oid;

		$executeCurlCallHandle = new ExecutecallClass();
		$json                  = $executeCurlCallHandle->ExecuteCurlCallGet( $get_url );
		$code                  = $json->code;
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

	public function get_zi_invoice_detail($request): WP_REST_RESPONSE {
		$rest_response = new WP_REST_Response();
		$rest_response->set_data( $this->empty_response );
		$rest_response->set_status( 400 );

		// Get invoice id from the request
		$invoice_id = $request['invoice_id'];
		
		// connection
		$zoho_inventory_oid = get_option( 'zoho_inventory_oid' );
		$zoho_inventory_url = get_option( 'zoho_inventory_url' );
		$get_url            = $zoho_inventory_url . 'api/v1/invoices/' . $invoice_id . '?organization_id=' . $zoho_inventory_oid;

		$executeCurlCallHandle = new ExecutecallClass();
		$json                  = $executeCurlCallHandle->ExecuteCurlCallGet( $get_url );
		$code                  = $json->code;
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
}
