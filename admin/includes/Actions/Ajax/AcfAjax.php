<?php

namespace RMS\Admin\Actions\Ajax;

use RMS\Admin\Traits\AjaxRequest;
use RMS\Admin\Traits\OptionStatus;
use RMS\Admin\Traits\Singleton;
use RMS\Admin\Traits\LogWriter;


defined( 'RMS_PLUGIN_NAME' ) || exit;

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



	private const FORMS = array(

	);
	private const ACTIONS = array(

		'get_acf_fields' => 'get_acf_fields',
	);
	private const OPTIONS = array(

	);

	public function __construct() {
		$this->load_actions();
		add_filter( 'acf/load_key', array( $this->get_acf_fields() ), 10, 3 );
	}


	public function get_acf_fields(): void {
		$post_type = isset( $_GET['module'] ) ? $_GET['module'] : '';
		error_log( ( 'post_type' . $post_type ) );
		// Get all field groups associated with WooCommerce products
		$groups = acf_get_field_groups( array( 'post_type' => $post_type ) );
		error_log( 'Groups: ' . print_r( $groups, true ) );

		// Check if there are any field groups
		if ( $groups ) {
			// Loop through each group
			foreach ( $groups as $group ) {
				// Get the fields for the current group
				$fields = acf_get_fields( $group['key'] );
			}
			$this->response = array( 'fields' => $fields );
			$this->serve();
		}
	}
}