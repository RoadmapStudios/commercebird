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

/**
 * Custom order statuses for Purchase Orders
 * @param mixed $order_statuses
 * @return array
 * @since 1.0.0
 */
// Add custom order statuses for 'shop_purchase'
function cmbird_register_custom_shop_purchase_statuses() {
	register_post_status( 'wc-awaiting-approval', array(
		'label' => _x( 'Awaiting Approval', 'Order status', 'commercebird' ),
		'public' => true,
		'exclude_from_search' => false,
		'show_in_admin_all_list' => true,
		'show_in_admin_status_list' => true,
		'label_count' => _n_noop( 'Awaiting Approval <span class="count">(%s)</span>', 'Awaiting Approval <span class="count">(%s)</span>', 'commercebird' ),
	) );

	register_post_status( 'wc-approved', array(
		'label' => _x( 'Approved', 'Order status', 'commercebird' ),
		'public' => true,
		'exclude_from_search' => false,
		'show_in_admin_all_list' => true,
		'show_in_admin_status_list' => true,
		'label_count' => _n_noop( 'Approved <span class="count">(%s)</span>', 'Approved <span class="count">(%s)</span>', 'commercebird' ),
	) );

	register_post_status( 'wc-received', array(
		'label' => _x( 'Received', 'Order status', 'commercebird' ),
		'public' => true,
		'exclude_from_search' => false,
		'show_in_admin_all_list' => true,
		'show_in_admin_status_list' => true,
		'label_count' => _n_noop( 'Received <span class="count">(%s)</span>', 'Received <span class="count">(%s)</span>', 'commercebird' ),
	) );
}
add_action( 'init', 'cmbird_register_custom_shop_purchase_statuses', 9 );
function cmbird_custom_order_statuses( $order_statuses ) {
	global $post;
	if ( 'shop_purchase' === get_post_type( $post->ID ) ) {
		$order_statuses['wc-awaiting-approval'] = _x( 'Awaiting Approval', 'Order status', 'commercebird' );
		$order_statuses['wc-approved'] = _x( 'Approved', 'Order status', 'commercebird' );
		$order_statuses['wc-received'] = _x( 'Received', 'Order status', 'commercebird' );
	}
	return $order_statuses;
}
add_filter( 'wc_order_statuses', 'cmbird_custom_order_statuses' );

function cmbird_custom_shop_purchase_bulk_actions( $bulk_actions ) {
	$bulk_actions['mark-awaiting-approval'] = __( 'Change status to awaiting approval', 'commercebird' );
	$bulk_actions['mark-approved'] = __( 'Change status to approved', 'commercebird' );
	$bulk_actions['mark-received'] = __( 'Change status to received', 'commercebird' );

	return $bulk_actions;
}
add_filter( 'bulk_actions-edit-shop_purchase', 'cmbird_custom_shop_purchase_bulk_actions' );

function cmbird_display_custom_shop_purchase_statuses_in_admin( $order_statuses ) {
	$new_order_statuses = array();
	// Loop through existing statuses and inject custom ones
	foreach ( $order_statuses as $key => $status ) {
		$new_order_statuses[ $key ] = $status;

		if ( 'wc-on-hold' === $key ) { // Add after a specific status, e.g., 'on-hold'
			$new_order_statuses['wc-awaiting-approval'] = _x( 'Awaiting Approval', 'Order status', 'commercebird' );
			$new_order_statuses['wc-approved'] = _x( 'Approved', 'Order status', 'commercebird' );
			$new_order_statuses['wc-received'] = _x( 'Received', 'Order status', 'commercebird' );
		}
	}
	return $new_order_statuses;
}
add_filter( 'woocommerce_order_statuses', 'cmbird_display_custom_shop_purchase_statuses_in_admin' );

function cmbird_handle_custom_status_transitions( $order_id, $old_status, $new_status ) {
	if ( 'wc-awaiting-approval' === $new_status ) {
		// Logic for when the order is awaiting approval
	}

	if ( 'wc-approved' === $new_status ) {
		// Logic for when the order is approved
	}

	if ( 'wc-received' === $new_status ) {
		// Increase the stock of the products in the order when the order is received
		$order = wc_get_order( $order_id );
		foreach ( $order->get_items() as $item_id => $item ) {
			$product = $item->get_product();
			if ( $product ) {
				$stock_quantity = $product->get_stock_quantity();
				$product->set_stock_quantity( $stock_quantity + $item->get_quantity() );
				$product->save();
			}
		}
	}
}

add_action( 'woocommerce_order_status_changed', 'cmbird_handle_custom_status_transitions', 10, 3 );