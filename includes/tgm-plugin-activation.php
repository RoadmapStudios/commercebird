<?php

/**
 * This file represents an example of the code that themes would use to register
 * the required plugins.
 *
 * It is expected that theme authors would copy and paste this code into their
 * functions.php file, and amend to suit.
 *
 * @see        http://tgmpluginactivation.com/configuration/ for detailed documentation.
 *
 * @package    TGM-Plugin-Activation
 * @subpackage Example
 * @version    2.6.1 for plugin Organic Widgets
 * @author     Thomas Griffin, Gary Jones, Juliette Reinders Folmer
 * @copyright  Copyright (c) 2011, Thomas Griffin
 * @license    http://opensource.org/licenses/gpl-2.0.php GPL v2 or later
 * @link       https://github.com/TGMPA/TGM-Plugin-Activation
 */

/**
 * Include the Organic_Widgets_TGM_Plugin_Activation class.
 *
 * Depending on your implementation, you may want to change the include call:
 *
 * Plugin:
 * require_once dirname( __FILE__ ) . '/path/to/class-tgm-plugin-activation.php';
 */

use RMS\Admin\Ajax;

if ( ! class_exists( 'TGM_Plugin_Activation' ) ) {
	require_once __DIR__ . '/classes/class-tgm-plugin-activation.php';
}
add_action( 'tgmpa_register', 'rmsZI_register_required_plugins' );
/**
 * Register the required plugins for this plugin.
 *
 */
function rmsZI_register_required_plugins() {
	/*
	 * Array of plugin arrays. Required keys are name and slug.
	 * If the source is NOT from the .org repo, then source is also required.
	 */
	$plugins           = array();
	$subscription_data = Ajax::instance()->get_subscription_data();
	if ( array_key_exists( 'variation_id', $subscription_data ) ) {
		// this IF check should check if user is using the Premium plan (variation_id = 18)
		if ( in_array( 18, $subscription_data['variation_id'] ) ) {
			$plugins = array(
				array(
					'name'         => 'B2B for WooCommerce',
					// The plugin name.
					'slug'         => 'b2b-for-woocommerce',
					// The plugin slug (typically the folder name).
					'required'     => false,
					// If false, the plugin is only 'recommended' instead of required.
					'external_url' => 'https://woocommerce.com/products/b2b-for-woocommerce/',
					// If set, overrides default API URL and points to an external URL.
					'source'       => 'https://woocommerce.com/products/b2b-for-woocommerce/',
				),
				array(
					'name'         => 'Product Bundles for WooCommerce',
					'slug'         => 'product-bundles',
					'required'     => false, // If false, the plugin is only 'recommended' instead of required.
					'external_url' => 'https://woocommerce.com/products/product-bundles/',
					'source'       => 'https://woocommerce.com/products/product-bundles/',
				),

				// This is an example of how to include a plugin from the WordPress Plugin Repository.
				array(
					'name'     => 'Custom Order Statuses for WooCommerce',
					'slug'     => 'custom-order-statuses-woocommerce',
					'required' => true,
				),
			);
		} // below check for the Business plan (variation_id = 16)
		elseif ( in_array( 16, $subscription_data['variation_id'] ) ) {
			$plugins = array(
				array(
					'name'     => 'Custom Order Statuses for WooCommerce',
					'slug'     => 'custom-order-statuses-woocommerce',
					'required' => true,
				),
			);
		} else {
			return;
		}
	} else {
		return;
	}

	// Filter the plugins array based on class existence
	$filtered_plugins = array_filter(
		$plugins,
		function ( $plugin ) {
			$class_exists = true;
			if ( $plugin['slug'] === 'b2b-for-woocommerce' && class_exists( 'Addify_B2B_Plugin' ) ) {
				$class_exists = false;
			}
			if ( $plugin['slug'] === 'product-bundles' && class_exists( 'WC_Bundles' ) ) {
				$class_exists = false;
			}

			return $class_exists;
		},
	);

	$config = array(
		'id'           => 'rmsZI',
		// Unique ID for hashing notices for multiple instances of TGMPA.
		'default_path' => '',
		// Default absolute path to bundled plugins.
		'menu'         => 'tgmpa-install-plugins',
		// Menu slug.
		'parent_slug'  => 'plugins.php',
		// Parent menu slug.
		'capability'   => 'manage_options',
		// Capability needed to view plugin install page, should be a capability associated with the parent menu used.
		'has_notices'  => true,
		// Show admin notices or not.
		'dismissable'  => true,
		// If false, a user cannot dismiss the nag message.
		'dismiss_msg'  => '',
		// If 'dismissable' is false, this message will be output at top of nag.
		'is_automatic' => false,
		// Automatically activate plugins after installation or not.
		'strings'      => array(
			'page_title'                     => __( 'Install Recommended Plugins', 'theme-slug' ),
			'notice_can_install_recommended' => _n_noop(
			/* translators: 1: plugin name(s). */
				'The Zoho Inventory plugin recommends the following plugin: %1$s.',
				'The Zoho Inventory plugin recommends the following plugins: %1$s.',
				'tgmpa',
			),
		),
	);

	tgmpa( $filtered_plugins, $config );
}
