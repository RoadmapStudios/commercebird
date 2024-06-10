<?php

namespace RMS\API;

use WP_REST_Response;
use WP_REST_Server;
use WP_REST_Controller;
use RMS\API\Authenticatable;

defined( 'RMS_PLUGIN_NAME' ) || exit();

class Exact extends WP_REST_Controller {
    use Authenticatable;

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
		$webhook_secret = '';
		// use authenticatable trait to verify the request
		if ( ! $this->authenticate( $data, $webhook_secret ) ) {
			return new WP_REST_Response( 'Unauthorized', 401 );
		}
		// TODO: process the webhook data

        return new WP_REST_Response( 'Webhook processed', 200 );
	}

}