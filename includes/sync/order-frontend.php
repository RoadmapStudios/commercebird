<?php

/**
 * All Frontend Order sync related functions.
 *
 * @package  WooZo Inventory
 * @category Zoho Integration
 * @author   Roadmap Studios
 * @link     https://commercebird.com
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sync the order from frontend to Zoho Inventory
 */
add_action( 'woocommerce_thankyou', 'zi_sync_frontend_order' );
function zi_sync_frontend_order( $order_id ) {
	$zoho_inventory_access_token = get_option( 'zoho_inventory_access_token' );
	if ( empty( $zoho_inventory_access_token ) ) {
		return;
	}
	// Check if the transient flag is set
	if ( get_transient( 'your_thankyou_callback_executed_' . $order_id ) ) {
		return;
	}
	// First sync the customer to Zoho Inventory
	if ( ! empty( $zoho_inventory_access_token ) ) {
		$zi_customer_class = new Sync_Order_Class();
		$zi_customer_class->zi_sync_customer_checkout( $order_id );
	}

	// Use WC Action Scheduler to sync the order to Zoho Inventory
	$existing_schedule = as_has_scheduled_action( 'sync_zi_order', array( $order_id ) );
	if ( ! $existing_schedule && ! empty( $zoho_inventory_access_token ) ) {
		as_schedule_single_action( time(), 'sync_zi_order', array( $order_id ) );
		// Set the transient flag to prevent multiple executions
		set_transient( 'your_thankyou_callback_executed_' . $order_id, true, 60 );
	}
}
