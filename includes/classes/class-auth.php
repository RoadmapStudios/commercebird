<?php

class Classfunctions {

	/**
	 * @var array|array[]
	 */
	private array $config;

	public function __construct() {
		$config = array(

			'ServiceZI' => array(
				'OID' => get_option( 'zoho_inventory_oid' ),
				'CLIENTSECRET' => get_option( 'zoho_inventory_cs' ),
				'CLIENTID' => get_option( 'zoho_inventory_cid' ),
				'REDIRECTURL' => get_option( 'authorization_redirect_uri' ),
				'APIURL' => get_option( 'zoho_inventory_url' ),
				'DOMAINNAME' => get_option( 'zoho_inventory_domain' ),
				'SCOPE' => 'ZohoInventory.FullAccess.all',
				//'STATE' => wp_create_nonce('redirect_url'),
				'AUTHURL' => 'https://accounts.zoho.' . get_option( 'zoho_inventory_domain' ) . '/oauth/v2/token',

			),
			'ServiceZCRM' => array(
				'CLIENTSECRET' => get_option( 'zoho_crm_cs' ),
				'CLIENTID' => get_option( 'zoho_crm_cid' ),
				'REDIRECTURL' => get_option( 'authorization_redirect_uri' ),
				'APIURL' => get_option( 'zoho_crm_url' ),
				'DOMAINNAME' => get_option( 'zoho_crm_domain' ),
				'SCOPE' => 'ZohoCRM.users.ALL,ZohoCRM.bulk.ALL,ZohoCRM.modules.ALL,ZohoCRM.settings.ALL,ZohoCRM.org.ALL,profile.userphoto.READ,ZohoFiles.files.CREATE',
				//'STATE' => wp_create_nonce('redirect_url'),
				'AUTHURL' => 'https://accounts.zoho.' . get_option( 'zoho_crm_domain' ) . '/oauth/v2/token',
			),

		);

		$this->config = $config;
	}

	public function get_zoho_access_token( $code, $app_name ) {

		$headers = array( 'Content-Type: application/x-www-form-urlencoded' );
		switch ( $app_name ) {
			case 'zoho_inventory':
				$params = array(
					'code' => $code,
					'client_id' => $this->config['ServiceZI']['CLIENTID'],
					'client_secret' => $this->config['ServiceZI']['CLIENTSECRET'],
					'redirect_uri' => $this->config['ServiceZI']['REDIRECTURL'],
					'scope' => $this->config['ServiceZI']['SCOPE'],
					'grant_type' => 'authorization_code',
				);
				$url = $this->config['ServiceZI']['AUTHURL'];
				break;
			default:
				$params = array(
					'code' => $code,
					'client_id' => $this->config['ServiceZCRM']['CLIENTID'],
					'client_secret' => $this->config['ServiceZCRM']['CLIENTSECRET'],
					'redirect_uri' => $this->config['ServiceZCRM']['REDIRECTURL'],
					'scope' => $this->config['ServiceZCRM']['SCOPE'],
					'grant_type' => 'authorization_code',
				);
				$url = $this->config['ServiceZCRM']['AUTHURL'];
				break;
		}

		// Set up the request arguments
		$args = array(
			'headers' => $headers,
			'body' => $params,
			'method' => 'POST',
		);
		// Make the request using wp_remote_post()
		$response = wp_remote_post( $url, $args );

		// Check if the request was successful
		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
			// If successful, get the body of the response
			$body = wp_remote_retrieve_body( $response );

			// Decode JSON response
			return json_decode( $body, true );
		} else {
			// If there was an error, handle it
			$error_message = is_wp_error( $response ) ? $response->get_error_message() : 'Unknown error.';
			return 'Error: ' . $error_message;
		}
	}


	//get refresh token in ServiceZI
	public function get_zoho_refresh_token( $refresh_token, $app_name ) {
		$headers = array( 'Content-Type: application/x-www-form-urlencoded' );

		$client_id = 'zoho_inventory' === $app_name ? $this->config['ServiceZI']['CLIENTID'] : $this->config['ServiceZCRM']['CLIENTID'];
		$client_sec = 'zoho_inventory' === $app_name ? $this->config['ServiceZI']['CLIENTSECRET'] : $this->config['ServiceZCRM']['CLIENTSECRET'];
		$params = array(
			'refresh_token' => $refresh_token,
			'grant_type' => 'refresh_token',
			'client_id' => $client_id,
			'client_secret' => $client_sec,
		);
		$url = 'zoho_inventory' === $app_name ? $this->config['ServiceZI']['AUTHURL'] : $this->config['ServiceZCRM']['AUTHURL'];
		// Set up the request arguments
		$args = array(
			'headers' => $headers,
			'body' => $params,
			'method' => 'POST',
		);
		// Make the request using wp_remote_post()
		$response = wp_remote_post( $url, $args );
		// Check if the request was successful
		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
			// If successful, get the body of the response
			$body = wp_remote_retrieve_body( $response );
			// Decode JSON response
			return json_decode( $body, true );
		} else {
			// If there was an error, handle it
			$error_message = is_wp_error( $response ) ? $response->get_error_message() : 'Unknown error.';
			echo 'Error: ' . esc_html( $error_message );
			// echo the exception
		}
	}
}

$handlefunction = new Classfunctions();
