<?php

namespace CommerceBird\Admin\Actions\Ajax;

use CommerceBird\Admin\Traits\AjaxRequest;
use CommerceBird\Admin\Traits\OptionStatus;
use CommerceBird\Admin\Traits\Singleton;
use CommerceBird\Admin\Traits\LogWriter;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Initializes the Zoho CRM class.
 * @since 1.0.0
 * @return void
 */
final class AcfAjax {

	use Singleton;
	use AjaxRequest;
	use OptionStatus;
	use LogWriter;



	private const ACTIONS = array(

		'get_acf_fields' => 'get_acf_fields',
	);

	public function __construct() {
		$this->load_actions();
	}


	public function get_acf_fields(): void {
		$post_type_value = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : ''; // phpcs:ignore  WordPress.Security.NonceVerification.Recommended
		if ( 'users' === $post_type_value ) {
			$post_type_value = 'all';
			$group_type = 'user_role';
		} else {
			$group_type = 'post_type';
		}
		// Get all field groups associated with WooCommerce products
		$groups = acf_get_field_groups( array( $group_type => $post_type_value ) );

		// Check if there are any field groups
		if ( $groups ) {
			// Loop through each group
			foreach ( $groups as $group ) {
				// Get the fields for the current group
				$fields = acf_get_fields( $group['key'] );
			}
			$this->response['fields'] = $fields;
			$this->serve();
		}
	}
}
