<?php

namespace RMS\Admin;

use RMS\Admin\Actions\Ajax\ExactOnlineAjax;
use RMS\Admin\Traits\Singleton;

if ( ! defined( 'RMS_PLUGIN_NAME' ) ) {
	exit;
}

final class Acf {
	use Singleton;

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_filter( 'acf/load_field/name=costunit', array( $this, 'cost_units' ), 20 );
		add_filter( 'acf/load_field/name=costcenter', array( $this, 'cost_centers' ), 20 );
		add_filter( 'acf/load_field/name=glaccount', array( $this, 'gl_accounts' ), 20 );
	}

	/**
	 * GL Account custom field customization.
	 */
	public function gl_accounts( $field ): array {
		$gl_accounts = ExactOnlineAjax::instance()->gl_account_get();
		return $this->extract_choice( $gl_accounts, $field );
	}

	/**
	 * Cost center custom field customization.
	 */
	public function cost_centers( $field ): array {
		$cost_centers = ExactOnlineAjax::instance()->cost_center_get();

		return $this->extract_choice( $cost_centers, $field );
	}

	/**
	 * Cost Units custom field customization.
	 */
	public function cost_units( $field ): array {
		$cost_units = ExactOnlineAjax::instance()->cost_unit_get();

		return $this->extract_choice( $cost_units, $field );
	}

	public function register_routes() {
		$exclude_type = [ 'acf-field-group', 'acf-field' ];
		$include_types = [ 'page' ];
		$post_types = array_diff( get_post_types( [ '_builtin' => false ] ), $exclude_type );
		$post_types = array_merge( $post_types, $include_types );

		array_walk(
			$post_types,
			function ( $post_type ) {
				register_rest_field(
					$post_type,
					'ACF',
					array(
						'get_callback' => function ( $object ) {
							return get_fields( $object['id'] );
						},
						'schema' => null,
					)
				);
			}
		);
	}

	/**
	 * @param array $data - ACF data
	 * @param array $field - ACF field
	 *
	 * @return array
	 */
	private function extract_choice( array $data, array $field ): array {
		if ( $data ) {
			$field['choices'] = array();
			foreach ( $data as $choice ) {
				$field['choices'][ $choice ] = $choice;
			}
		}

		return $field;
	}
}
