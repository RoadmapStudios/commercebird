<?php

/**
 * All Multi Currency related functions.
 *
 * @package  WooZo Inventory
 * @category Zoho Integration
 * @author   Roadmap Studios
 * @link     https://commercebird.com
 */
class MulticurrencyClass {

	private $config;
	public function __construct() {

		$config = array(

			'MulticurrencyZI' => array(
				'OID'    => get_option( 'zoho_inventory_oid' ),
				'APIURL' => get_option( 'zoho_inventory_url' ),

			),

		);

		$this->config = $config;
	}

	public function zoho_currency_data( $user_currency, $userid ) {

		$zoho_inventory_oid = $this->config['MulticurrencyZI']['OID'];
		$zoho_inventory_url = $this->config['MulticurrencyZI']['APIURL'];

		//execute curl

		$url = $zoho_inventory_url . 'api/v1/settings/currencies?organization_id=' . $zoho_inventory_oid;

		$execute_curl_call_handle = new ExecutecallClass();
		$response                 = $execute_curl_call_handle->ExecuteCurlCallGet( $url );

		$code    = $response->code;
		$message = $response->message;

		if ( 0 == $code || '0' == $code ) {

			foreach ( $response->currencies as $key => $value ) {
				if ( $value->currency_code == $user_currency ) {
					update_user_meta( $userid, 'zi_currency_id', $value->currency_id );
					update_user_meta( $userid, 'zi_currency_code', $value->currency_code );
				}
			}
		}

		$currency_id = intval( get_user_meta( $userid, 'zi_currency_id', true ) );
		return $currency_id;
	}
}
