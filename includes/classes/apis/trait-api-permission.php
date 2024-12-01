<?php

namespace CommerceBird\API;

use Exception;
use CommerceBird\Admin\Actions\Ajax\ZohoInventoryAjax;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait Api {

	private static string $namespace = 'v2';

	private string $empty_response = 'No data found from webhook. check on  ' . __METHOD__ . ' of ' . __CLASS__ . ' on line ' . __LINE__;

	public static function endpoint(): string {
		return get_rest_url() . self::$namespace . '/' . self::$endpoint;
	}

	public function permission_check() {
		// Get all headers
		$headers = getallheaders();
		// Check if the Authorization header is present
		if ( isset( $headers['Authorization'] ) ) {
			$authorization = $headers['Authorization'];
			if ( ! password_verify( 'commercebird-zi-webhook-token', $authorization ) ) {
				return new WP_Error(
					'rest_forbidden',
					'You are not header to access this endpoint',
					array(
						'status' => 400,
						'header' => $authorization,
					)
				);
			}
		}
		$subscription = ZohoInventoryAjax::instance()->get_subscription_data();
		if ( isset( $subscription['plan'] ) ) {
			$subscription_plan = implode( ' ', $subscription['plan'] );
			if ( stripos( $subscription_plan, 'Premium' ) === false ) {
				return new WP_Error(
					'rest_forbidden',
					$subscription_plan,
					array(
						'status' => 403,
						'data' => strpos( 'Premium', $subscription_plan ),
					)
				);
			}
			return true;
		}
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
		// Create a unique key based on the payload
		$payload_hash = wp_hash( wp_json_encode( $data ) );
		$lock_key = 'cmbird_processing_payload_' . $payload_hash;
		// Check and set lock using update_option
		$lock_acquired = add_option( $lock_key, time() );
		if ( ! $lock_acquired ) {
			$response->set_data( 'Duplicate payload ignored. Already being processed.' );
			$response->set_status( 200 );
			return rest_ensure_response( $response );
		}
		// Process the payload
		if ( ! empty( $data ) ) {
			try {
				$response = $this->process( $data );
			} catch (Exception $exception) {
				$response->set_data( $exception->getMessage() );
				$response->set_status( 500 );
			}
		}
		// Clean up the lock after processing
		delete_option( $lock_key );
		return rest_ensure_response( $response );
	}
}
