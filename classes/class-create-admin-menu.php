<?php
/**
 * This file will create admin menu page.
 */

class Wp_Create_Admin_Page {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'create_admin_menu' ] );
        add_action( 'rest_api_init', [ $this, 'wooventory_register_post_meta' ] );
        add_action( 'woocommerce_product_options_general_product_data', [ $this, 'wooventory_cost_price' ] ); 
	    add_action( 'woocommerce_process_product_meta', [ $this, 'wooventory_cost_price_save' ] );
        add_action( 'init', [ $this, 'wooventory_add_cors_http_header' ] );
    }

    public function create_admin_menu() {
        $capability = 'manage_options';
        $slug = 'wooventory-app';
        $icon = WR_URL . 'media/wooventory-icon.svg';

        add_menu_page(
            __( 'Wooventory', 'wooventory' ),
            __( 'Wooventory', 'wooventory' ),
            $capability,
            $slug,
            [ $this, 'menu_page_template' ],
            $icon,
            29
        );
    }

    public function menu_page_template() {
        echo '<div class="wrap"><div id="wp-admin-app"></div></div>';
    }

    public function wooventory_cost_price() {
		echo '<div class="product_custom_field">';
		// Custom Product Text Field
		woocommerce_wp_text_input(
			array(
				'id' => 'cost_price',
				'placeholder' => 'Cost Price',
				'label' => __('Cost Price', 'woocommerce'),
				'desc_tip' => 'true'
			)
		);
		echo '</div>';
    }

    public function wooventory_cost_price_save($post_id) {
        // Custom Product Text Field
		$woocommerce_custom_product_text_field = $_POST['cost_price'];
		if (!empty($woocommerce_custom_product_text_field))
			update_post_meta($post_id, 'cost_price', esc_attr($woocommerce_custom_product_text_field));
    }

    public function wooventory_register_post_meta() {
		register_rest_field( 'product', // any post type registered with API
			'cost_price', // this needs to match meta key
			array(
				'get_callback' => [$this, 'wooventory_get_meta'],
				'update_callback' => [$this, 'wooventory_update_meta'],
				'schema' => null,
			)
		);
	}
	public function wooventory_get_meta( $object, $field_name, $request ) {
		return get_post_meta( $object[ 'id' ], $field_name, true );
	}
	public function wooventory_update_meta( $value, $object, $field_name ) {
		return update_post_meta( $object->id, $field_name, $value );
	}

    public function wooventory_add_cors_http_header(){
        header("Access-Control-Allow-Origin: *");
    }

}
new Wp_Create_Admin_Page();