<?php

namespace RMS\API;

use ExecutecallClass;
use WP_REST_Response;
use WP_REST_Server;

defined( 'RMS_PLUGIN_NAME' ) || exit();

class CreateOrderWebhook {

	use Api;

	private string $endpoint = 'zoho-woo-order';


	public function __construct() {
		register_rest_route(
			$this->namespace,
			$this->endpoint,
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => '__return_true',
			)
		);
	}


	private function process( array $order_data ): WP_REST_Response {
		$response = new WP_REST_Response();
		$response->set_data( $this->empty_response );
		$response->set_status( 400 );
		if ( ! empty( $order_data['salesorder'] ) ) {

		}

		return $response;
	}
}
