<?php

/**
 * All backend order sync related functions.
 *
 * @category Fulfillment
 * @package  commercebird
 * @author   Roadmap Studios <info@roadmapstudios.com>
 * @license  GNU General Public License v3.0
 * @link     https://commercebird.com
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use CommerceBird\Admin\Actions\Ajax\ZohoInventoryAjax;

/**
 * Loading admin order sync script.
 */
function cmbird_load_script() {
	if ( is_admin() ) {
		$screen = get_current_screen();
		if ( $screen->id === 'product' || $screen->id === 'shop_order' || $screen->id === 'woocommerce_page_wc-orders' ) {
			wp_enqueue_script( 'zoho-admin-order-ajax-script', CMBIRD_URL . 'admin/js/zoho_admin_order_ajax.js', array( 'jquery' ), CMBIRD_VERSION, true );
			wp_register_script( 'sweatAlert', CMBIRD_URL . 'admin/js/sweetalert.min.js', array( 'jquery' ), CMBIRD_VERSION, true );
			wp_enqueue_script( 'sweatAlert' );
		}
	}
}
add_action( 'admin_enqueue_scripts', 'cmbird_load_script' );

function cmbird_zoho_admin_metabox() {
	$zoho_inventory_access_token = get_option( 'zoho_inventory_access_token' );
	if ( empty( $zoho_inventory_access_token ) ) {
		return;
	}
	$screen = wc_get_container()->get( CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled()
		? 'woocommerce_page_wc-orders'
		: 'shop_order';

	add_meta_box(
		'zoho-admin-sync',
		'Sync Order to Zoho',
		'cmbird_admin_metabox_callback',
		$screen,
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'cmbird_zoho_admin_metabox' );

function cmbird_admin_metabox_callback( $post_or_order_object ) {
	$zoho_inventory_access_token = get_option( 'zoho_inventory_access_token' );
	if ( empty( $zoho_inventory_access_token ) ) {
		return;
	}
	$subscription = new ZohoInventoryAjax();
	$data = $subscription->get_subscription_data();
	// Flag to check if 'ZohoInventory' is found
	$found = false;
	// Loop through fee_lines and check for the 'name' key
	if ( isset( $data['fee_lines'] ) && is_array( $data['fee_lines'] ) ) {
		foreach ( $data['fee_lines'] as $fee_line ) {
			if ( isset( $fee_line['name'] ) && $fee_line['name'] === 'ZohoInventory' ) {
				$found = true;
				break;
			}
		}
	}
	$order = ( $post_or_order_object instanceof WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : wc_get_order( $post_or_order_object->get_id() );
	$userid = $order->get_user_id();
	$order_id = $order->get_id();
	if ( $found ) {
		$nonce_order = wp_create_nonce( 'zoho_admin_order_sync' );
		echo '<a href="javascript:void(0)" style="width:100%; text-align: center;"
		class="button save_order button-primary" onclick="zoho_admin_order_ajax(' . esc_attr( $order_id ) . ', \'' . esc_attr( $nonce_order ) . '\')">Sync Order</a>';
		if ( $userid ) {
			$nonce = wp_create_nonce( 'zi_customer_unmap_hook' );
			echo '<br><p style="color:red;">Click on below button if you are seeing the error "Billing AddressID passed is invalid"</p>';
			echo '<a href="javascript:void(0)" style="width:100%; text-align: center;"
			class="button customer_unmap" onclick="zoho_admin_customer_unmap(' . esc_attr( $order_id ) . ', \'' . esc_attr( $nonce ) . '\')">Unmap Customer</a>';
		}
	} else {
		echo '<p style="color:red;">Please activate the Zoho Inventory Integration</p>';
	}
}

/**
 * Bulk-action to sync orders from WooCommerce to Zoho
 * @param: $bulk_array
 */
add_filter( 'bulk_actions-woocommerce_page_wc-orders', 'cmbird_zi_sync_all_orders_to_zoho', 10, 1 );
function cmbird_zi_sync_all_orders_to_zoho( $actions ) {
	$actions['sync_order_to_zoho'] = __( 'Sync to Zoho', 'woocommerce' );
	return $actions;
}

add_filter( 'handle_bulk_actions-woocommerce_page_wc-orders', 'cmbird_zi_sync_all_orders_to_zoho_handler', 10, 3 );
function cmbird_zi_sync_all_orders_to_zoho_handler( $redirect, $action, $object_ids ) {
	if ( 'sync_order_to_zoho' !== $action ) {
		return $redirect; // Exit
	}
	// let's remove query args first
	$redirect = remove_query_arg( 'sync_order_to_zoho_done', $redirect );

	// do something for "Make Draft" bulk action
	if ( 'sync_order_to_zoho' === $action ) {

		foreach ( $object_ids as $post_id ) {
			$order_sync = new CMBIRD_Order_Sync_ZI();
			$order_sync->zi_order_sync( $post_id );
		}

		// do not forget to add query args to URL because we will show notices later
		$redirect = add_query_arg( 'sync_order_to_zoho_done', count( $object_ids ), $redirect );
	}

	return $redirect;
}

// output the message of bulk action
add_action( 'admin_notices', 'cmbird_sync_order_to_zoho_notices' );
function cmbird_sync_order_to_zoho_notices() {
	// verify nonce
	if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'bulk-orders' ) ) {
		return;
	}
	if ( ! empty( $_REQUEST['sync_order_to_zoho_done'] ) ) {
		echo '<div id="message" class="updated notice is-dismissible">
			<p>Orders Synced. If order is not synced, please click on Edit Order to see the API response.</p>
		</div>';
	}
}

/**
 * Add the product meta as line item meta to the Order Webhook Payload
 * @param: $payload
 * @param: $resource
 * @param: $resource_id
 * @param: $id
 * @return: $payload
 */
add_filter( 'woocommerce_webhook_payload', 'cmbird_modify_order_webhook_payload', 10, 4 );
function cmbird_modify_order_webhook_payload( $payload, $resource, $resource_id, $id ) {
	$webhook = wc_get_webhook( $id );

	// if webhook name contains 'CommerceBird Customers' using strpos, then add the customer_id to the payload
	$customers_webhook_name = 'CommerceBird Customers';
	$webhook_name = $webhook->get_name();
	if ( $webhook && strpos( $webhook_name, $customers_webhook_name ) !== false ) {
		// include eo_gl_account and eo_account_id in the payload
		$customer_id = (int) $payload['id'];
		$eo_gl_account = get_user_meta( $customer_id, 'eo_gl_account', true );
		$eo_account_id = get_user_meta( $customer_id, 'eo_account_id', true );
		$eo_contact_id = get_user_meta( $customer_id, 'eo_contact_id', true );
		if ( ! empty( $eo_gl_account ) ) {
			$payload['meta_data'][] = array(
				'key' => 'eo_gl_account',
				'value' => $eo_gl_account,
			);
		}
		if ( ! empty( $eo_account_id ) ) {
			$payload['meta_data'][] = array(
				'key' => 'eo_account_id',
				'value' => $eo_account_id,
			);
		}
		if ( ! empty( $eo_contact_id ) ) {
			$payload['meta_data'][] = array(
				'key' => 'eo_contact_id',
				'value' => $eo_contact_id,
			);
		}
		return $payload;
	}

	if ( $webhook && $webhook_name !== 'CommerceBird Orders' ) {
		return $payload;
	}

	$eo_account_id = '';
	$customer_id = (int) $payload['customer_id'];

	// All guest users will have the customer_id field set to 0
	if ( $customer_id > 0 ) {
		$eo_account_id = (string) get_user_meta( $customer_id, 'eo_account_id', true );
		if ( ! empty( $eo_account_id ) ) {
			$payload['meta_data'][] = array(
				'key' => 'eo_account_id',
				'value' => $eo_account_id,
			);
		}
	}
	// add eo_order_id to the meta_data array
	$order_object = wc_get_order( $resource_id );
	$eo_order_id = $order_object->get_meta( 'eo_order_id', true );
	if ( ! empty( $eo_order_id ) ) {
		$payload['meta_data'][] = array(
			'key' => 'eo_order_id',
			'value' => $eo_order_id,
		);
	}
	// Loop through line items in and add the eo_item_id to the line item
	foreach ( $payload['line_items'] as &$item ) {
		// Get the product ID associated with the line item
		$product_id = $item['product_id'];
		$variation_id = $item['variation_id'];
		// Get the product meta value based on the product ID and meta key
		if ( $variation_id ) {
			$eo_item_id = get_post_meta( $variation_id, 'eo_item_id', true );
		} else {
			$eo_item_id = get_post_meta( $product_id, 'eo_item_id', true );
		}
		// Add the product meta to the line item
		if ( ! empty( $eo_item_id ) ) {
			$item['meta'][] = array(
				'key' => 'eo_item_id',
				'value' => $eo_item_id,
			);
		}
	}

	return $payload;
}

/**
 * Prevent Webhook delivery execution when order gets updated too often per minute
 * @param bool $should_deliver
 * @param WC_Webhook $webhook
 * @param array $arg
 * @return bool
 */
add_filter( 'woocommerce_webhook_should_deliver', 'cmbird_skip_webhook_delivery', 10, 3 );
function cmbird_skip_webhook_delivery( $should_deliver, $webhook, $arg ) {

	$webhook_name_to_exclude = 'CommerceBird Orders';
	if ( $webhook->get_name() === $webhook_name_to_exclude ) {
		$order = wc_get_order( $arg );
		// check if order status is failed, pending, on-hold or cancelled
		$order_status = $order->get_status();
		if ( in_array( $order_status, array( 'failed', 'pending', 'on-hold', 'cancelled' ) ) ) {
			$should_deliver = false;
		}
		// check if order contains meta data for eo_order_id
		$eo_order_id = $order->get_meta( 'eo_order_id', true );
		if ( ! empty( $eo_order_id ) && 'refunded' !== $order_status ) {
			$should_deliver = false;
		}
		// check if order status is processing and webhook status is disabled or paused
		$webhook_status = $webhook->get_status();
		// also return false if webhoook status is disabled or paused
		if ( $webhook_status === 'disabled' || $webhook_status === 'paused' ) {
			$should_deliver = false;
		}
	}

	return $should_deliver; // Continue with normal webhook delivery
}

/**
 * Update Customer Meta Data when Order is updated
 * @param $order_id
 *
 */
function cmbird_update_customer_meta( $order_id ) {
	$order = wc_get_order( $order_id );
	$customer_id = $order->get_user_id();
	$eo_gl_account = $order->get_meta( 'glaccount', true );
	if ( ! empty( $eo_gl_account ) ) {
		// get the value of the glaccount meta, which is everything before : in the value
		$string = $eo_gl_account;
		$value = strstr( $string, ':', true );
		update_user_meta( $customer_id, 'eo_gl_account', trim( $value ) );
	}
}
add_action( 'woocommerce_update_order', 'cmbird_update_customer_meta' );
