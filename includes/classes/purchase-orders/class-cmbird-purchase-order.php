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

class CMBIRD_Purchase_Order extends WC_Order {
	public $order_type = 'shop_purchase';

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

	public function remove_item( $item_id ) {
		parent::remove_item( $item_id );
		parent::calculate_totals();
	}

	// update meta data
	public function update_meta_data( $key, $value, $meta_id = 0 ) {
		parent::update_meta_data( $key, $value, $meta_id );
		// Custom logic for updating meta data for purchase orders
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
		'class_name' => 'CMBIRD_Purchase_Order',
	) );

	register_post_status(
		'wc-awaiting-approval',
		array(
			'label' => _x( 'Awaiting Approval', 'Order status', 'commercebird' ),
			'public' => true,
			'exclude_from_search' => false,
			'show_in_admin_all_list' => true,
			'show_in_admin_status_list' => true,
			// translators: 1-2: 1: count
			'label_count' => _n_noop( 'Awaiting Approval <span class="count">(%s)</span>', 'Awaiting Approval <span class="count">(%s)</span>', 'commercebird' ),
		)
	);

	register_post_status(
		'wc-approved',
		array(
			'label' => _x( 'Approved', 'Order status', 'commercebird' ),
			'public' => true,
			'exclude_from_search' => false,
			'show_in_admin_all_list' => true,
			'show_in_admin_status_list' => true,
			// translators: 1-2: 1: count
			'label_count' => _n_noop( 'Approved <span class="count">(%s)</span>', 'Approved <span class="count">(%s)</span>', 'commercebird' ),
		)
	);

	register_post_status(
		'wc-received',
		array(
			'label' => _x( 'Received', 'Order status', 'commercebird' ),
			'public' => true,
			'exclude_from_search' => false,
			'show_in_admin_all_list' => true,
			'show_in_admin_status_list' => true,
			// translators: 1-2: 1: count
			'label_count' => _n_noop( 'Received <span class="count">(%s)</span>', 'Received <span class="count">(%s)</span>', 'commercebird' ),
		)
	);

	// Create the "vendor" role
	add_role(
		'vendor',
		__( 'Vendor', 'commercebird' ),
		array(
			'read' => true,
		)
	);

}
add_action( 'init', 'cmbird_register_shop_purchase_order_type' );

// Load custom class for Purchase Orders
function cmbird_load_purchase_order_class( $order_classname, $order_type, $order_id ) {
	if ( 'shop_purchase' === $order_type ) {
		$order_classname = 'CMBIRD_Purchase_Order';
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
function cmbird_custom_order_statuses( $order_statuses ) {

	// check if current screen is shop_purchase with wc_get_screen_id()
	if ( ! wc_get_order_type( 'shop_purchase' ) ) {
		return $order_statuses;
	}

	$new_order_statuses = array();
	foreach ( $order_statuses as $key => $status ) {
		$new_order_statuses[ $key ] = $status;
		if ( 'wc-pending' === $key ) {
			$new_order_statuses['wc-awaiting-approval'] = 'Awaiting Approval';
			$new_order_statuses['wc-approved'] = 'Approved';
			$new_order_statuses['wc-received'] = 'Received';
		}
	}
	return $new_order_statuses;
}
add_filter( 'wc_order_statuses', 'cmbird_custom_order_statuses' );

function cmbird_custom_shop_purchase_bulk_actions( $bulk_actions ) {
	$bulk_actions['mark_awaiting_approval'] = __( 'Change status to awaiting approval', 'commercebird' );
	$bulk_actions['mark_approved'] = __( 'Change status to approved', 'commercebird' );
	$bulk_actions['mark_received'] = __( 'Change status to received', 'commercebird' );

	// unset the statuses that are not needed - e.g. 'mark_processing'
	unset( $bulk_actions['mark_processing'] );
	unset( $bulk_actions['mark_on-hold'] );
	unset( $bulk_actions['mark_completed'] );

	return $bulk_actions;
}
add_filter( 'bulk_actions-edit-shop_purchase', 'cmbird_custom_shop_purchase_bulk_actions' );
// HPOS Screens
add_filter( 'bulk_actions-woocommerce_page_wc-orders--shop_purchase', 'cmbird_custom_shop_purchase_bulk_actions' );


function cmbird_handle_custom_status_transitions( $order_id, $old_status, $new_status ) {

	if ( 'awaiting-approval' === $new_status ) {
		// Logic for when the order is awaiting approval
	}

	if ( 'approved' === $new_status ) {
		// Logic for when the order is approved
	}

	if ( 'received' === $new_status ) {
		// Increase the stock of the products in the order when the order is received
		$order = wc_get_order( $order_id );
		foreach ( $order->get_items() as $item_id => $item ) {
			$product = $item->get_product();
			if ( $product && $product->managing_stock() ) { // Check if stock management is enabled
				$stock_quantity = $product->get_stock_quantity();
				$new_stock = $stock_quantity + $item->get_quantity();

				// Update stock quantity
				$product->set_stock_quantity( $new_stock );
				$product->save();
			}
		}
	}
}
add_action( 'woocommerce_order_status_changed', 'cmbird_handle_custom_status_transitions', 10, 3 );

// Set cost price in purchase orders when created or edited via Admin
function cmbird_set_cost_price_for_admin_purchase_order( $post_id, $post, $update ) {
	if ( 'shop_purchase' === wc_get_order_type( $post_id ) ) {

		$order = wc_get_order( $post_id );

		if ( $order && 'shop_purchase' === $order->get_type() ) {

			foreach ( $order->get_items() as $item_id => $item ) {
				$product = $item->get_product();

				if ( $product ) {
					// Get the cost price from product meta
					$cost_price = get_post_meta( $product->get_id(), '_cost_price', true );

					// If cost price is set, update the item price in the order
					if ( $cost_price && is_numeric( $cost_price ) ) {
						$item->set_subtotal( $cost_price * $item->get_quantity() );
						$item->set_total( $cost_price * $item->get_quantity() );
						$item->save();
					}
				}
			}

			// Recalculate totals for the order after setting cost price
			$order->calculate_totals();

			// Set the store's address as the shipping address for purchase orders
			$warehouse_address = get_option( 'woocommerce_wh_address', '' );
			$warehouse_address_2 = get_option( 'woocommerce_wh_address_2', '' );
			$warehouse_city = get_option( 'woocommerce_wh_city', '' );
			$warehouse_state = get_option( 'woocommerce_wh_state', '' );
			$warehouse_country = get_option( 'woocommerce_wh_country', '' );
			$warehouse_zip = get_option( 'woocommerce_wh_postcode', '' );

			// Update the shipping address with store details
			$shipping_address = array(
				'first_name' => 'Store',
				'last_name' => 'Address',
				'company' => get_option( 'blogname' ),
				'address_1' => $warehouse_address,
				'address_2' => $warehouse_address_2,
				'city' => $warehouse_city,
				'state' => $warehouse_state,
				'postcode' => $warehouse_zip,
				'country' => $warehouse_country,
			);

			// Set the new shipping address for the order
			$order->set_address( $shipping_address, 'shipping' );

			// save shipping address as customer shipping address.
			$customer = $order->get_customer();
			$customer->set_shipping_address( $shipping_address );
			$customer->save();
		}
	}
}
add_action( 'save_post_shop_purchase', 'cmbird_set_cost_price_for_admin_purchase_order', 10, 3 );

// Add warehouse address in woocommerce settings
add_filter( 'woocommerce_general_settings', 'cmbird_additional_store_addresses_admin', 9999 );

function cmbird_additional_store_addresses_admin( $settings ) {

	$new_settings = array(

		array(
			'title' => 'Warehouse Address',
			'type' => 'title',
			'id' => 'wh_address',
		),

		array(
			'title' => __( 'Address line 1', 'commercebird' ),
			'id' => 'woocommerce_wh_address',
			'type' => 'text',
		),

		array(
			'title' => __( 'Address line 2', 'commercebird' ),
			'id' => 'woocommerce_wh_address_2',
			'type' => 'text',
		),

		array(
			'title' => __( 'City', 'commercebird' ),
			'id' => 'woocommerce_wh_city',
			'type' => 'text',
		),

		array(
			'title' => __( 'State', 'commercebird' ),
			'id' => 'woocommerce_wh_state',
			'type' => 'text',
		),

		array(
			'title' => __( 'Country', 'commercebird' ),
			'id' => 'woocommerce_wh_country',
			'type' => 'single_select_country',
		),

		array(
			'title' => __( 'Postcode / ZIP', 'commercebird' ),
			'id' => 'woocommerce_wh_postcode',
			'type' => 'text',
		),

		array(
			'type' => 'sectionend',
			'id' => 'wh_address',
		),

	);

	return array_merge( array_slice( $settings, 0, 7 ), $new_settings, array_slice( $settings, 7 ) );
}

/**
 * Use 'woocommerce_rest_insert_customer' action hook to change the user role to vendor when creating customer.
 *
 * @param WP_User         $user_data Data used to create the customer.
 * @param WP_REST_Request $request   Request object.
 * @param boolean         $creating  True when creating customer, false when updating customer.
 */
function cmbird_change_user_role_to_vendor( $user_data, $request, $creating ) {
	// Get the property 'role' from the request object
	$role = $request->get_param( 'role' );

	// Change the user role to 'vendor'
	if ( ! empty( $role ) && 'vendor' === $role ) {
		$user_data->set_role( 'vendor' );
	}

	// Return the user data
	return $user_data;
}
add_action( 'woocommerce_rest_insert_customer', 'cmbird_change_user_role_to_vendor', 10, 3 );

/**
 * Register the custom email class for Purchase Orders
 * @param array $email_classes
 * @return array
 */
function cmbird_register_purchase_order_email( $email_classes ) {
	require_once 'class-cmbird-purchase-order-email.php';
	$email_classes['CMBIRD_Email_Purchase_Order'] = new CMBIRD_Email_Purchase_Order();
	return $email_classes;
}
add_action( 'woocommerce_email_classes', 'cmbird_register_purchase_order_email' );

function cmbird_format_purchase_order_number( $order_number, $order ) {
	if ( 'shop_purchase' === $order->get_type() ) {
		return 'PO-' . $order_number;
	}
	return $order_number;
}
add_filter( 'woocommerce_order_number', 'cmbird_format_purchase_order_number', 10, 2 );

function cmbird_set_vendor_email_recipient( $recipient, $order ) {
	if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
		return $recipient;
	}

	if ( 'shop_purchase' === $order->get_type() ) {
		$vendor_id = $order->get_customer_id();
		$vendor = get_userdata( $vendor_id );

		if ( $vendor && in_array( 'vendor', (array) $vendor->roles ) ) {
			$recipient = $vendor->user_email;
		}
	}
	return $recipient;
}
add_filter( 'woocommerce_email_recipient_new_order', 'cmbird_set_vendor_email_recipient', 10, 2 );

function cmbird_customize_purchase_order_email_subject( $subject, $order ) {
	if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
		return $subject;
	}

	if ( 'shop_purchase' === $order->get_type() ) {
		$subject = sprintf( __( 'New Purchase Order: PO-%s', 'commercebird' ), $order->get_id() );
	}
	return $subject;
}
add_filter( 'woocommerce_email_subject_new_order', 'cmbird_customize_purchase_order_email_subject', 10, 2 );

function cmbird_customize_purchase_order_email_heading( $heading, $email ) {
	if ( isset( $email->object ) && is_a( $email->object, 'WC_Order' ) && 'shop_purchase' === $email->object->get_type() ) {
		$heading = __( 'You Have a New Purchase Order', 'commercebird' );
	}
	return $heading;
}
add_filter( 'woocommerce_email_heading_new_order', 'cmbird_customize_purchase_order_email_heading', 10, 2 );

function cmbird_remove_order_totals_for_shop_purchase( $totals, $order ) {
	if ( $order->get_type() === 'shop_purchase' ) {
		// Remove pricing-related rows
		unset( $totals['cart_subtotal'] );
		unset( $totals['discount'] );
		unset( $totals['shipping'] );
		unset( $totals['payment_method'] );
		unset( $totals['order_total'] );
	}
	return $totals;
}
add_filter( 'woocommerce_get_order_item_totals', 'cmbird_remove_order_totals_for_shop_purchase', 10, 2 );

function cmbird_remove_item_prices_for_shop_purchase( $items, $order ) {
	if ( $order instanceof WC_Order && $order->get_type() === 'shop_purchase' ) {
		foreach ( $items as $item_id => $item ) {
			// Set subtotal and total to zero
			$item->set_subtotal( 0 );
			$item->set_total( 0 );
		}
	}
	return $items;
}
add_filter( 'woocommerce_order_get_items', 'cmbird_remove_item_prices_for_shop_purchase', 10, 2 );