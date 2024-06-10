<?php

namespace RMS\API;

use WP_REST_Response;
use WP_REST_Server;
use WP_REST_Controller;

defined( 'RMS_PLUGIN_NAME' ) || exit();

class Exact extends WP_REST_Controller {

	protected $prefix = 'v2';
	protected $rest_base = 'exact-webhooks';

	public function __construct() {

		register_rest_route(
			$this->prefix,
			'/' . $this->rest_base . '/process/',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array( $this, 'process_webhook' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function process_webhook( $request ) {
		$data = $request->get_json_params();
		// if current site contains localhost, use https://dev.commercebird.com
		$current_site_url = isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : '';
		$site_url = str_contains( $current_site_url, 'localhost' ) ? 'https://dev.commercebird.com' : site_url();

		// TODO: process the webhook data
		// make new POST API call to our api endpoint and send the object_id and topic as body
		$response = wp_remote_post(
			'https://api.commercebird.com/webapp/customs/exact/webhooks/object',
			array(
				'headers' => array(
					'Accept' => 'application/json',
					'Content-Type' => 'application/json',
					'zohowooagent' => $site_url,
				),
				'timeout' => 60,
				'sslverify' => false,
				'body' => wp_json_encode( $data ),
			)
		);
		// get the "description" from the response body which serves as the wc order_id in our case
		$body = wp_remote_retrieve_body( $response );
		$order_id = json_decode( $body )->description;
		// get the order object
		$order = wc_get_order( $order_id );
		// update the status to complete
		$order->update_status( 'completed', 'Invoice Paid in Exact Online' );
		$order->save();
		return new WP_REST_Response( 'Webhook processed', 200 );
	}

}