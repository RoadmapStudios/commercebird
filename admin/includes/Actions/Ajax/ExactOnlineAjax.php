<?php

namespace RMS\Admin\Actions\Ajax;

use RMS\Admin\Actions\Sync\ExactOnlineSync;
use RMS\Admin\Connectors\CommerceBird;
use RMS\Admin\Traits\AjaxRequest;
use RMS\Admin\Traits\Singleton;

defined( 'RMS_PLUGIN_NAME' ) || exit;

final class ExactOnlineAjax {
	use Singleton;
	use AjaxRequest;

	private const FORMS   = array(
		'connect' => array(
			'token',
		),
		'product' => array(
			'importProducts',
		),
	);
	private const ACTIONS = array(
		'save_exact_online_connect'     => 'connect_save',
		'get_exact_online_connect'      => 'connect_load',
		'save_exact_online_cost_center' => 'cost_center_save',
		'save_exact_online_cost_unit'   => 'cost_unit_save',
		'import_exact_online_product'   => 'product_import',
		'map_exact_online_product'      => 'product_map',
		'map_exact_online_customer'     => 'customer_map',
	);
	private const OPTIONS = array(
		'connect'     => array(
			'token' => 'commercebird-exact-online-token',
		),
		'cost_center' => 'commercebird-exact-online-cost-center',
		'cost_unit'   => 'commercebird-exact-online-cost-unit',
	);



	public function product_map() {
		$this->verify( self::FORMS['product'] );
		$products = ( new CommerceBird() )->products();
		$chunked  = array_chunk( $products['items'], 20 );
		foreach ( $chunked as $chunked_products ) {
			$id = as_schedule_single_action(
				time() + 60,
				'sync_eo_products',
				array(
					wp_json_encode( $chunked_products ),
					(bool) $this->data['importProducts'],
				)
			);
			if ( empty( $id ) ) {
				break;
			}
		}

		$this->response['message'] = __( 'Items are being mapped in background. You can visit other tabs :).', 'commercebird' );
		$this->serve();
	}

	public function customer_map() {
		$this->verify();
		$this->response['message'] = __( 'Items are being mapped in background. You can visit other tabs :).', 'commercebird' );
		$this->serve();
	}

	public function cost_center_get() {
		return get_option( self::OPTIONS['cost_center'], array() );
	}

	public function cost_unit_get() {
		return get_option( self::OPTIONS['cost_unit'], array() );
	}

	public function get_token() {
		return get_option( self::OPTIONS['connect']['token'], '' );
	}

	public function cost_center_save() {
		$this->verify();
		$response = ( new CommerceBird() )->cost_centers();
		if ( empty( $response ) ) {
			$this->errors['message'] = __( 'Cost centers not found', 'commercebird' );
		} else {
			$centers = array_map(
				function ( $item ) {
					return "{$item['Code']}-{$item['Description']}";
				},
				$response['data']
			);
			update_option( self::OPTIONS['cost_center'], $centers );
			$this->response['message'] = __( 'Cost centers saved', 'commercebird' );
		}
		$this->serve();
	}

	public function cost_unit_save() {
		$this->verify();
		$response = ( new CommerceBird() )->cost_units();
		if ( empty( $response ) ) {
			$this->errors['message'] = __( 'Cost units not found', 'commercebird' );
		} else {
			$units = array_map(
				function ( $item ) {
					return "{$item['Code']}-{$item['Description']}";
				},
				$response['data']
			);
			update_option( self::OPTIONS['cost_unit'], $units );
			$this->response['message'] = __( 'Cost units saved', 'commercebird' );
		}
		$this->serve();
	}

	public function __construct() {
		$this->load_actions();
	}

	public function connect_save() {
		$this->verify( self::FORMS['connect'] );
		if ( isset( $this->data['token'] ) && ! empty( $this->data['token'] ) ) {
			update_option( self::OPTIONS['connect']['token'], $this->data['token'] );
			$this->response['message'] = __( 'Saved', 'commercebird' );
			$this->response['data']    = $this->data;
		} else {
			$this->errors['message'] = __( 'Token is required', 'commercebird' );
		}

		$this->serve();
	}

	public function connect_load() {
		$this->verify();
		$this->response['token'] = get_option( self::OPTIONS['connect']['token'], '' );
		$this->serve();
	}
}
