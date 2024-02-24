<?php

namespace RMS\API;

use Exception;
use RMS\Admin\Actions\Ajax\ZohoInventoryAjax;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'RMS_PLUGIN_NAME' ) || exit();


trait Api {

	private static string $namespace = 'v2';

	private string $empty_response = 'No data found from webhook. check on  ' . __METHOD__ . ' of ' . __CLASS__ . ' on line ' . __LINE__;

	public static function endpoint(): string {
		return get_rest_url() . self::$namespace . '/' . self::$endpoint;
	}

	public function check_origin( WP_REST_Request $request ) {
		// Check if the request is from the domain example.com
		$allowed_domain = array(
			'http://localhost:8100',
			'https://app.commercebird.com',
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

	public function permission_check( WP_REST_Request $request ) {
		$authorization = $request->get_header( 'Authorization' );
		$subscription  = ZohoInventoryAjax::instance()->get_subscription_data();
		$premium       = isset( $subscription['plan'] ) ? in_array( 'Wooventory - Premium', $subscription['plan'] ) : false;
		return password_verify( 'commercebird-zi-webhook-token', $authorization ) && $premium;
	}

	public function handle( WP_REST_Request $request ) {
		$response = new WP_REST_Response();
		$response->set_data( $this->empty_response );
		$response->set_status( 404 );
		$data = $request->get_json_params();
		if ( empty( $data ) ) {
			return rest_ensure_response( $response );
		}
		if ( array_key_exists( 'JSONString', $data ) ) {
			$data = str_replace( '\\', '', $data['JSONString'] );
		}
		if ( ! empty( $data ) ) {
			try {
				$response = $this->process( $data );
			} catch ( Exception $exception ) {
				$this->write_log( $exception );
				$response->set_data( $exception->getMessage() );
				$response->set_status( 500 );
			}
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Logs error messages with timestamp and data.
	 *
	 * @param mixed $data The data to be encoded and logged.
	 * @return void
	 */
	private function write_log( $data ): void {
		$timestamp = gmdate( 'Y-m-d H:i:s' );
		$jsonData  = wp_json_encode( $data, JSON_PRETTY_PRINT );
		$logDir    = __DIR__ . '/' . self::$endpoint . '-webhook.log';

		$logMessage = sprintf( '%s - %s %s', $timestamp, $jsonData, PHP_EOL );
		error_log( $logMessage, 3, $logDir );
	}
}
