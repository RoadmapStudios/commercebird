<?php

/**
 * Plugin Name: Wooventory
 * Plugin URI:  https://wooventory.com
 * Description: This plugin helps you get the most of Wooventory by
 * allowing you to manage product images, integrations and more.
 * Version: 2.0.6
 * Author: Wooventory
 * Author URI:  https://wooventory.com
 * Requires PHP: 7.4
 * Text Domain: rmsZI
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @category Fulfillment
 * @package  Wooventory
 * @author   Fawad Tiemoerie <info@roadmapstudios.com>
 * @license  GNU General Public License v3.0
 * @link     https://wooventory.com
 *
 * WC requires at least: 8.0.0
 * WC tested up to: 8.4.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '' ) or die( 'No script kiddies please!' );
}
if ( ! defined( 'RMS_PLUGIN_NAME' ) ) {
	define( 'RMS_PLUGIN_NAME', 'Wooventory' );
}
if ( ! defined( 'RMS_VERSION' ) ) {
	define( 'RMS_VERSION', '2.0.6' );
}
if ( ! defined( 'RMS_DIR_PATH' ) ) {
	define( 'RMS_DIR_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'RMS_DIR_URL' ) ) {
	define( 'RMS_DIR_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'RMS_BASENAME' ) ) {
	define( 'RMS_BASENAME', plugin_basename( __FILE__ ) );
}
if ( ! defined( 'RMS_MENU_SLUG' ) ) {
	define( 'RMS_MENU_SLUG', 'wooventory-app' );
}
if ( ! defined( 'RMS_DOCUMENTATION_URL' ) ) {
	define( 'RMS_DOCUMENTATION_URL', 'https://support.wooventory.com/portal/en/kb/zoho-inventory-woocommerce' );
}
if ( ! defined( 'RMS_PLUGIN_URL' ) ) {
	define( 'RMS_PLUGIN_URL', 'https://wooventory.com/product/woocommerce-zoho-inventory/' );
}

require_once RMS_DIR_PATH . 'includes/woo-functions.php';
require_once RMS_DIR_PATH . 'includes/sync/order-backend.php';
require_once RMS_DIR_PATH . 'includes/sync/order-frontend.php';
require_once RMS_DIR_PATH . 'data-sync.php';
require_once RMS_DIR_PATH . 'includes/wc-am-client.php';
require_once RMS_DIR_PATH . 'includes/tgm-plugin-activation.php';
require_once RMS_DIR_PATH . 'libraries/action-scheduler/action-scheduler.php';

require __DIR__ . '/vendor/autoload.php';

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use RMS\Admin\Ajax;
use RMS\Admin\Cors;
use RMS\Admin\Template;
use RMS\API\ProductWebhook;
use RMS\API\ShippingWebhook;
use RMS\API\Zoho;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

//  remove on production
$whoops = new Run();
$whoops->pushHandler( new PrettyPageHandler() );
$whoops->register();
// remove on production

/* Check the minimum PHP version on activation hook */
function woozo_check_plugin_requirements() {
	$php_min_version     = '7.4';
	$php_current_version = phpversion();

	if ( version_compare( $php_min_version, $php_current_version, '>' ) ) {
		deactivate_plugins( 'wooventory/plugin.php' );

		$error_message = sprintf(
			'Your server is running PHP version %s but the Wooventory plugin requires at least PHP %s. Please update your PHP version.',
			$php_current_version,
			$php_min_version,
		);

		wp_die(
			$error_message,
			'Plugin Activation Error',
			array(
				'response'  => 200,
				'back_link' => true,
			)
		);
	}
}

add_action( 'admin_init', 'woozo_check_plugin_requirements' );

// Activate plugin.
register_activation_hook( __FILE__, array( 'wooventory', 'activate' ) );

// Init hooks.
Wooventory::initHooks();

/**
 * Create table if it is not available in plugin
 */
/*
if (!function_exists('zi_create_order_log_table')) {

function zi_create_order_log_table()
{
global $wpdb;
$charset_collate = $wpdb->get_charset_collate();
$zi_order_log_table = "{$wpdb->prefix}zoho_ordersale_error";
$zi_create_sql = "CREATE TABLE $zi_order_log_table ( ID bigint(20) PRIMARY KEY auto_increment, user_id bigint(20) NOT NULL, order_id bigint(20) NOT NULL, error_message TEXT NOT NULL, order_timestamp VARCHAR(20) NOT NULL, status int(10) NOT NULL )$charset_collate;";

require_once ABSPATH . 'wp-admin/includes/upgrade.php';
dbDelta($zi_create_sql);
}
}
register_activation_hook(__FILE__, 'zi_create_order_log_table');
 */

// Register Cronjob Hook when activating the plugin
register_activation_hook( __FILE__, 'zi_custom_zoho_cron_activate' );

function zi_custom_zoho_cron_activate() {
	$interval = get_option( 'zi_cron_interval', 'daily' );
	if ( ! wp_next_scheduled( 'zi_execute_import_sync' ) ) {
		wp_schedule_event( time(), $interval, 'zi_execute_import_sync' );
	}
}

// Unschedule event upon plugin deactivation
register_deactivation_hook( __FILE__, 'zi_zoho_cron_deactivate' );
function zi_zoho_cron_deactivate() {
	wp_clear_scheduled_hook( 'zi_execute_import_sync' );
}


/**
 * Declaring compatibility for WooCommerce HPOS
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( FeaturesUtil::class ) ) {
			FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

// register_uninstall_hook( __FILE__, 'rms_cron_unsubscribe' );
// /**
//  * Unsunscribing cron at uninstall of hook.
//  */
// if ( ! function_exists( 'rms_cron_unsubscribe' ) ) {
// 	function rms_cron_unsubscribe() {
// 		// wp_clear_scheduled_hook( 'rms_cron_schedule_hook' );
// 		$post_meta_keys = array(
// 			'zi_item_id',
// 			'zi_purchase_account_id',
// 			'zi_account_id',
// 			'zi_account_name',
// 			'zi_inventory_account_id',
// 			'zi_salesorder_id',
// 			'zi_category_id',
// 		);
// 		$user_meta_keys = array(
// 			'zi_contact_id',
// 			'zi_primary_contact_id',
// 			'zi_created_time',
// 			'zi_last_modified_time',
// 			'zi_billing_address_id',
// 			'zi_shipping_address_id',
// 			'zi_contact_persons_id',
// 			'zi_currency_id',
// 			'zi_currency_code',
// 		);
// 		$zi_option_keys = array(
// 			'zi_cron_isactive',
// 			'zoho_inventory_cron_class',
// 			'zoho_sync_status',
// 			'zoho_item_category',
// 			'zoho_stock_sync_status',
// 			'zoho_item_name_sync_status',
// 			'zoho_enable_auto_no_status',
// 			'zoho_product_sync_status',
// 			'zoho_disable_itemimage_sync_status',
// 			'zoho_disable_itemprice_sync_status',
// 			'zoho_disable_itemname_sync_status',
// 			'zoho_disable_itemdescription_sync_status',
// 			'zoho_disable_groupitem_sync_status',
// 			'zoho_enable_attributes_sync_status',
// 			'zoho_enable_accounting_stock_status',
// 			'zoho_enable_order_status',
// 			'wootozoho_custom_fields',
// 			'zoho_pricelist_id',
// 			'zoho_warehouse_id',
// 			'zoho_inventory_auth_code',
// 			'zoho_inventory_access_token',
// 			'zoho_inventory_refresh_token',
// 			'zoho_inventory_timestamp',
// 			'rms_ck',
// 			'rms_cs',
// 		);

// 		foreach ( $zi_option_keys as $zi_option ) {
// 			delete_option( $zi_option );
// 		}

// 		foreach ( $post_meta_keys as $post_key ) {
// 			delete_post_meta_by_key( $post_key );
// 		}

// 		$users = get_users();
// 		foreach ( $users as $user ) {
// 			foreach ( $user_meta_keys as $user_key ) {
// 				delete_user_meta( $user->ID, $user_key );
// 			}
// 		}

// 		// deleting mapped categories
// 		global $wpdb;
// 		$table_name = $wpdb->prefix . 'options';
// 		$sql        = $wpdb->get_results( 'SELECT * FROM ' . $table_name . ' WHERE option_name LIKE "%zoho_id_for_term_id_%"' );
// 		foreach ( $sql as $key => $row ) {
// 			$option_name = $row->option_name;
// 			delete_option( $option_name );
// 		}
// 	}
// }

/**
 * Hooks for WC Action Scheduler to import or export products
 */
$importProductClass = new ImportProductClass();
$importPricelist    = new ImportPricelistClass();
$productClass       = new ProductClass();
$orderClass = new Sync_Order_Class();
add_action( 'import_group_items_cron', array( $importProductClass, 'sync_groupitem_recursively' ), 10, 2 );
add_action( 'import_simple_items_cron', array( $importProductClass, 'sync_item_recursively' ), 10, 2 );
add_action( 'import_variable_product_cron', array( $importProductClass, 'import_variable_product_variations' ), 10, 2 );
add_action( 'sync_zi_product_cron', array( $productClass, 'zi_products_prepare_sync' ), 10, 2 );
add_action( 'sync_zi_pricelist', array( $importPricelist, 'zi_get_pricelist' ), 10, 2 );
add_action( 'sync_zi_order', array( $orderClass, 'zi_orders_prepare_sync' ), 10, 2 );

if ( is_admin() ) {
	Template::instance();
	Ajax::instance();
	Cors::instance();
}
// Load Media Library Endpoints
new WooCommerce_Media_API_By_wooventory();
// Load License Key library
if ( class_exists( 'Wooventory_AM_Client' ) ) {
	$wcam_lib_custom_menu = array(
		'menu_type'   => 'add_submenu_page',
		'parent_slug' => 'wooventory-app',
		'page_title'  => 'API key Activation',
		'menu_title'  => 'License Activation',
	);
	$wcam_lib             = new Wooventory_AM_Client( __FILE__, '', RMS_VERSION, 'plugin', 'https://wooventory.com/', 'Wooventory', '', $wcam_lib_custom_menu, false );
}
add_action(
	'rest_api_init',
	function () {
		new Zoho();
		new ProductWebhook();
		new ShippingWebhook();
	}
);
