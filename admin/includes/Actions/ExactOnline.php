<?php

namespace RMS\Admin\Actions;

use RMS\Admin\Connectors\CommerceBird;
use RMS\Admin\Traits\AjaxRequest;
use RMS\Admin\Traits\Singleton;

defined( 'RMS_PLUGIN_NAME' ) || exit;

final class ExactOnline {
	use Singleton;
	use AjaxRequest;

	private const FORMS = array(
		'connect' => array(
			'token',
		)
	);
	private const ACTIONS = array(
		'save_exact_online_connect'     => 'connect_save',
		'get_exact_online_connect'      => 'connect_load',
		'save_exact_online_cost_center' => 'cost_center_save',
		'save_exact_online_cost_unit'   => 'cost_unit_save',
	);
	private const OPTIONS = [
		'connect'     => [
			'token' => 'commercebird-exact-online-token',
		],
		'cost_center' => 'commercebird-exact-online-cost-center',
		'cost_unit'   => 'commercebird-exact-online-cost-unit',
	];

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
			$centers = array_map( function ( $item ) {
				return "{$item['Code']}-{$item['Description']}";
			}, $response );
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
			$units = array_map( function ( $item ) {
				return "{$item['Code']}-{$item['Description']}";
			}, $response );
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