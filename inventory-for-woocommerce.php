<?php
/**
 * Plugin Name: Inventory for WooCommerce
 * Description: Allows you to upload Product Images via https://app.wooventory.com.
 * Author: wooventory
 * Author URI: https://wooventory.com
 * Version: 1.0.0
 * License: GPL2 or later
 */
if (!defined('ABSPATH')) {
    exit;
}

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

defined('ALLOW_UNFILTERED_UPLOADS') or define('ALLOW_UNFILTERED_UPLOADS', true);

class WooCommerce_Media_API_By_wooventory
{

    public function __construct()
    {
        add_action('rest_api_init', array($this, 'register_routes'), 15);
    }

    public function register_routes()
    {
        global $wp_version;
        if (version_compare($wp_version, 4.4, '<')) {
            return;
        }

        require_once __DIR__ . 'includes/class-wooventory-api-controller.php';
        require_once __DIR__ . 'includes/class-wooventory-metadata-controller.php';
        require_once __DIR__ . 'includes/class-wooventory-list-items-api-controller.php';
        $api_classes = array(
            'WC_REST_WooCommerce_Media_API_By_wooventory_Controller',
            'WC_REST_WooCommerce_Metadata_API_By_wooventory_Controller',
            'WC_REST_List_Items_API_By_wooventory_Controller',
        );
        foreach ($api_classes as $api_class) {
            $controller = new $api_class();
            $controller->register_routes();
        }
    }
}

new WooCommerce_Media_API_By_wooventory();

function filter_wooventory_modify_after_query($request)
{
    $request['date_query'][0]['column'] = 'post_modified';
    return $request;
}

add_filter("woocommerce_rest_orders_prepare_object_query", 'filter_wooventory_modify_after_query');
add_filter("woocommerce_rest_product_object_query", 'filter_wooventory_modify_after_query');

function action_wooventory_update_profile_modified($user_id)
{
    update_user_meta($user_id, 'wooventory_profile_updated', current_time('mysql'));
}
add_action('profile_update', 'action_wooventory_update_profile_modified');

function action_wooventory_update_variation_stock_quantity($variation)
{
    $product = wc_get_product($variation->get_parent_id());
    update_post_meta($product->get_id(), 'wooventory_product_last_set_stock', current_time('mysql'));
}
add_action('woocommerce_variation_set_stock', 'action_wooventory_update_variation_stock_quantity');

function action_wooventory_update_stock_quantity($product)
{
    update_post_meta($product->get_id(), 'wooventory_product_last_set_stock', current_time('mysql'));
}
add_action('woocommerce_product_set_stock', 'action_wooventory_update_stock_quantity');

// allow cors
function wooventory_add_cors_http_header()
{
    header("Access-Control-Allow-Origin: *");
}
add_action('init', 'wooventory_add_cors_http_header');

function wooventory_cors($allowed_origins)
{
    $allowed_origins[] = 'http://localhost:8100';
    $allowed_origins[] = 'https://app.wooventory.com';
    return $allowed_origins;
}
add_filter('allowed_http_origins', 'wooventory_cors', 10, 1);

// The code for creating Product Cost Price in WooCommerce as Meta
add_action('woocommerce_product_options_general_product_data', 'wooventory_cost_price');
add_action('woocommerce_process_product_meta', 'wooventory_cost_price_save');

function wooventory_cost_price()
{
    echo '<div class="product_custom_field">';
    // Custom Product Text Field
    woocommerce_wp_text_input(
        array(
            'id' => 'cost_price',
            'placeholder' => 'Cost Price',
            'label' => __('Cost Price', 'woocommerce'),
            'desc_tip' => 'true',
        )
    );
    echo '</div>';
}

function wooventory_cost_price_save($post_id)
{
    // Custom Product Text Field
    $woocommerce_custom_product_text_field = $_POST['cost_price'];
    if (!empty($woocommerce_custom_product_text_field)) {
        update_post_meta($post_id, 'cost_price', esc_attr($woocommerce_custom_product_text_field));
    }

}

// Release cost price in the Woo API
add_action('rest_api_init', 'wooventory_register_post_meta');
function wooventory_register_post_meta()
{
    register_rest_field('product', // any post type registered with API
        'cost_price', // this needs to match meta key
        array(
            'get_callback' => 'wooventory_get_meta',
            'update_callback' => 'wooventory_update_meta',
            'schema' => null,
        )
    );
}
function wooventory_get_meta($object, $field_name, $request)
{
    return get_post_meta($object['id'], $field_name, true);
}
function wooventory_update_meta($value, $object, $field_name)
{
    return update_post_meta($object->id, $field_name, $value);
}

// function wooventory_client_activate( $slash = '' ) {
//     $config = file_get_contents (ABSPATH . "wp-config.php");
//     $config = preg_replace ("/^([\r\n\t ]*)(\<\?)(php)?/i", "<?php define('ALLOW_UNFILTERED_UPLOADS', true);", $config);
//     file_put_contents (ABSPATH . $slash . "wp-config.php", $config);
// }

// if ( file_exists (ABSPATH . "wp-config.php") && is_writable (ABSPATH . "wp-config.php") ){
//     wooventory_client_activate();
// }
// else if (file_exists (dirname (ABSPATH) . "/wp-config.php") && is_writable (dirname (ABSPATH) . "/wp-config.php")){
//     wooventory_client_activate('/');
// }
// else {
//     add_warning('Error adding');
// }
// register_activation_hook( __FILE__, 'wooventory_client_activate' );
