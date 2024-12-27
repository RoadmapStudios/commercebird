<?php
/**
 * WooCommerce Purchase Orders WC Admin Manager.
 *
 * @package  CommerceBird Purchase/Admin
 * @version  1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Initializes the Purchase Order Admin class.
 * @since 1.0.0
 * @return void
 */
class CMBIRD_PO_Admin_Manager {

	/**
	 * Initialise the class and attach hook callbacks.
	 */
	public static function init() {
		if ( ! defined( 'WC_ADMIN_PLUGIN_FILE' ) ) {
			return;
		}

		add_action( 'admin_menu', array( __CLASS__, 'cmbird_register_purchase_admin_pages' ) );
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
}
