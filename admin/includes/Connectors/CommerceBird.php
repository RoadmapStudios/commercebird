<?php

namespace RMS\Admin\Connectors;

use RMS\Admin\Actions\Ajax\ExactOnlineAjax;
use RMS\Admin\Actions\Ajax\ZohoCRMAjax;
use RMS\Admin\Traits\LogWriter;
use WP_Error;

defined( 'RMS_PLUGIN_NAME' ) || exit;

final class CommerceBird {
	use LogWriter;

	const COST_CENTERS = 'customs/exact/cost-centers';
	const COST_UNITS = 'customs/exact/cost-units';
	const GL_ACCOUNTS = 'customs/exact/gl-accounts';
	const ITEM = 'customs/exact/bulk-items';
	const CUSTOMER = 'customs/exact/bulk-customers';
	const ORDER = 'customs/exact/bulk-orders';
	const PAYMENT_STATUS = 'customs/exact/invoice-payment-status';
	const WEBHOOKS = 'customs/exact/webhooks';
	const API = 'https://api.commercebird.com';
	const ZCRMFIELDS = 'customs/zoho/fields';

	public function cost_centers() {
		return $this->request( self::COST_CENTERS );
	}

	public function gl_accounts() {
		return $this->request( self::GL_ACCOUNTS );
	}

	/**
	 * Suscribe to Exact Online Webhooks. Pass the webhook URL to Exact Online and Topic
	 *
	 * @param array $data array ( callback_url, topic )
	 * @return array|WP_Error array ( webhook_id, topic )
	 */
	public function subscribe_exact_webhooks( array $data ) {
		$response = $this->request( self::WEBHOOKS, 'POST', $data );
		return $response['code'] === 200 ? $response['data'] : $response['message'];
	}

	/**
	 * Get all Zoho CRM Custom fields
	 *
	 * @param
	 *
	 * @return array|WP_Error array ( account_id, company_id )
	 * @throws WP_Error Invalid customer if empty
	 */
	public function get_zcrm_fields( $module ) {
		$response = $this->request( self::ZCRMFIELDS, 'GET', array( 'module' => $module ) );
		return $response['code'] === 200 ? $response['data'] : $response['message'];
	}

	/**
	 * Collect customer or account id
	 *
	 * @param array $customer array ( customer_email, company_name )
	 *
	 * @return array|WP_Error array ( account_id, company_id )
	 * @throws WP_Error Invalid customer if empty
	 */
	public function customer() {
		$response = $this->request( self::CUSTOMER );
		return $response['code'] === 200 ? $response['data'] : $response['message'];
	}
	/**
	 * Collect customer or account id
	 *
	 * @param array $customer array ( customer_email, company_name )
	 *
	 * @return array|WP_Error array ( account_id, company_id )
	 * @throws WP_Error Invalid customer if empty
	 */
	public function order( array $range ) {

		$response = $this->request(
			self::ORDER,
			'GET',
			array(),
			$range
		);
		return $response['code'] === 200 ? $response['data'] : $response['message'];
	}

	/**
	 * Get item ID by product ID
	 *
	 * @param int $id of the item
	 *
	 * @return array|WP_Error The ID of the item or an error object.
	 */
	public function products() {

		$response = $this->request( self::ITEM );

		return $response['code'] === 200 ? $response['data'] : $response['message'];
	}

	public function cost_units() {
		return $this->request( self::COST_UNITS );
	}

	/**
	 * Get payment status
	 *
	 * @param array
	 *
	 * @return array|WP_Error array ( payment_status )
	 */
	public function payment_status( array $data ) {
		$response = $this->request( self::PAYMENT_STATUS, 'POST', $data, array() );
		return $response['code'] === 200 ? $response['data'] : $response['message'];
	}

	/**
	 * Generate request URL
	 */
	private function request( string $endpoint, string $method = 'GET', array $data = array(), array $params = array() ) {
		$token = ! empty( ExactOnlineAjax::instance()->get_token() ) ? ExactOnlineAjax::instance()->get_token() : ZohoCRMAjax::instance()->get_token();
		$url = sprintf( '%s/%s?token=%s', self::API, $endpoint, $token );
		if ( ! empty( $params ) ) {
			$url .= '&' . http_build_query( $params );
		}

		// if current site contains localhost, use https://dev.commercebird.com
		$current_site_url = isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : '';
		$site_url = str_contains( $current_site_url, 'localhost' ) ? 'https://dev.commercebird.com' : site_url();

		if ( 'POST' === $method ) {
			$response = wp_remote_post(
				$url,
				array(
					'headers' => array(
						'Accept' => 'application/json',
						'Content-Type' => 'application/json',
						'zohowooagent' => $site_url,
					),
					'timeout' => 60,
					'sslverify' => false,
					'body' => wp_json_encode( $data ),
				)
			);
		} else {
			$response = wp_remote_get(
				$url,
				array(
					'headers' => array(
						'Accept' => 'application/json',
						'zohowooagent' => $site_url,
						'x-woozo-module' => $data['module'] ?? '',
					),
					'timeout' => 60,
					'sslverify' => false,
				)
			);
		}

		if ( is_wp_error( $response ) ) {
			$this->write_log( $response->get_error_message(), 'commercebird-connector' );
			return;
		}
		$response = wp_remote_retrieve_body( $response );
		return json_decode( $response, true );
	}
}
