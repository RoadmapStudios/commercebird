<?php

namespace RMS\API;

use Exception;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'RMS_PLUGIN_NAME' ) || exit();


trait Api {
	private string $namespace = 'v2';

	private string $empty_response = 'No product found from webhook. check on  ' . __METHOD__ . ' of ' . __CLASS__ . ' on line ' . __LINE__;

	public function check_origin( WP_REST_Request $request ) {
		// Check if the request is from the domain example.com
		$allowed_domain = array(
			'http://localhost:8100',
			'https://app.wooventory.com',
			'capacitor://localhost',
			'http://localhost',
		);
		$origin         = $request->get_header( 'Origin' );
		// check if the origin is in our array of allowed domains
		if ( in_array( $origin, $allowed_domain, true ) ) {
			// Allow the REST request
			return true;
		}

		// If not from the allowed domain, return a permission error
		return new WP_Error( 'rest_forbidden', 'Sorry, this API endpoint is not accessible from your domain.', array( 'status' => 403 ) );
	}

	public function handle( WP_REST_Request $request ) {
		$response = new WP_REST_Response();
		$response->set_data( $this->empty_response );
		$response->set_status( 404 );
		$data = $request->get_json_params();
		if ( array_key_exists( 'JSONString', $data ) ) {
			$data = str_replace( '\\', '', $data['JSONString'] );
		} else {
			$data = str_replace( '\\', '', $data );
		}
		$this->write_log( $data );
		if ( ! empty( $data ) ) {
			try {
				$response = $this->process( $data );

			} catch ( Exception $exception ) {
				error_log(
					__DIR__ . '/error-' . $this->endpoint . '.log',
					3,
					wp_json_encode(
						array(
							'error' => $exception->getMessage(),
							'line'  => $exception->getLine(),
							'file'  => $exception->getFile(),
							'trace' => $exception->getTraceAsString(),
						)
					),
					JSON_PRETTY_PRINT
				);
				$response->set_data( $exception->getMessage() );
				$response->set_status( 500 );
			}
		}

		return rest_ensure_response( $response );
	}

	/**
	 * @param $data
	 *
	 * @return void
	 */
	private function write_log( $data ): void {
		error_log( sprintf( '%s - %s%s', gmdate( 'Y-m-d H:i:s' ), wp_json_encode( $data, JSON_PRETTY_PRINT ), PHP_EOL ), 3, sprintf( '%s/%s-webhook.log', __DIR__, $this->endpoint ) );
	}
}
