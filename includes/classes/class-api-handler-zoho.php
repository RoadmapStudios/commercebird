<?php

/**
 * All Execute Call Class related functions.
 *
 * @package  Inventory
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once ABSPATH . 'wp-admin/includes/file.php';
class CMBIRD_API_Handler_Zoho {
	/**
	 * @var array|array[]
	 */
	private array $config;

	public function __construct() {
		$this->config = array(
			'ExecutecallZI' => array(
				'OID' => get_option( 'cmbird_zoho_inventory_oid' ),
				'ATOKEN' => get_option( 'cmbird_zoho_inventory_access_token' ),
				'RTOKEN' => get_option( 'cmbird_zoho_inventory_refresh_token' ),
				'EXPIRESTIME' => get_option( 'cmbird_zoho_inventory_timestamp' ),
			),
			'ExecutecallZCRM' => array(
				'ATOKEN' => get_option( 'cmbird_zoho_crm_access_token' ),
				'RTOKEN' => get_option( 'cmbird_zoho_crm_refresh_token' ),
				'EXPIRESTIME' => get_option( 'cmbird_zoho_crm_timestamp' ),
			),
		);
	}

	// Get Call Zoho
	public function execute_curl_call_get( $url ) {
		// Sleep for .5 sec for each api calls
		usleep( 500000 );
		$handlefunction = new CMBIRD_Auth_Zoho();

		// if $url contains 'inventory' use zoho_access_token, refresh token and timestamp
		if ( strpos( $url, 'inventory' ) !== false ) {
			$app_name = 'zoho_inventory';
			$zoho_access_token = $this->config['ExecutecallZI']['ATOKEN'];
			$zoho_refresh_token = $this->config['ExecutecallZI']['RTOKEN'];
			$zoho_timestamp = $this->config['ExecutecallZI']['EXPIRESTIME'];
			$authorization_header = 'Bearer';
		} else {
			$app_name = 'zoho_crm';
			$zoho_access_token = $this->config['ExecutecallZCRM']['ATOKEN'];
			$zoho_refresh_token = $this->config['ExecutecallZCRM']['RTOKEN'];
			$zoho_timestamp = $this->config['ExecutecallZCRM']['EXPIRESTIME'];
			$authorization_header = 'Zoho-oauthtoken';
		}
		$current_time = strtotime( gmdate( 'Y-m-d H:i:s' ) );
		if ( $zoho_timestamp < $current_time ) {

			$respo_at_js = $handlefunction->get_zoho_refresh_token( $zoho_refresh_token, $app_name );
			if ( empty( $respo_at_js ) || ! array_key_exists( 'access_token', $respo_at_js ) ) {
				return new WP_Error( 403, 'Access denied!' );
			}
			$zoho_access_token = $respo_at_js['access_token'];
			if ( 'zoho_inventory' === $app_name ) {
				update_option( 'cmbird_zoho_inventory_access_token', $respo_at_js['access_token'] );
				update_option( 'cmbird_zoho_inventory_timestamp', strtotime( gmdate( 'Y-m-d H:i:s' ) ) + $respo_at_js['expires_in'] );
			} else {
				update_option( 'cmbird_zoho_crm_access_token', $respo_at_js['access_token'] );
				update_option( 'cmbird_zoho_crm_timestamp', strtotime( gmdate( 'Y-m-d H:i:s' ) ) + $respo_at_js['expires_in'] );
			}
		}

		$args = array(
			'headers' => array(
				'Authorization' => "$authorization_header $zoho_access_token",
			),
		);

		$response = wp_remote_get( $url, $args );

		// get the status code of the response
		$status_code = wp_remote_retrieve_response_code( $response );
		// if code is 429, update the option "zoho_rate_limit_exceeded" to true
		if ( 429 === $status_code ) {
			update_option( 'cmbird_zoho_rate_limit_exceeded', true );
		} else {
			update_option( 'cmbird_zoho_rate_limit_exceeded', false );
		}

		// Check if the request was successful
		if ( ! is_wp_error( $response ) ) {
			// If successful, get the body of the response
			$body = wp_remote_retrieve_body( $response );

			// Decode JSON response
			return json_decode( $body );
		} else {
			// If there was an error, handle it
			$error_message = is_wp_error( $response ) ? $response->get_error_message() : 'Unknown error.';
			return "Error: $error_message";
		}
	}

	// Post Call Zoho

	public function execute_curl_call_post( $url, $data ) {
		$handlefunction = new CMBIRD_Auth_Zoho();

		// if $url contains 'inventory' use zoho__access_token, refresh token and timestamp
		if ( strpos( $url, 'inventory' ) !== false ) {
			$app_name = 'zoho_inventory';
			$zoho_access_token = $this->config['ExecutecallZI']['ATOKEN'];
			$zoho_refresh_token = $this->config['ExecutecallZI']['RTOKEN'];
			$zoho_timestamp = $this->config['ExecutecallZI']['EXPIRESTIME'];
			$authorization_header = 'Bearer';
		} else {
			$app_name = 'zoho_crm';
			$zoho_access_token = $this->config['ExecutecallZCRM']['ATOKEN'];
			$zoho_refresh_token = $this->config['ExecutecallZCRM']['RTOKEN'];
			$zoho_timestamp = $this->config['ExecutecallZCRM']['EXPIRESTIME'];
			$authorization_header = 'Zoho-oauthtoken';
		}

		$current_time = strtotime( gmdate( 'Y-m-d H:i:s' ) );

		if ( $zoho_timestamp < $current_time ) {

			$respo_at_js = $handlefunction->get_zoho_refresh_token( $zoho_refresh_token, $app_name );

			$zoho_access_token = $respo_at_js['access_token'];
			if ( 'zoho_inventory' === $app_name ) {
				update_option( 'cmbird_zoho_inventory_access_token', $respo_at_js['access_token'] );
				update_option( 'cmbird_zoho_inventory_timestamp', strtotime( gmdate( 'Y-m-d H:i:s' ) ) + $respo_at_js['expires_in'] );
			} else {
				update_option( 'cmbird_zoho_crm_access_token', $respo_at_js['access_token'] );
				update_option( 'cmbird_zoho_crm_timestamp', strtotime( gmdate( 'Y-m-d H:i:s' ) ) + $respo_at_js['expires_in'] );
			}
		}

		$args = array(
			'body' => $data,
			'headers' => array(
				'Authorization' => "$authorization_header $zoho_access_token",
			),
		);
		$response = wp_remote_post( $url, $args );

		// get the status code of the response
		$status_code = wp_remote_retrieve_response_code( $response );
		// if code is 429, update the option "zoho_rate_limit_exceeded" to true
		if ( 429 === $status_code ) {
			update_option( 'cmbird_zoho_rate_limit_exceeded', true );
		} else {
			update_option( 'cmbird_zoho_rate_limit_exceeded', false );
		}

		// Check if the request was successful
		if ( ! is_wp_error( $response ) ) {
			// If successful, get the body of the response
			$body = wp_remote_retrieve_body( $response );
			// Decode JSON response
			return json_decode( $body );
		} else {
			// If there was an error, handle it
			$error_message = is_wp_error( $response ) ? $response->get_error_message() : 'Unknown error.';
			return "Error: $error_message";
		}
	}

	// Put Call Zoho

	public function execute_curl_call_put( $url, $data ) {

		$handlefunction = new CMBIRD_Auth_Zoho();

		// if $url contains 'inventory' use zoho_inventory_access_token, refresh token and timestamp
		if ( strpos( $url, 'inventory' ) !== false ) {
			$app_name = 'zoho_inventory';
			$zoho_inventory_access_token = $this->config['ExecutecallZI']['ATOKEN'];
			$zoho_inventory_refresh_token = $this->config['ExecutecallZI']['RTOKEN'];
			$zoho_inventory_timestamp = $this->config['ExecutecallZI']['EXPIRESTIME'];
			$authorization_header = 'Bearer';
		} else {
			$app_name = 'zoho_crm';
			$zoho_inventory_access_token = $this->config['ExecutecallZCRM']['ATOKEN'];
			$zoho_inventory_refresh_token = $this->config['ExecutecallZCRM']['RTOKEN'];
			$zoho_inventory_timestamp = $this->config['ExecutecallZCRM']['EXPIRESTIME'];
			$authorization_header = 'Zoho-oauthtoken';
		}

		$current_time = strtotime( gmdate( 'Y-m-d H:i:s' ) );

		if ( $zoho_inventory_timestamp < $current_time ) {

			$respo_at_js = $handlefunction->get_zoho_refresh_token( $zoho_inventory_refresh_token, $app_name );
			$zoho_inventory_access_token = $respo_at_js['access_token'];
			if ( 'zoho_inventory' === $app_name ) {
				update_option( 'cmbird_zoho_inventory_access_token', $respo_at_js['access_token'] );
				update_option( 'cmbird_zoho_inventory_timestamp', strtotime( gmdate( 'Y-m-d H:i:s' ) ) + $respo_at_js['expires_in'] );
			} else {
				update_option( 'cmbird_zoho_crm_access_token', $respo_at_js['access_token'] );
				update_option( 'cmbird_zoho_crm_timestamp', strtotime( gmdate( 'Y-m-d H:i:s' ) ) + $respo_at_js['expires_in'] );
			}
		}

		$args = array(
			'body' => $data,
			'headers' => array(
				'Authorization' => "$authorization_header $zoho_inventory_access_token",
			),
			'method' => 'PUT',
		);
		$response = wp_remote_request( $url, $args );

		// get the status code of the response
		$status_code = wp_remote_retrieve_response_code( $response );
		// if code is 429, update the option "zoho_rate_limit_exceeded" to true
		if ( 429 === $status_code ) {
			update_option( 'cmbird_zoho_rate_limit_exceeded', true );
		} else {
			update_option( 'cmbird_zoho_rate_limit_exceeded', false );
		}

		// Check if the request was successful
		if ( ! is_wp_error( $response ) ) {
			// If successful, get the body of the response
			$body = wp_remote_retrieve_body( $response );
			// Decode JSON response
			return json_decode( $body );
		} else {
			// If there was an error, handle it
			$error_message = is_wp_error( $response ) ? $response->get_error_message() : 'Unknown error.';
			return "Error: $error_message";
		}
	}

	/**
	 * Delete Call to Zoho
	 * @param string $url
	 * @return mixed
	 */
	public function execute_curl_call_delete( $url ) {
		$handlefunction = new CMBIRD_Auth_Zoho();

		// if $url contains 'inventory' use zoho_inventory_access_token, refresh token and timestamp
		if ( strpos( $url, 'inventory' ) !== false ) {
			$app_name = 'zoho_inventory';
			$zoho_inventory_access_token = $this->config['ExecutecallZI']['ATOKEN'];
			$zoho_inventory_refresh_token = $this->config['ExecutecallZI']['RTOKEN'];
			$zoho_inventory_timestamp = $this->config['ExecutecallZI']['EXPIRESTIME'];
			$authorization_header = 'Bearer';
		} else {
			$app_name = 'zoho_crm';
			$zoho_inventory_access_token = $this->config['ExecutecallZCRM']['ATOKEN'];
			$zoho_inventory_refresh_token = $this->config['ExecutecallZCRM']['RTOKEN'];
			$zoho_inventory_timestamp = $this->config['ExecutecallZCRM']['EXPIRESTIME'];
			$authorization_header = 'Zoho-oauthtoken';
		}

		$current_time = strtotime( gmdate( 'Y-m-d H:i:s' ) );

		if ( $zoho_inventory_timestamp < $current_time ) {

			$respo_at_js = $handlefunction->get_zoho_refresh_token( $zoho_inventory_refresh_token, $app_name );
			$zoho_inventory_access_token = $respo_at_js['access_token'];
			if ( 'zoho_inventory' === $app_name ) {
				update_option( 'cmbird_zoho_inventory_access_token', $respo_at_js['access_token'] );
				update_option( 'cmbird_zoho_inventory_timestamp', strtotime( gmdate( 'Y-m-d H:i:s' ) ) + $respo_at_js['expires_in'] );
			} else {
				update_option( 'cmbird_zoho_crm_access_token', $respo_at_js['access_token'] );
				update_option( 'cmbird_zoho_crm_timestamp', strtotime( gmdate( 'Y-m-d H:i:s' ) ) + $respo_at_js['expires_in'] );
			}
		}

		$args = array(
			'headers' => array(
				'Authorization' => "$authorization_header $zoho_inventory_access_token",
			),
			'method' => 'DELETE',
		);
		$response = wp_remote_request( $url, $args );

		// get the status code of the response
		$status_code = wp_remote_retrieve_response_code( $response );
		// if code is 429, update the option "zoho_rate_limit_exceeded" to true
		if ( 429 === $status_code ) {
			update_option( 'cmbird_zoho_rate_limit_exceeded', true );
		} else {
			update_option( 'cmbird_zoho_rate_limit_exceeded', false );
		}

		// Check if the request was successful
		if ( ! is_wp_error( $response ) ) {
			// If successful, get the body of the response
			$body = wp_remote_retrieve_body( $response );
			// Decode JSON response
			return json_decode( $body );
		} else {
			// If there was an error, handle it
			$error_message = is_wp_error( $response ) ? $response->get_error_message() : 'Unknown error.';
			return "Error: $error_message";
		}
	}

	/**
	 *
	 * Get Call Zoho Image
	 * @param mixed $url - URL of the image.
	 * @return string
	 */
	public function execute_curl_call_image_get( $url, $image_name ) {
		// $fd = fopen( __DIR__ . '/execute_curl_call_image_get.txt', 'w' );
		global $wp_filesystem;
		WP_Filesystem();

		$handlefunction = new CMBIRD_Auth_Zoho();
		$zoho_inventory_access_token = $this->config['ExecutecallZI']['ATOKEN'];
		$zoho_inventory_refresh_token = $this->config['ExecutecallZI']['RTOKEN'];
		$zoho_inventory_timestamp = $this->config['ExecutecallZI']['EXPIRESTIME'];

		$current_time = strtotime( gmdate( 'Y-m-d H:i:s' ) );

		if ( $zoho_inventory_timestamp < $current_time ) {

			$respo_at_js = $handlefunction->get_zoho_refresh_token( $zoho_inventory_refresh_token, 'zoho_inventory' );

			$zoho_inventory_access_token = $respo_at_js['access_token'];
			update_option( 'cmbird_zoho_inventory_access_token', $respo_at_js['access_token'] );
			update_option( 'cmbird_zoho_inventory_timestamp', strtotime( gmdate( 'Y-m-d H:i:s' ) ) + $respo_at_js['expires_in'] );

		}
		$args = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $zoho_inventory_access_token,
			),
		);
		$response = wp_remote_get( $url, $args );
		// fwrite( $fd, PHP_EOL . 'Response : ' . print_r( $response, true ) );
		// fclose( $fd );

		// Check if the request was successful
		if ( ! is_wp_error( $response ) ) {
			// If successful, get the body of the response
			$body = wp_remote_retrieve_body( $response );

			// Set up the upload directory
			$upload = wp_upload_dir();
			$absolute_upload_path = $upload['basedir'] . '/zoho_image/';
			$url_upload_path = $upload['baseurl'] . '/zoho_image/';

			// Generate a unique image name
			$img = wp_rand() . '_' . $image_name;
			$upload_dir = $absolute_upload_path . '/' . $img;

			// remove the file if it exists
			if ( file_exists( $upload_dir ) ) {
				wp_delete_file( $upload_dir );
			}

			// Create the directory if it doesn't exist
			if ( ! is_dir( $absolute_upload_path ) ) {
				wp_mkdir_p( $absolute_upload_path );
			}
			// Save the image file
			try {
				$wp_filesystem->put_contents( $upload_dir, $body );
			} catch (Exception $e) {
				wp_delete_file( $upload_dir );
				// If there was an error, handle it
				$error_message = $e->getMessage();
				echo esc_html( "Error image import: $error_message" );
				return '';
			}
			// Use trailingslashit to make sure the URL ends with a single slash
			return trailingslashit( $url_upload_path ) . $img;
		} else {
			// If there was an error, handle it
			$error_message = is_wp_error( $response ) ? $response->get_error_message() : 'Unknown error.';
			echo esc_html( "Error image import: $error_message" );
			return '';
		}
	}
}
