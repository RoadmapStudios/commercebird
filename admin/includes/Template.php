<?php

namespace CommerceBird\Admin;

use CommerceBird\Admin\Traits\Singleton;
use CommerceBird\API\CreateOrderWebhook;
use CommerceBird\API\ProductWebhook;
use CommerceBird\API\ShippingWebhook;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Template {
	use Singleton;

	const NAME = 'commercebird-app';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
	}

	public function menu(): void {
		$svg = CMBIRD_URL . 'admin/commercebird-icon.svg';
		add_menu_page(
			__( 'CommerceBird', 'commercebird' ),
			__( 'CommerceBird', 'commercebird' ),
			'manage_woocommerce',
			self::NAME,
			function () {
				wp_enqueue_style( self::NAME );
				wp_enqueue_style( self::NAME . '-notify', CMBIRD_URL . 'admin/css/notyf.min.css', array(), CMBIRD_VERSION );
				// Pass version number to the Vue script
				wp_localize_script(
					self::NAME,
					'cmbirdData',
					array(
						'version' => CMBIRD_VERSION,
					)
				);
				wp_enqueue_script( self::NAME );
				add_filter( 'script_loader_tag', array( $this, 'add_module' ), 10, 3 );
				printf( '<div id="%s">Loading...</div>', esc_attr( self::NAME ) );
			},
			$svg,
			29
		);
	}

	/**
	 * It will add module attribute to script tag.
	 *
	 * @param string $tag of script.
	 * @param string $id of script.
	 *
	 * @return string
	 */
	public function add_module( string $tag, string $id ): string {
		if ( self::NAME === $id ) {
			$tag = str_replace( '<script ', '<script type="module" ', $tag );
		}

		return $tag;
	}

	/**
	 * It loads scripts based on plugin's mode, dev or prod.
	 *
	 * @return void
	 */
	public function scripts(): void {
		global $wp_roles;
		// comment on production
		wp_register_style( self::NAME, 'http://localhost:5000/src/main.css', array(), CMBIRD_VERSION );
		wp_register_script( self::NAME, 'http://localhost:5000/src/main.js', array(), CMBIRD_VERSION, true );
		// comment on production
		wp_register_style( self::NAME, CMBIRD_URL . 'admin/assets/dist/index.css', array(), CMBIRD_VERSION );
		wp_register_script( self::NAME, CMBIRD_URL . 'admin/assets/dist/index.js', array(), CMBIRD_VERSION, true );
		wp_add_inline_style( self::NAME, '#wpcontent, .auto-fold #wpcontent{padding-left: 0px} #wpcontent .notice, #wpcontent #message{display: none} input[type=checkbox]:checked::before{content:unset}' );
		$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );
		wp_localize_script(
			self::NAME,
			'commercebird_admin',
			array(
				'security_token' => wp_create_nonce( self::NAME ),
				'api_token' => get_option( 'cmbird_zi_webhook_password', false ),
				'webhooks' => array(
					'Items' => ProductWebhook::endpoint(),
					'Order Create' => CreateOrderWebhook::endpoint(),
					'Shipping Status' => ShippingWebhook::endpoint(),
				),
				'redirect_uri' => admin_url( 'admin.php?page=commercebird-app' ),
				'url' => admin_url( 'admin-ajax.php' ),
				'wc_tax_enabled' => is_plugin_active( 'woocommerce/woocommerce.php' ) ? wc_tax_enabled() : false,
				'roles' => $wp_roles->get_names(),
				'b2b_enabled' => class_exists( 'Addify_B2B_Plugin' ),
				'fileinfo_enabled' => extension_loaded( 'fileinfo' ),
				'acf_enabled' => class_exists( 'ACF' ),
				'cosw_enabled' => self::is_wc_shipped_status_exists(),
				'wcb2b_enabled' => class_exists( 'WooCommerceB2B' ),
				'wcb2b_groups' => get_transient( 'wc_b2b_groups' ),
				'site_url' => site_url(),
				'eo_sync' => get_option( 'cmbird_exact_online_sync_orders' ),
			),
		);
	}

	/**
	 * Check if the status "shipped" exists in WooCommerce statuses.
	 *
	 * @return bool
	 */
	public static function is_wc_shipped_status_exists(): bool {
		$shipped_status = wc_get_order_statuses();
		return isset( $shipped_status['wc-shipped'] );
	}
}
