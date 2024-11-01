<?php

namespace CommerceBird\Admin\Traits;

use CommerceBird\Admin\Template;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait AjaxRequest {

	// Array to store registered AJAX requests
	private array $request = array();
	// Array to store registered AJAX response
	private array $response = array( 'message' => 'Saved' );
	// Array to store registered AJAX posted data
	private array $data = array();
	// Array to store registered AJAX errors
	private array $errors = array();

	private function load_actions() {
		foreach ( self::ACTIONS as $action => $handler ) {
			add_action(
				$this->action( $action ),
				array( $this, $handler ),
			);
		}
	}

	/**
	 * Serve data to AJAX request.
	 */
	private function serve(): void {
		if ( count( $this->errors ) > 0 ) {
			wp_send_json_error( $this->errors );
		}

		wp_send_json_success( $this->response );
	}

	/**
	 * Verify AJAX request.
	 */
	private function verify( array $keys = array() ): void {
		check_ajax_referer( Template::NAME, 'security_token' );

		// Initialize the response and errors
		$this->response = array(
			'success' => true,
		);
		$this->errors = array();
		$this->request = array_map( 'sanitize_text_field', wp_unslash( $_REQUEST ) );

		// Try to detect JSON content in the request if specific data key is not used
		$json_data = null;

		if ( empty( $_POST ) ) {
			// Attempt to retrieve raw JSON content if POST is empty
			$contents = trim( file_get_contents( 'php://input' ) );

			// Check if contents contain valid JSON
			if ( $this->is_json( $contents ) ) {
				$json_data = sanitize_text_field( $contents );
			}
		}

		// Decode JSON if found, then extract data
		if ( $json_data ) {
			$decode = json_decode( $json_data, true );
			if ( ! empty( $decode ) ) {
				$data = $this->extract_data( $decode, $keys );
				$this->data = ! empty( $data ) ? $data : array();
			}
		}
	}


	/**
	 * Utility to check if a string is JSON.
	 */
	private function is_json( string $string ): bool {
		json_decode( $string );
		return json_last_error() === JSON_ERROR_NONE;
	}

	/**
	 * Extracts data from an array using the given keys.
	 *
	 * @param array $sanitized The array from which to extract data.
	 * @param array $keys The keys to use for extraction.
	 *
	 * @return array The extracted data.
	 */
	public function extract_data( array $sanitized, array $keys ): array {
		return array_intersect_key( $sanitized, array_flip( $keys ) );
	}

	/**
	 * Register AJAX actions.
	 */
	private function action( $action ): string {
		return sprintf( 'wp_ajax_%s-%s', Template::NAME, $action );
	}
}
