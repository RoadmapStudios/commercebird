<?php
/**
 * WooCommerce Purchase Orders WC Admin Manager.
 *
 * @package  WooCommerce Purchase/Admin
 * @version  1.0.0
 */

use Automattic\WooCommerce\Admin\Features\Navigation\Menu;
use Automattic\WooCommerce\Admin\Features\Navigation\Screen;

defined( 'ABSPATH' ) || exit;

/**
 * Initializes the Purchase Order Admin class.
 * @since 1.0.0
 * @return void
 */
class WCP_WC_Admin_Manager {

	/**
	 * Initialise the class and attach hook callbacks.
	 */
	public static function init() {
		if ( ! defined( 'WC_ADMIN_PLUGIN_FILE' ) ) {
			return;
		}

		add_action( 'admin_menu', array( __CLASS__, 'cmbird_register_purchase_admin_pages' ) );
		add_action( 'admin_menu', array( __CLASS__, 'cmbird_register_navigation_items' ), 6 );
	}

	/**
	 * Connects existing WooCommerce purchase admin pages to WooCommerce Admin.
	 */
	public static function cmbird_register_purchase_admin_pages() {

		// WooCommerce > Purchase.
		wc_admin_connect_page(
			array(
				'id' => 'woocommerce-purchases',
				'screen_id' => 'edit-shop_purchase',
				'title' => __( 'Purchase Orders', 'commercebird' ),
				'path' => add_query_arg( 'post_type', 'shop_purchase', 'edit.php' ),
			)
		);

		// WooCommerce > Purchase Orders (HPOS)
		wc_admin_connect_page(
			array(
				'id' => 'woocommerce-purchases',
				'screen_id' => wc_get_page_screen_id( 'shop_purchase' ),
				'title' => __( 'Purchase Orders', 'commercebird' ),
				'path' => 'admin.php?page=wc-orders--shop_purchase',
			)
		);

		// WooCommerce > Purchase Orders > Add New.
		wc_admin_connect_page(
			array(
				'id' => 'woocommerce-add-purchase',
				'parent' => 'woocommerce-purchases',
				'screen_id' => 'shop_purchase-add-new',
				'title' => __( 'Add New Purchase Order', 'commercebird' ),
			)
		);

		// WooCommerce > Purchase Orders > Edit purchase.
		wc_admin_connect_page(
			array(
				'id' => 'woocommerce-edit-purchase',
				'parent' => 'woocommerce-purchases',
				'screen_id' => 'shop_purchase',
				'title' => __( 'Edit purchase', 'commercebird' ),
			)
		);
	}

	/**
	 * Register the navigation items in the WooCommerce navigation.
	 *
	 * @since 1.0.0
	 */
	public static function cmbird_register_navigation_items() {
		if (
			! class_exists( '\Automattic\WooCommerce\Admin\Features\Navigation\Menu' ) ||
			! class_exists( '\Automattic\WooCommerce\Admin\Features\Navigation\Screen' )
		) {
			return;
		}

		$purchase_items = Menu::get_post_type_items(
			'shop_purchase',
			array(
				'title' => __( 'Purchase Orders', 'commercebird' ),
			)
		);

		Menu::add_plugin_item( $purchase_items['all'] );
		Screen::register_post_type( 'shop_purchase' );
	}
}