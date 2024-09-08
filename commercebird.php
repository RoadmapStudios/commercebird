<?php
/**
 * Plugin Name: CommerceBird
 * Plugin URI:  https://commercebird.com
 * Author:      CommerceBird
 * Description: This plugin helps you get the most of CommerceBird by allowing you to upload product images, use integrations like Zoho CRM & Exact Online and more.
 * Version: 2.2.0
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * Requires at least: 6.5
 * Tested up to: 6.6.1
 * Text Domain: commercebird
 * Domain Path: /languages
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
 * WC tested up to: 9.2.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '' ) || die( 'No script kiddies please!' );
}

if ( ! defined( 'CMBIRD_VERSION' ) ) {
	define( 'CMBIRD_VERSION', '2.2.0' );
}
if ( ! defined( 'CMBIRD_PATH' ) ) {
	define( 'CMBIRD_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'CMBIRD_URL' ) ) {
	define( 'CMBIRD_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'CMBIRD_MENU_SLUG' ) ) {
	define( 'CMBIRD_MENU_SLUG', 'commercebird-app' );
}

require_once CMBIRD_PATH . 'includes/woo-functions.php';
require_once CMBIRD_PATH . 'includes/sync/order-backend.php';
require_once CMBIRD_PATH . 'includes/taxonomies/taxonomy-product_brands.php';
require_once CMBIRD_PATH . 'data-sync.php';
require_once CMBIRD_PATH . 'includes/wc-am-client.php';

require __DIR__ . '/vendor/autoload.php';

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use CommerceBird\Plugin;
use CommerceBird\Admin\Actions\Sync\ExactOnlineSync;
use CommerceBird\Admin\Actions\Sync\ZohoCRMSync;
use CommerceBird\API\ProductWebhook;
use CommerceBird\API\CreateOrderWebhook;
use CommerceBird\API\CreateSFOrderWebhook;
use CommerceBird\API\ShippingWebhook;
use CommerceBird\API\Zoho;
use CommerceBird\API\Exact;

/*
|--------------------------------------------------------------------------
| Activation, deactivation and uninstall event.
|--------------------------------------------------------------------------
*/
register_activation_hook( __FILE__, array( Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Plugin::class, 'deactivate' ) );
register_uninstall_hook( __FILE__, array( Plugin::class, 'uninstall' ) );

/*
|--------------------------------------------------------------------------
| Start the plugin
|--------------------------------------------------------------------------
*/
Plugin::init();

/**
 * Declaring compatibility for WooCommerce HPOS
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( FeaturesUtil::class) ) {
			FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

/**
 * Hooks for WC Action Scheduler to import or export products
 */
$import_products = new CMBIRD_Products_ZI();
$import_pricelist = new CMBIRD_Pricelist_ZI();
$product_class = new CMBIRD_Products_ZI_Export();
$order_class = new CMBIRD_Order_Sync_ZI();
$contact_class = new CMBIRD_Contact_ZI();
$import_pricelist->wc_b2b_groups();
add_action( 'import_group_items_cron', array( $import_products, 'sync_groupitem_recursively' ), 10, 2 );
add_action( 'import_simple_items_cron', array( $import_products, 'sync_item_recursively' ), 10, 2 );
add_action( 'import_variable_product_cron', array( $import_products, 'import_variable_product_variations' ), 10, 2 );
add_action( 'sync_zi_product_cron', array( $product_class, 'cmbird_zi_products_prepare_sync' ), 10, 2 );
add_action( 'sync_zi_pricelist', array( $import_pricelist, 'zi_get_pricelist' ), 10, 2 );
add_action( 'sync_zi_order', array( $order_class, 'zi_orders_prepare_sync' ), 10, 2 );
add_action( 'sync_zi_import_contacts', array( $contact_class, 'cmbird_get_zoho_contacts' ), 10, 2 );
// add action to set the zoho rate limit option exceeded to false
add_action( 'commercebird_common', array( CMBIRD_Common_Functions::class, 'set_zoho_rate_limit_option' ) );
// Exact Online Hooks
add_action( 'sync_eo', array( ExactOnlineSync::class, 'sync' ), 10, 3 );
add_action( 'sync_payment_status', array( ExactOnlineSync::class, 'sync_payment_status' ), 10, 1 );
add_action( 'commmercebird_exact_online_get_payment_statuses', array( ExactOnlineSync::class, 'get_payment_status_via_cron' ) );
// Zoho CRM Hooks
add_action( 'init', array( ZohoCRMSync::class, 'refresh_token' ) );

// Load License Key library
if ( class_exists( 'commercebird_AM_Client' ) ) {
	$wcam_lib_custom_menu = array(
		'menu_type' => 'add_submenu_page',
		'parent_slug' => 'commercebird-app',
		'page_title' => 'API key Activation',
		'menu_title' => 'License Activation',
	);
	$wcam_lib = new commercebird_AM_Client( __FILE__, '', CMBIRD_VERSION, 'plugin', 'https://commercebird.com/', 'commercebird', '', $wcam_lib_custom_menu, false );
}
// add classes to REST API
add_action(
	'rest_api_init',
	function () {
		new Zoho();
		new Exact();
		new ProductWebhook();
		new ShippingWebhook();
		new CreateOrderWebhook();
		new CreateSFOrderWebhook();
	}
);

add_action(
	'save_post',
	function ($post_id, $post) {
		if ( $post->post_type === 'wcb2b_group' ) {
			delete_transient( 'wc_b2b_groups' );
		}
	},
	10,
	2
);

/**
 * Perform actions when the plugin is updated
 * @param string $upgrader_object
 * @param array $options
 * @return void
 */
add_action( 'upgrader_process_complete', 'cmbird_update_plugin_tasks', 10, 2 );

function cmbird_update_plugin_tasks( $upgrader_object, $options ) {
	$this_plugin = plugin_basename( __FILE__ );

	if ( '2.1.18' >= CMBIRD_VERSION ) {
		return;
	}

	if ( $options['action'] === 'update' && $options['type'] === 'plugin' ) {
		foreach ( $options['plugins'] as $plugin ) {
			if ( $plugin === $this_plugin ) {
				// Perform tasks when the plugin is updated
				$zoho_inventory_url = get_option( 'zoho_inventory_url' );
				if ( $zoho_inventory_url ) {
					$new_zoho_inventory_url = str_replace( 'inventory.zoho', 'www.zohoapis', $zoho_inventory_url );
					update_option( 'zoho_inventory_url', $new_zoho_inventory_url );
				}
			}
		}
	}
}
