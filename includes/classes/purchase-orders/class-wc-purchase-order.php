<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure WooCommerce is active
if ( ! class_exists( 'WC_Order' ) ) {
	return;
}

/**
 * Purchase Order Class
 *
 * This class extends the WC_Order class to add custom functionality for purchase orders.
 */

class WC_Purchase_Order extends WC_Order {
	public $order_type = '';

	public function __construct( $order_id = 0 ) {
		parent::__construct( $order_id );
		$this->order_type = 'shop_purchase';
		// Custom initialization code for purchase orders can go here
	}

	public function get_type() {
		return 'shop_purchase';
	}

	// You can override or add methods for handling purchase orders
	public function is_purchase() {
		return true;
	}
}

// Register the custom order type
function cmbird_register_shop_purchase_order_type() {
	wc_register_order_type( 'shop_purchase', array(
		'labels' => array(
			'name' => __( 'Purchase Orders', 'commercebird' ),
			'singular_name' => __( 'Purchase Order', 'commercebird' ),
			'add_new' => _x( 'Add Purchase', 'custom post type setting', 'commercebird' ),
			'add_new_item' => _x( 'Add New Purchase', 'custom post type setting', 'commercebird' ),
			'edit' => _x( 'Edit', 'custom post type setting', 'commercebird' ),
			'edit_item' => _x( 'Edit Purchase', 'custom post type setting', 'commercebird' ),
			'new_item' => _x( 'New Purchase', 'custom post type setting', 'commercebird' ),
			'view' => _x( 'View Purchase', 'custom post type setting', 'commercebird' ),
			'view_item' => _x( 'View Purchase', 'custom post type setting', 'commercebird' ),
			'search_items' => __( 'Search Purchases', 'commercebird' ),
			'not_found_in_trash' => _x( 'No Purchases found in trash', 'custom post type setting', 'commercebird' ),
			'parent' => _x( 'Parent Purchases', 'custom post type setting', 'commercebird' ),
			'menu_name' => __( 'Purchases', 'commercebird' ),
		),
		'public' => false,
		'show_ui' => true,
		'capability_type' => 'shop_order',
		'map_meta_cap' => true,
		'exclude_from_search' => true,
		'publicly_queryable' => false,
		'show_in_menu' => current_user_can( 'manage_woocommerce' ) ? 'woocommerce' : true,
		'hierarchical' => false,
		'has_archive' => false,
		'exclude_from_orders_screen' => true,
		'exclude_from_order_webhooks' => true,
		'exclude_from_order_count' => true,
		'exclude_from_order_views' => true,
		'exclude_from_order_reports' => true,
		'exclude_from_order_sales_reports' => true,
		'add_order_meta_boxes' => true,
		'class_name' => 'WC_Purchase_Order',
	) );
}
add_action( 'init', 'cmbird_register_shop_purchase_order_type' );

// Load custom class for Purchase Orders
function cmbird_load_purchase_order_class( $order_classname, $order_type, $order_id ) {
	if ( 'shop_purchase' === $order_type ) {
		$order_classname = 'WC_Purchase_Order';
	}
	return $order_classname;
}
add_filter( 'woocommerce_order_class', 'cmbird_load_purchase_order_class', 10, 3 );

function cmbird_custom_order_statuses( $order_statuses ) {
	$new_order_statuses = array();

	foreach ( $order_statuses as $key => $status ) {
		$new_order_statuses[ $key ] = $status;

		if ( 'wc-processing' === $key ) {
			$new_order_statuses['wc-purchase-processing'] = _x( 'Purchase Processing', 'Order status', 'commercebird' );
		}
	}

	return $new_order_statuses;
}
add_filter( 'wc_order_statuses', 'cmbird_custom_order_statuses' );