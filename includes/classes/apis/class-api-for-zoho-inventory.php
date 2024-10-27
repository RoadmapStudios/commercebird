<?php

namespace CommerceBird\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CMBIRD_API_Handler_Zoho;
use WP_REST_Response;
use WP_REST_Server;
use WP_REST_Controller;

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
			'/' . $this->rest_base . '/vendors/',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_zi_vendors' ),
				'permission_callback' => array( $this, 'permission_check' ),
			)
		);

		register_rest_route(
			$this->prefix,
			'/' . $this->rest_base . '/vendor-details/',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_zi_vendor_details' ),
				'permission_callback' => array( $this, 'permission_check' ),
				'vendor_id' => array(
					'required' => true,  // Set id as required
				),
			)
		);

		register_rest_route(
			$this->prefix,
			'/' . $this->rest_base . '/create-vendor/',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array( $this, 'create_zi_vendor' ),
				'permission_callback' => array( $this, 'permission_check' ),
			)
		);

		register_rest_route(
			$this->prefix,
			'/' . $this->rest_base . '/delete-vendor/(?P<id>\d+)', // Add vendor ID as a URL parameter
			array(
				'methods' => WP_REST_Server::DELETABLE,
				'callback' => array( $this, 'delete_zi_vendor' ), // Update to the delete vendor function
				'permission_callback' => array( $this, 'permission_check' ),
			)
		);


		register_rest_route(
			$this->prefix,
			'/' . $this->rest_base . '/update-vendor/',
			array(
				'methods' => WP_REST_Server::CREATABLE, // or use 'PUT' or 'PATCH' if needed
				'callback' => array( $this, 'update_zi_vendor_details' ),  // Update to correct function
				'permission_callback' => array( $this, 'permission_check' ),
				'args' => array(
					'vendor_id' => array(
						'required' => true,  // Vendor ID is required for the update
						'description' => 'ID of the vendor to update',
						'type' => 'string', // assuming the vendor_id is a string
					),
				),
			)
		);


		register_rest_route(
			$this->prefix,
			'/' . $this->rest_base . '/warehouses/',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_zi_warehouses' ),
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
		register_rest_route(
			$this->prefix,
			'/' . $this->rest_base . '/create-po/',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array( $this, 'create_zi_purchase_order' ),
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
		$zoho_inventory_oid = get_option( 'cmbird_zoho_inventory_oid' );
		$zoho_inventory_url = get_option( 'cmbird_zoho_inventory_url' );
		$get_url = $zoho_inventory_url . 'inventory/v1/organizations/' . $zoho_inventory_oid . '?organization_id=' . $zoho_inventory_oid;

		$execute_curl_call_handle = new CMBIRD_API_Handler_Zoho();
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
		$zoho_inventory_oid = get_option( 'cmbird_zoho_inventory_oid' );
		$zoho_inventory_url = get_option( 'cmbird_zoho_inventory_url' );
		$get_url = $zoho_inventory_url . "inventory/v1/invoices/$invoice_id?organization_id=$zoho_inventory_oid";
		return $this->handle_get_api_request( $get_url, 'invoice', 'invoice' );
	}

	public function get_zi_purchase_orders(): WP_REST_Response {
		$zoho_inventory_oid = get_option( 'cmbird_zoho_inventory_oid' );
		$zoho_inventory_url = get_option( 'cmbird_zoho_inventory_url' );
		$get_url = $zoho_inventory_url . "inventory/v1/purchaseorders/?organization_id=$zoho_inventory_oid";
		return $this->handle_get_api_request( $get_url, 'purchaseorders', 'purchaseorders' );
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
		$zoho_inventory_oid = get_option( 'cmbird_zoho_inventory_oid' );
		$zoho_inventory_url = get_option( 'cmbird_zoho_inventory_url' );
		$get_url = $zoho_inventory_url . "inventory/v1/purchaseorders/$purchaseorder_id?organization_id=$zoho_inventory_oid";
		return $this->handle_get_api_request( $get_url, 'purchaseorder', 'purchase_order' );
	}

	public function get_zi_vendors( $request ): WP_REST_RESPONSE {
		$zoho_inventory_oid = get_option( 'cmbird_zoho_inventory_oid' );
		$zoho_inventory_url = get_option( 'cmbird_zoho_inventory_url' );
		$get_url = $zoho_inventory_url . "inventory/v1/vendors?organization_id=$zoho_inventory_oid";
		return $this->handle_get_api_request( $get_url, 'contacts', 'users' );
	}

	public function get_zi_vendor_details( $request ): WP_REST_RESPONSE {
		$zoho_inventory_oid = get_option( 'cmbird_zoho_inventory_oid' );
		$zoho_inventory_url = get_option( 'cmbird_zoho_inventory_url' );

		$rest_response = new WP_REST_Response();
		$rest_response->set_data( $this->empty_response );
		$rest_response->set_status( 400 );
		// Get Vendor Details from the request
		$vendor_id = $request->get_param( 'vendor_id' );

		if ( empty( $vendor_id ) ) {
			$rest_response->set_data( 'Vendor ID is required' );
			$rest_response->set_status( 400 );
			return rest_ensure_response( $rest_response );
		}
		$get_url = $zoho_inventory_url . "inventory/v1/contacts/$vendor_id?organization_id=$zoho_inventory_oid";
		return $this->handle_get_api_request( $get_url, 'contact', 'vendor' );
	}
	public function delete_zi_vendor( $request ): WP_REST_Response {
		$zoho_inventory_oid = get_option( 'cmbird_zoho_inventory_oid' );
		$zoho_inventory_url = get_option( 'cmbird_zoho_inventory_url' );

		$rest_response = new WP_REST_Response();
		$rest_response->set_data( $this->empty_response );
		$rest_response->set_status( 400 );
		$id = $request['id'];
		// Get Vendor ID from the request
		$vendor_id = $request->get_param( 'vendor_id' );

		if ( empty( $vendor_id ) ) {
			$rest_response->set_data( 'Vendor ID is required' );
			$rest_response->set_status( 400 );
			return rest_ensure_response( $rest_response );
		}

		// Construct the DELETE URL for Zoho Inventory
		$delete_url = $zoho_inventory_url . "inventory/v1/contacts/$vendor_id?organization_id=$zoho_inventory_oid";

		// Handle the API request to delete the vendor
		$execute_curl_call_handle = new CMBIRD_API_Handler_Zoho();
		$json = $execute_curl_call_handle->execute_curl_call_delete( $delete_url );

		$code = $json->code;
		if ( 0 === (int) $code ) {
			$response['code'] = 200;
			$response['json'] = $json;
			$rest_response->set_data( $response );
			$rest_response->set_status( 200 );
		} else {
			$response['code'] = $json->code;
			$response['message'] = $json->message;
			$rest_response->set_data( $response );
			$rest_response->set_status( 200 );
		}

		return rest_ensure_response( $rest_response );
	}


	public function create_zi_vendor( $request ) {
		$zoho_inventory_oid = get_option( 'cmbird_zoho_inventory_oid' );
		$zoho_inventory_url = get_option( 'cmbird_zoho_inventory_url' );

		$rest_response = new WP_REST_Response();
		$rest_response->set_data( $this->empty_response );
		$rest_response->set_status( 400 );

		// Get Vendor Details (payload) from the request
		$vendor_data = $request->get_json_params();
		if ( empty( $vendor_data ) ) {
			$rest_response->set_data( 'Vendor data is required to update' );
			return rest_ensure_response( $rest_response );
		}

		// Construct the Zoho Inventory URL for updating the vendor
		$update_url = $zoho_inventory_url . "inventory/v1/contacts?organization_id=$zoho_inventory_oid";

		// Get Vendor from request.
		$vendor = $request['vendor'];

		// Send the PUT or PATCH request to Zoho API to update the vendor
		$execute_curl_call_handle = new CMBIRD_API_Handler_Zoho();
		$json = $execute_curl_call_handle->execute_curl_call_post(
			$update_url,
			array(
				'JSONString' => wp_json_encode( $vendor ),
			)
		);
		$code = $json->code;
		if ( 0 === (int) $code ) {
			$response['code'] = 200;
			$response['vendor'] = $json->contact;
			$rest_response->set_data( $response );
			$rest_response->set_status( 200 );
		} else {
			$response['code'] = $json->code;
			$response['message'] = $json->message;
			$rest_response->set_data( $response );
			$rest_response->set_status( 200 );
		}
		return rest_ensure_response( $rest_response );
	}

	public function update_zi_vendor_details( $request ): WP_REST_Response {
		$zoho_inventory_oid = get_option( 'cmbird_zoho_inventory_oid' );
		$zoho_inventory_url = get_option( 'cmbird_zoho_inventory_url' );

		$rest_response = new WP_REST_Response();
		$rest_response->set_data( $this->empty_response );
		$rest_response->set_status( 400 );

		// Get Vendor ID from the request
		$vendor_id = $request->get_param( 'vendor_id' );
		if ( empty( $vendor_id ) ) {
			$rest_response->set_data( 'Vendor ID is required' );
			return rest_ensure_response( $rest_response );
		}

		// Get Vendor Details (payload) from the request
		$vendor_data = $request->get_json_params();
		if ( empty( $vendor_data ) ) {
			$rest_response->set_data( 'Vendor data is required to update' );
			return rest_ensure_response( $rest_response );
		}

		// Construct the Zoho Inventory URL for updating the vendor
		$update_url = $zoho_inventory_url . "inventory/v1/contacts/$vendor_id?organization_id=$zoho_inventory_oid";

		// Get Vendor from request.
		$vendor = $request['vendor'];

		// Send the PUT or PATCH request to Zoho API to update the vendor
		$execute_curl_call_handle = new CMBIRD_API_Handler_Zoho();
		$json = $execute_curl_call_handle->execute_curl_call_put(
			$update_url,
			array(
				'JSONString' => wp_json_encode( $vendor ),
			)
		);
		$code = $json->code;
		if ( 0 === (int) $code ) {
			$response['code'] = 200;
			$response['vendor'] = $json->contact;
			$rest_response->set_data( $response );
			$rest_response->set_status( 200 );
		} else {
			$response['code'] = $json->code;
			$response['message'] = $json->message;
			$rest_response->set_data( $response );
			$rest_response->set_status( 200 );
		}
		return rest_ensure_response( $rest_response );
	}

	public function get_zi_warehouses( $request ): WP_REST_RESPONSE {
		$zoho_inventory_oid = get_option( 'cmbird_zoho_inventory_oid' );
		$zoho_inventory_url = get_option( 'cmbird_zoho_inventory_url' );
		$get_url = $zoho_inventory_url . "inventory/v1/settings/warehouses?organization_id=$zoho_inventory_oid";
		return $this->handle_get_api_request( $get_url, 'warehouses', 'warehouses' );
	}

	public function create_zi_purchase_order( $request ): WP_REST_RESPONSE {
		$rest_response = new WP_REST_Response();
		$rest_response->set_data( $this->empty_response );
		$rest_response->set_status( 400 );

		// Get invoice id from the request
		$purchaseorder = $request['purchaseorder'];

		if ( empty( $purchaseorder ) ) {
			$rest_response->set_data( 'purchaseorder is required' );
			$rest_response->set_status( 400 );
			return rest_ensure_response( $rest_response );
		}
		$zoho_inventory_oid = get_option( 'cmbird_zoho_inventory_oid' );
		$zoho_inventory_url = get_option( 'cmbird_zoho_inventory_url' );
		$get_url = $zoho_inventory_url . "inventory/v1/purchaseorders?organization_id=$zoho_inventory_oid&ignore_auto_number_generation=false";

		$execute_curl_call_handle = new CMBIRD_API_Handler_Zoho();
		$json = $execute_curl_call_handle->execute_curl_call_post(
			$get_url,
			array(
				'JSONString' => wp_json_encode( $purchaseorder ),
			)
		);
		$code = $json->code;
		$response['url'] = $get_url;
		if ( 0 === (int) $code ) {
			$response['purchase_order'] = $json->purchaseorder;
			$rest_response->set_data( $response );
			$rest_response->set_status( 200 );
		} else {
			$response['data'] = $json;
			$rest_response->set_data( $response );
			$rest_response->set_status( 400 );
		}
		return rest_ensure_response( $rest_response );
	}
}
