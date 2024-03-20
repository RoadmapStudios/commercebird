<?php

namespace RMS\Admin\Connectors;

use RMS\Admin\Actions\Ajax\ExactOnlineAjax;
use RMS\Admin\Traits\LogWriter;
use WP_Error;

defined( 'RMS_PLUGIN_NAME' ) || exit;

final class CommerceBird {
	use LogWriter;

	const COST_CENTERS  = 'customs/exact/cost-centers';
	const COST_UNITS    = 'customs/exact/cost-units';
	const ITEM          = 'customs/exact/bulk-items';
	const CUSTOMER      = 'customs/exact/bulk-customers';
	const ORDER         = 'customs/exact/bulk-orders';
	const API           = 'https://api.commercebird.com';
	const WEBAPP_ORDERS = 'webapp/orders/synced-orders';

	const ZCRMFIELDS = 'customs/zoho/fields';

	public function cost_centers() {
		return $this->request( self::COST_CENTERS );
	}

	/**
	 * Get all Zoho CRM Custom fields
	 *
	 * @param
	 *
	 * @return array|WP_Error array ( account_id, company_id )
	 * @throws WP_Error Invalid customer if empty
	 */
	public function zcrm_fields() {

		$response = $this->request( self::ZCRMFIELDS );

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

	public function map_orders() {
		global $pagenow, $typenow;
		if ( 'shop_order' !== $typenow || 'edit.php' !== $pagenow || ! isset( $_GET['get_zcrm_statuses'] ) || $_GET['get_zcrm_statuses'] !== 'yes' ) {
			return '';
		}
		$response = $this->request( self::WEBAPP_ORDERS );
		if ( $response['code'] !== 200 ) {
			return $response['message'];
		}
		foreach ( $response as $item ) {
			update_post_meta( $item['wooId'], 'eo_order_id', $item['zohoId'] );
		}

		return $response['message'];
	}

	/**
	 * Generate request URL
	 */
	private function request( string $endpoint, string $method = 'GET', array $data = array(), array $params = array() ) {
		$token = ExactOnlineAjax::instance()->get_token();
		$url   = sprintf( '%s/%s?token=%s', self::API, $endpoint, $token );
		if ( ! empty( $params ) ) {
			$url .= '&' . http_build_query( $params );
		}

		$site_url = site_url() === 'http://commercebird.test' ? 'https://dev.commercebird.com' : site_url();

		if ( 'POST' === $method ) {
			$response = wp_remote_post(
				$url,
				array(
					'headers'   => array(
						'Accept'       => 'application/json',
						'Content-Type' => 'application/json',
						'zohowooagent' => $site_url,
					),
					'timeout'   => 60,
					'sslverify' => false,
					'body'      => wp_json_encode( $data ),
				)
			);
		} else {
			$response = wp_remote_get(
				$url,
				array(
					'headers'   => array(
						'Accept'       => 'application/json',
						'zohowooagent' => $site_url,
					),
					'timeout'   => 60,
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
