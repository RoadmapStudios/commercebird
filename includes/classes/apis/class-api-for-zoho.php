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
			'/zoho-accesstoken/',
			array(
				'methods'             => WP_REST_Server::READABLE, /* WP_REST_Server::READABLE */
				'callback'            => array( $this, 'get_token' ),
				'permission_callback' => array( $this, 'check_origin' ),
			)
		);
	}

	public function get_token(): WP_REST_Response {
		$rest_response = new WP_REST_Response();
		$rest_response->set_data( $this->empty_response );
		$rest_response->set_status( 400 );

		// cron sync options
		$opt_category = get_option( 'zoho_item_category' );
		if ( $opt_category ) {
			$opt_category = unserialize( $opt_category );
		} else {
			$opt_category = array();
		}
		$cron_options                        = array();
		$cron_options['disable_name']        = get_option( 'zoho_disable_itemname_sync_status' );
		$cron_options['disable_price']       = get_option( 'zoho_disable_itemprice_sync_status' );
		$cron_options['disable_image']       = get_option( 'zoho_disable_itemimage_sync_status' );
		$cron_options['disable_description'] = get_option( 'zoho_disable_itemdescription_sync_status' );
		$cron_options['disable_stock']       = get_option( 'zoho_stock_sync_status' );
		$cron_options['accounting_stock']    = get_option( 'zoho_enable_accounting_stock_status' );

		// connection
		$zoho_inventory_oid = get_option( 'zoho_inventory_oid' );
		$zoho_inventory_url = get_option( 'zoho_inventory_url' );
		$get_url            = $zoho_inventory_url . 'api/v1/organizations/' . $zoho_inventory_oid . '?organization_id=' . $zoho_inventory_oid;

		$executeCurlCallHandle = new ExecutecallClass();
		$json                  = $executeCurlCallHandle->ExecuteCurlCallGet( $get_url );
		$code                  = $json->code;
		if ( 0 === (int) $code ) {
			$access_token                = get_option( 'zoho_inventory_access_token' );
			$response['message']         = $json->message;
			$response['access_token']    = 'Bearer ' . $access_token;
			$response['zi_org_id']       = $zoho_inventory_oid;
			$response['zi_api_url']      = $zoho_inventory_url;
			$response['item_categories'] = $opt_category;
			$response['cron_options']    = $cron_options;

			$rest_response->set_data( $response );
			$rest_response->set_status( 200 );

		} else {
			$rest_response->set_data( 'connection is not yet setup' );
			$rest_response->set_status( 400 );
		}

		return rest_ensure_response( $rest_response );
	}
}
