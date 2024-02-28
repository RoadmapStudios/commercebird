<?php

class Classfunctions {

	/**
	 * @var array|array[]
	 */
	private array $config;

	public function __construct() {
		$config = array(

			'ServiceZI' => array(
				'OID'          => get_option( 'zoho_inventory_oid' ),
				'CLIENTSECRET' => get_option( 'zoho_inventory_cs' ),
				'CLIENTID'     => get_option( 'zoho_inventory_cid' ),
				'REDIRECTURL'  => get_option( 'authorization_redirect_uri' ),
				'APIURL'       => get_option( 'zoho_inventory_url' ),
				'DOMAINNAME'   => get_option( 'zoho_inventory_domain' ),
				'SCOPE'        => 'ZohoInventory.FullAccess.all',
				//'STATE' => wp_create_nonce('redirect_url'),
				'AUTHURL'      => 'https://accounts.zoho.' . get_option( 'zoho_inventory_domain' ) . '/oauth/v2/token',

			),

		);

		return $this->config = $config;
	}

	function GetServiceZIAccessToken( $code ) {

		$headers = array( 'Content-Type: application/x-www-form-urlencoded' );
		$params  = array(
			'code'          => $code,
			'client_id'     => $this->config['ServiceZI']['CLIENTID'],
			'client_secret' => $this->config['ServiceZI']['CLIENTSECRET'],
			'redirect_uri'  => $this->config['ServiceZI']['REDIRECTURL'],
			'scope'         => $this->config['ServiceZI']['SCOPE'],
			'grant_type'    => 'authorization_code',
		);
		// Set up the request arguments
		$args = array(
			'headers' => $headers,
			'body'    => $params,
			'method'  => 'POST',
		);
		$url  = $this->config['ServiceZI']['AUTHURL'];
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
	function GetServiceZIRefreshToken( $refresh_token ) {
		$headers = array( 'Content-Type: application/x-www-form-urlencoded' );
		$params  = array(
			'refresh_token' => $refresh_token,
			'grant_type'    => 'refresh_token',
			'client_id'     => $this->config['ServiceZI']['CLIENTID'],
			'client_secret' => $this->config['ServiceZI']['CLIENTSECRET'],
		);
		// Set up the request arguments
		$args = array(
			'headers' => $headers,
			'body'    => $params,
			'method'  => 'POST',
		);
		$url  = $this->config['ServiceZI']['AUTHURL'];
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
}

$handlefunction = new Classfunctions();
