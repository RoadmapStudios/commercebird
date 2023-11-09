<?php

/**
 * Plugin Name: WooCommerce Zoho Inventory - Pro
 * Plugin URI:  https//roadmapstudios.com
 * Description: Connect your Zoho Inventory with your WooCommerce store in
 * realtime to sync Customers, Items and Sales Orders. Manage your entire
 * inventory from Zoho. Please follow the documentation "Getting Started"
 * before you use this plugin. Version:     3.8.42 Author:      Roadmap Studios
 * Author URI:  https://roadmapstudios.com Requires PHP: 7.4 Domain Path:
 * /languages Text Domain: rmsZI License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package WooZo_Inventory
 * @license GNU General Public License v3.0
 *
 * WC requires at least: 7.0.0
 * WC tested up to: 8.0.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '' ) or die( 'No script kiddies please!' );
}
if ( ! defined( 'RMS_PLUGIN_NAME' ) ) {
	define( 'RMS_PLUGIN_NAME', 'Zoho Inventory' );
}
if ( ! defined( 'RMS_VERSION' ) ) {
	define( 'RMS_VERSION', '3.8.42' );
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
	define( 'RMS_MENU_SLUG', 'zoho-inventory' );
}
if ( ! defined( 'RMS_DOCUMENTATION_URL' ) ) {
	define( 'RMS_DOCUMENTATION_URL', 'https://support.roadmapstudios.com/portal/en/kb/zoho-inventory-woocommerce' );
}
if ( ! defined( 'RMS_PLUGIN_URL' ) ) {
	define( 'RMS_PLUGIN_URL', 'https://roadmapstudios.com/product/woocommerce-zoho-inventory/' );
}

include_once RMS_DIR_PATH . 'includes/woo-functions.php';
include_once RMS_DIR_PATH . 'includes/sync/order-backend.php';
include_once RMS_DIR_PATH . 'includes/sync/order-frontend.php';
include_once RMS_DIR_PATH . 'data-sync.php';
include_once RMS_DIR_PATH . 'includes/tgm-plugin-activation.php';
require_once RMS_DIR_PATH . 'libraries/action-scheduler/action-scheduler.php';

require_once __DIR__ . '/includes/config.php';
require __DIR__ . '/vendor/autoload.php';

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use RMS\Admin\Ajax;
use RMS\Admin\Cors;
use RMS\Admin\Template;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

//	remove on production
$whoops = new Run();
$whoops->pushHandler( new PrettyPageHandler() );
$whoops->register();
// remove on production

global $importProductClass, $productClass, $importPricelist, $zi_plugin_version, $zi_plugin_prod_id;
/* Check the minimum PHP version on activation hook */
function woozo_check_plugin_requirements() {
	$php_min_version     = '7.4';
	$php_current_version = phpversion();

	if ( version_compare( $php_min_version, $php_current_version, '>' ) ) {
		deactivate_plugins( 'woocommerce-zoho-inventory/woocommerce-zoho-inventory.php' );

		$error_message = sprintf(
			'Your server is running PHP version %s but the WooCommerce Zoho Inventory plugin requires at least PHP %s. Please update your PHP version.',
			$php_current_version,
			$php_min_version,
		);

		wp_die( $error_message, 'Plugin Activation Error', [
			'response'  => 200,
			'back_link' => TRUE,
		] );
	}
}

add_action( 'admin_init', 'woozo_check_plugin_requirements' );

/**
 * Function for initializing plugin object.
 */
if ( class_exists( 'WC_WooZo_Client' ) ) {
	$wcam_lib = new WC_WooZo_Client( __FILE__, $zi_plugin_prod_id, $zi_plugin_version, 'plugin', 'https://roadmapstudios.com/', 'WooCommerce Zoho Inventory' );

	if ( is_admin() && isset( $_GET['page'] ) && $_GET['page'] == 'zoho-inventory' ) {
		// Code to check plugin activation key validity.
		$api_key_option = get_option( $wcam_lib->data_key );
		$args           = [];
		if ( ! empty( $api_key_option ) ) {
			$wc_am_instance_id = get_option( $wcam_lib->data_key . '_instance' );
			$wc_am_domain      = str_ireplace( [
				'http://',
				'https://',
			], '', home_url() );

			$defaults      = [
				'request'    => 'status',
				'product_id' => $wcam_lib->product_id,
				'instance'   => $wc_am_instance_id,
				'object'     => $wc_am_domain,
			];
			$wc_am_api_key = $api_key_option[ $wcam_lib->data_key . '_api_key' ];
			$args          = [
				'api_key' => $wc_am_api_key,
			];

			$args       = wp_parse_args( $defaults, $args );
			$target_url = esc_url_raw( $wcam_lib->create_software_api_url( $args ) );
			$request    = wp_safe_remote_post( $target_url, [ 'timeout' => 15 ] );

			if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
				// Request failed
				return FALSE;
			}

			$response = wp_remote_retrieve_body( $request );
			$response = json_decode( $response, TRUE );
			if ( $response['success'] ) {
				if ( $response['status_check'] == 'active' ) {
					update_option( $wcam_lib->data_key . '_activated', 'Activated' );
				} else {
					update_option( $wcam_lib->data_key . '_activated', 'Deactivated' );
				}
			}
		}
		// Closing of validation code.
	}
}

// Activate plugin.
register_activation_hook( __FILE__, [ 'WCZohoInventory', 'activate' ] );

// Init hooks.
WCZohoInventory::initHooks();

/**
 * Scheduling multiple interval.
 */
if ( ! function_exists( 'rms_cron_schedules' ) ) {
	function rms_cron_schedules( $schedules ) {
		if ( ! isset( $schedules['1min'] ) ) {
			$schedules['1min'] = [
				'interval' => 60,
				'display'  => __( 'Once every 1 minutes' ),
			];
		}
		if ( ! isset( $schedules['10min'] ) ) {
			$schedules['10min'] = [
				'interval' => 10 * 60,
				'display'  => __( 'Once every 10 minutes' ),
			];
		}
		if ( ! isset( $schedules['1hour'] ) ) {
			$schedules['1hour'] = [
				'interval' => 60 * 60,
				'display'  => __( 'Once every hour' ),
			];
		}
		if ( ! isset( $schedules['1day'] ) ) {
			$schedules['1day'] = [
				'interval' => 24 * 60 * 60,
				'display'  => __( 'Once every day' ),
			];
		}

		return $schedules;
	}
}

add_filter( 'cron_schedules', 'rms_cron_schedules' );

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

/**
 * Declaring compatibility for WooCommerce HPOS
 */
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( FeaturesUtil::class ) ) {
		FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, TRUE );
	}
} );

register_uninstall_hook( __FILE__, 'rms_cron_unsubscribe' );
/**
 * Unsunscribing cron at uninstall of hook.
 */
if ( ! function_exists( 'rms_cron_unsubscribe' ) ) {
	function rms_cron_unsubscribe() {
		// wp_clear_scheduled_hook( 'rms_cron_schedule_hook' );
		$post_meta_keys = [
			'zi_item_id',
			'zi_purchase_account_id',
			'zi_account_id',
			'zi_account_name',
			'zi_inventory_account_id',
			'zi_salesorder_id',
			'zi_category_id',
		];
		$user_meta_keys = [
			'zi_contact_id',
			'zi_primary_contact_id',
			'zi_created_time',
			'zi_last_modified_time',
			'zi_billing_address_id',
			'zi_shipping_address_id',
			'zi_contact_persons_id',
			'zi_currency_id',
			'zi_currency_code',
		];
		$zi_option_keys = [
			'zi_cron_isactive',
			'zoho_inventory_cron_class',
			'zoho_sync_status',
			'zoho_item_category',
			'zoho_stock_sync_status',
			'zoho_item_name_sync_status',
			'zoho_enable_auto_no_status',
			'zoho_product_sync_status',
			'zoho_disable_itemimage_sync_status',
			'zoho_disable_itemprice_sync_status',
			'zoho_disable_itemname_sync_status',
			'zoho_disable_itemdescription_sync_status',
			'zoho_disable_groupitem_sync_status',
			'zoho_enable_attributes_sync_status',
			'zoho_enable_accounting_stock_status',
			'zoho_enable_order_status',
			'wootozoho_custom_fields',
			'zoho_pricelist_id',
			'zoho_warehouse_id',
			'zoho_inventory_auth_code',
			'zoho_inventory_access_token',
			'zoho_inventory_refresh_token',
			'zoho_inventory_timestamp',
			'rms_ck',
			'rms_cs',
		];

		foreach ( $zi_option_keys as $zi_option ) {
			delete_option( $zi_option );
		}

		foreach ( $post_meta_keys as $post_key ) {
			delete_post_meta_by_key( $post_key );
		}

		$users = get_users();
		foreach ( $users as $user ) {
			foreach ( $user_meta_keys as $user_key ) {
				delete_user_meta( $user->ID, $user_key );
			}
		}

		// deleting mapped categories
		global $wpdb;
		$table_name = $wpdb->prefix . 'options';
		$sql        = $wpdb->get_results( 'SELECT * FROM ' . $table_name . ' WHERE option_name LIKE "%zoho_id_for_term_id_%"' );
		foreach ( $sql as $key => $row ) {
			$option_name = $row->option_name;
			delete_option( $option_name );
		}
	}
}

/**
 * Hooks for WC Action Scheduler to import or export products
 */
add_action( 'import_group_items_cron', [
	$importProductClass,
	'sync_groupitem_recursively',
], 10, 2 );
add_action( 'import_simple_items_cron', [
	$importProductClass,
	'sync_item_recursively',
], 10, 2 );
add_action( 'import_variable_product_cron', [
	$importProductClass,
	'import_variable_product_variations',
], 10, 2 );
add_action( 'sync_zi_product_cron', [
	$productClass,
	'zi_products_prepare_sync',
], 10, 2 );
add_action( 'sync_zi_pricelist', [
	$importPricelist,
	'zi_get_pricelist',
], 10, 2 );

if ( is_admin() ) {
	Template::instance();
	Ajax::instance();
	Cors::instance();
}
// From wooventory
new WooCommerce_Media_API_By_wooventory();
