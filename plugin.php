<?php
/**
 * Plugin Name: CommerceBird
 * Plugin URI:  https://commercebird.com
 * Description: This plugin helps you get the most of CommerceBird by allowing you to upload product images, use integrations like Zoho CRM & Exact Online and more.
 * Version: 2.1.14
 * Requires PHP: 7.4
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @category  Fulfillment
 * @package   CommerceBird
 * @author    Fawad Tiemoerie <info@roadmapstudios.com>
 * @copyright Copyright (c) 2024, CommerceBird
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 *
 * WC requires at least: 8.0.0
 * WC tested up to: 8.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '' ) || die( 'No script kiddies please!' );
}
if ( ! defined( 'RMS_PLUGIN_NAME' ) ) {
	define( 'RMS_PLUGIN_NAME', 'CommerceBird' );
}
if ( ! defined( 'RMS_VERSION' ) ) {
	define( 'RMS_VERSION', '2.1.14' );
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
	define( 'RMS_MENU_SLUG', 'commercebird-app' );
}
if ( ! defined( 'RMS_DOCUMENTATION_URL' ) ) {
	define( 'RMS_DOCUMENTATION_URL', 'https://support.commercebird.com/portal/en/kb/' );
}
if ( ! defined( 'RMS_PLUGIN_URL' ) ) {
	define( 'RMS_PLUGIN_URL', 'https://commercebird.com/product/commercebird/' );
}

require_once RMS_DIR_PATH . 'includes/woo-functions.php';
require_once RMS_DIR_PATH . 'includes/sync/order-backend.php';
require_once RMS_DIR_PATH . 'includes/taxonomies/taxonomy-product_brands.php';
require_once RMS_DIR_PATH . 'data-sync.php';
require_once RMS_DIR_PATH . 'includes/wc-am-client.php';
require_once RMS_DIR_PATH . 'includes/tgm-plugin-activation.php';

require __DIR__ . '/vendor/autoload.php';

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use RMS\Admin\Actions\Ajax\ExactOnlineAjax;
use RMS\Admin\Actions\Sync\ExactOnlineSync;
use RMS\Admin\Actions\Ajax\ZohoInventoryAjax;
use RMS\Admin\Actions\Ajax\ZohoCRMAjax;
use RMS\Admin\Cors;
use RMS\Admin\Template;
use RMS\API\ProductWebhook;
use RMS\API\CreateOrderWebhook;
use RMS\API\CreateSFOrderWebhook;
use RMS\API\ShippingWebhook;
use RMS\API\Zoho;

/* Check the minimum PHP version on activation hook */
function woozo_check_plugin_requirements() {
	$php_min_version     = '7.4';
	$php_current_version = phpversion();

	if ( version_compare( $php_min_version, $php_current_version, '>' ) ) {
		deactivate_plugins( 'commercebird/plugin.php' );

		$error_message = sprintf( 'Your server is running PHP version %s but the commercebird plugin requires at least PHP %s. Please update your PHP version.', $php_current_version, $php_min_version, );

		wp_die(
			esc_html( $error_message ),
			'Plugin Activation Error',
			array(
				'response'  => 200,
				'back_link' => true,
			)
		);
	}
	$zoho_inventory_access_token = get_option( 'zoho_inventory_access_token' );
	// remove option zoho_inventory_access_token if it contains only one character
	if ( $zoho_inventory_access_token && strlen( $zoho_inventory_access_token ) === 1 ) {
		delete_option( 'zoho_inventory_access_token' );
	}
}

add_action( 'admin_init', 'woozo_check_plugin_requirements' );

// Activate plugin.
register_activation_hook( __FILE__, array( CMReviewReminder::class, 'activate' ) );

// Init hooks.
CMreviewReminder::initHooks();

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
	$interval     = get_option( 'zi_cron_interval', 'daily' );
	$access_token = get_option( 'zoho_inventory_access_token' );
	if ( 'none' !== $interval && ! empty( $access_token ) ) {
		if ( ! wp_next_scheduled( 'zi_execute_import_sync' ) ) {
			wp_schedule_event( time(), $interval, 'zi_execute_import_sync' );
		}
	} else {
		wp_clear_scheduled_hook( 'zi_execute_import_sync' );
	}
}

// Unschedule event upon plugin deactivation
register_deactivation_hook( __FILE__, 'zi_zoho_cron_deactivate' );
function zi_zoho_cron_deactivate() {
	wp_clear_scheduled_hook( 'zi_execute_import_sync' );
	update_option( 'woocommerce_enable_guest_checkout', 'yes' );
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

register_uninstall_hook( __FILE__, 'rms_cron_unsubscribe' );
/**
 * Unsunscribing cron at uninstall of hook.
 */
if ( ! function_exists( 'rms_cron_unsubscribe' ) ) {
	function rms_cron_unsubscribe() {
		// wp_clear_scheduled_hook( 'rms_cron_schedule_hook' );
		$post_meta_keys = array(
			'zi_item_id',
			'zi_purchase_account_id',
			'zi_account_id',
			'zi_account_name',
			'zi_inventory_account_id',
			'zi_salesorder_id',
			'zi_category_id',
		);
		$user_meta_keys = array(
			'zi_contact_id',
			'zi_primary_contact_id',
			'zi_created_time',
			'zi_last_modified_time',
			'zi_billing_address_id',
			'zi_shipping_address_id',
			'zi_contact_persons_id',
			'zi_currency_id',
			'zi_currency_code',
		);
		$zi_option_keys = array(
			'zi_cron_isactive',
			'zoho_inventory_cron_class',
			'zoho_sync_status',
			'zoho_item_category',
			'zoho_stock_sync_status',
			'zoho_item_name_sync_status',
			'zoho_enable_auto_no_status',
			'zoho_product_sync_status',
			'zoho_disable_image_sync_status',
			'zoho_disable_price_sync_status',
			'zoho_disable_name_sync_status',
			'zoho_disable_description_sync_status',
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
		);

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
		$sql        = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %s WHERE option_name LIKE %s', $table_name, '%zoho_id_for_term_id_%' ) );
		foreach ( $sql as $key => $row ) {
			$option_name = $row->option_name;
			$wpdb->delete( $table_name, array( 'option_name' => $option_name ), array( '%s' ) );
		}
	}
}

/**
 * Hooks for WC Action Scheduler to import or export products
 */
$import_products  = new import_product_class();
$import_pricelist = new ImportPricelistClass();
$product_class    = new ProductClass();
$order_class      = new Sync_Order_Class();
$contact_class    = new ContactClass();
$import_pricelist->wc_b2b_groups();
add_action( 'import_group_items_cron', array( $import_products, 'sync_groupitem_recursively' ), 10, 2 );
add_action( 'import_simple_items_cron', array( $import_products, 'sync_item_recursively' ), 10, 2 );
add_action( 'import_variable_product_cron', array( $import_products, 'import_variable_product_variations' ), 10, 2 );
add_action( 'sync_zi_product_cron', array( $product_class, 'zi_products_prepare_sync' ), 10, 2 );
add_action( 'sync_zi_pricelist', array( $import_pricelist, 'zi_get_pricelist' ), 10, 2 );
add_action( 'sync_zi_order', array( $order_class, 'zi_orders_prepare_sync' ), 10, 2 );
add_action( 'sync_zi_import_contacts', array( $contact_class, 'get_zoho_contacts' ), 10, 2 );
// Exact Online Hooks
add_action( 'sync_eo', array( ExactOnlineSync::class, 'sync' ), 10, 3 );

if ( is_admin() ) {
	if ( ! get_option( 'zi_webhook_password', false ) ) {
		update_option( 'zi_webhook_password', password_hash( 'commercebird-zi-webhook-token', PASSWORD_BCRYPT ) );
	}
	Template::instance();
	ZohoInventoryAjax::instance();
	ZohoCRMAjax::instance();
	Cors::instance();
}
ExactOnlineAjax::instance();
// CM_Webhook_Modify::instance();
// Load Media Library Endpoints
new CommerceBird_WC_API();
// Load License Key library
if ( class_exists( 'commercebird_AM_Client' ) ) {
	$wcam_lib_custom_menu = array(
		'menu_type'   => 'add_submenu_page',
		'parent_slug' => 'commercebird-app',
		'page_title'  => 'API key Activation',
		'menu_title'  => 'License Activation',
	);
	$wcam_lib             = new commercebird_AM_Client( __FILE__, '', RMS_VERSION, 'plugin', 'https://commercebird.com/', 'commercebird', '', $wcam_lib_custom_menu, false );
}
add_action(
	'rest_api_init',
	function () {
		new Zoho();
		new ProductWebhook();
		new ShippingWebhook();
		new CreateOrderWebhook();
		new CreateSFOrderWebhook();
	}
);


add_action(
	'save_post',
	function ( $post_id, $post ) {
		if ( $post->post_type === 'wcb2b_group' ) {
			delete_transient( 'wc_b2b_groups' );
		}
	},
	10,
	2
);
