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

		// Sanitize and decode JSON data from AJAX request
		$this->request = array_map( 'sanitize_text_field', wp_unslash( $_REQUEST ) );
		$json_data = isset( $_POST['data'] ) ? wp_unslash( $_POST['data'] ) : ''; // Assuming JSON data is sent in 'data'

		if ( $json_data ) {
			$decode = json_decode( $json_data, true );
			if ( ! empty( $decode ) && is_array( $decode ) ) {
				$data = $this->extract_data( $decode, $keys );
				$this->data = ! empty( $data ) ? $data : array();
			}
		}
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
