<?php

/**
 * Extending the WC API with our custom endpoints.
 *
 * @author Fawad Tiemoerie <info@roadmapstudios.com>
 * @since 2.0.0
 */
class CommerceBird_WC_API {


	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ), 15 );
	}

	public function register_routes() {
		global $wp_version;
		if ( version_compare( $wp_version, 6.0, '<' ) ) {
			return;
		}
		$api_classes = array(
			'WC_REST_CommerceBird_Media_API_Controller',
			'WC_REST_CommerceBird_Metadata_API_Controller',
			'WC_REST_List_Items_API_CommerceBird_Controller',
			'WC_REST_CommerceBird_Product_Brands_API_Controller',
		);
		foreach ( $api_classes as $api_class ) {
			$controller = new $api_class();
			$controller->register_routes();
		}
	}
}
