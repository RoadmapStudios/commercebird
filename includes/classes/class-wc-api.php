<?php

namespace CommerceBird;

defined( 'RMS_PLUGIN_NAME' ) || exit;

/**
 * Extending the WC API with our custom endpoints.
 *
 * @author Fawad Tiemoerie <info@roadmapstudios.com>
 * @since 2.0.0
 */
class CommerceBird_WC_API {
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
		// register cost_price as meta field for products.
		register_rest_field(
			'product',
			'cost_price',
			array(
				'get_callback' => array( $this, 'cmbird_get_product_field' ),
				'update_callback' => array( $this, 'cmbird_update_product_field' ),
				'schema' => null,
			)
		);
	}

	/**
	 * Get meta field value.
	 *
	 * @param mixed $object
	 * @param mixed $object
	 * @param mixed $field_name
	 * @param mixed $request
	 * @return mixed
	 */
	public function cmbird_get_product_field( $object, $field_name, $request ) {
		return get_post_meta( $object['id'], $field_name, true );
	}
	/**
	 * Update meta field value.
	 * @param mixed $value
	 * @param mixed $object
	 * @param mixed $field_name
	 * @return bool|int
	 */
	public function cmbird_update_product_field( $value, $object, $field_name ) {
		return update_post_meta( $object->id, $field_name, $value );
	}
}
