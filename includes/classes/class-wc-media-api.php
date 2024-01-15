<?php

class WooCommerce_Media_API_By_commercebird
{

	public function __construct()
	{
		add_action('rest_api_init', array($this, 'register_routes'), 15);
	}

	public function register_routes()
	{
		global $wp_version;
		if (version_compare($wp_version, 6.0, '<')) {
			return;
		}
		$api_classes = array(
			'WC_REST_WooCommerce_Media_API_By_commercebird_Controller',
			'WC_REST_WooCommerce_Metadata_API_By_commercebird_Controller',
			'WC_REST_List_Items_API_By_commercebird_Controller',
		);
		foreach ($api_classes as $api_class) {
			$controller = new $api_class();
			$controller->register_routes();
		}
	}
}

