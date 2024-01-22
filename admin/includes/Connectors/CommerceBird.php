<?php

namespace RMS\Admin\Connectors;

use RMS\Admin\Actions\ExactOnline;
use WP_Error;

defined( 'RMS_PLUGIN_NAME' ) || exit;

final class CommerceBird {
	const COST_CENTERS = 'customs/exact/cost-centers';
	const COST_UNITS = 'customs/exact/cost-units';
	const ITEM = 'customs/exact/item';
	const CUSTOMER = 'customs/exact/customer';
	const API = "https://api.commercebird.com";
	const WEBAPP_ORDERS = 'webapp/orders/synced-orders';

	public function cost_centers() {
		return $this->request( self::COST_CENTERS );
	}

	/**
	 * Collect customer or account id
	 *
	 * @param array $customer array ( customer_email, company_name )
	 *
	 * @return array|WP_Error array ( account_id, company_id )
	 * @throws WP_Error Invalid customer if empty
	 */
	public function customer( array $customer ) {
		if ( empty( $customer ) ) {
			return new WP_Error( 'invalid_customer', __( 'Invalid customer', 'commercebird' ) );
		}
		$response = $this->request(
			self::CUSTOMER,
			'POST',
			$customer
		);

		return $response['data'] ?? $response;
	}

	/**
	 * Get item ID by product ID
	 *
	 * @param int $id of the item
	 *
	 * @return array|WP_Error The ID of the item or an error object.
	 */
	public function item_id( int $id ) {
		if ( empty( $id ) ) {
			return new WP_Error( 'invalid_id', __( 'Invalid ID', 'commercebird' ) );
		}

		$response = $this->request(
			self::ITEM,
			'POST',
			array(
				'id' => $id
			)
		);

		return $response['data'] ?? $response;

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
		foreach ( $response as $item ) {
			update_post_meta( $item['wooId'], 'eo_order_id', $item['zohoId'] );
		}

		return $response['message'];
	}

	/**
	 * Generate request URL
	 */
	private function request( string $endpoint, string $method = 'GET', array $data = array() ) {
		$token    = ExactOnline::instance()->get_token();
		$url      = sprintf( "%s/%s?token=%s", self::API, $endpoint, $token );
		$site_url = site_url() === 'http://commercebird.test' ? 'https://dev.wooventory.com' : site_url();
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
			return false;
		}

		return json_decode( $response['body'], true );
	}
}