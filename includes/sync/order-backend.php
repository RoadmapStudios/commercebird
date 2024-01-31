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

if (!defined('ABSPATH')) {
    exit;
}

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;


/**
 * Loading admin order sync script.
 */
function load_script()
{
    if (is_admin()) {
        $screen = get_current_screen();
        if ($screen->id === 'product' || $screen->id === 'shop_order' || $screen->id === 'woocommerce_page_wc-orders') {
            wp_enqueue_script('zoho-admin-order-ajax-script', RMS_DIR_URL . 'admin/js/zoho_admin_order_ajax.js', array('jquery'), RMS_VERSION, true);
            wp_register_script('sweatAlert', 'https://unpkg.com/sweetalert/dist/sweetalert.min.js', array('jquery'), RMS_VERSION, true);
            wp_enqueue_script('sweatAlert');
        }
    }
}
add_action('admin_enqueue_scripts', 'load_script');

function zoho_admin_metabox()
{
    $zoho_inventory_access_token = get_option('zoho_inventory_access_token');
    if (!$zoho_inventory_access_token) {
        return;
    }
    $screen = wc_get_container()->get(CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled()
        ? wc_get_page_screen_id('shop-order')
        : 'shop_order';

    add_meta_box(
        'zoho-admin-sync',
        'Sync Order to Zoho',
        'zoho_admin_metabox_callback',
        $screen,
        'side',
        'high'
    );
}
function zoho_admin_metabox_callback($post_or_order_object)
{
    global $wcam_lib;
    $order = ($post_or_order_object instanceof WP_Post) ? wc_get_order($post_or_order_object->ID) : wc_get_order($post_or_order_object->get_id());
    $userid = $order->get_user_id();
    if ($wcam_lib->get_api_key_status()) {
        echo '<a href="javascript:void(0)" style="width:100%; text-align: center;" class="button save_order button-primary" onclick="zoho_admin_order_ajax(' . $order->get_id() . ')">Sync Order</a>';
        if ($userid) {
            echo '<br><p style="color:red;">Click on below button if you are seeing the error "Billing AddressID passed is invalid"</p>';
            echo '<a href="javascript:void(0)" style="width:100%; text-align: center;" class="button customer_unmap" onclick="zoho_admin_customer_unmap(' . $order->get_id() . ')">Unmap Customer</a>';
        }
    } else {
        echo '<p style="color:red;">Please activate the license to sync this order</p>';
    }
}
add_action('add_meta_boxes', 'zoho_admin_metabox');

/**
 * Bulk-action to sync orders from WooCommerce to Zoho
 * @param: $bulk_array
 */
add_filter('bulk_actions-woocommerce_page_wc-orders', 'zi_sync_all_orders_to_zoho', 10, 1);
function zi_sync_all_orders_to_zoho($actions)
{
    $actions['sync_order_to_zoho'] = __('Sync to Zoho', 'woocommerce');
    return $actions;
}

add_filter('handle_bulk_actions-woocommerce_page_wc-orders', 'zi_sync_all_orders_to_zoho_handler', 10, 3);
function zi_sync_all_orders_to_zoho_handler($redirect, $action, $object_ids)
{
    if ($action !== 'sync_order_to_zoho')
        return $redirect; // Exit
    // let's remove query args first
    $redirect = remove_query_arg('sync_order_to_zoho_done', $redirect);

    // do something for "Make Draft" bulk action
    if ($action == 'sync_order_to_zoho') {

        foreach ($object_ids as $post_id) {
            $order_sync = new Sync_Order_Class();
            $order_sync->zi_order_sync($post_id);
        }

        // do not forget to add query args to URL because we will show notices later
        $redirect = add_query_arg('sync_order_to_zoho_done', count($object_ids), $redirect);
    }

    return $redirect;
}

// output the message of bulk action
add_action('admin_notices', 'sync_order_to_zoho_notices');
function sync_order_to_zoho_notices()
{
    if (!empty($_REQUEST['sync_order_to_zoho_done'])) {
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
add_filter('woocommerce_webhook_payload', 'cm_modify_order_webhook_payload', 10, 4);
function cm_modify_order_webhook_payload($payload, $resource, $resource_id, $id)
{
    if ($resource !== 'order') {
        return $payload;
    }

    $eo_account_id = '';
    $customer_id = (int) $payload['customer_id'];

    // All guest users will have the customer_id field set to 0
    if ($customer_id > 0) {
        $eo_account_id = (string) get_user_meta($customer_id, 'eo_account_id', true);
        if(!empty($eo_account_id)) {
            $payload['meta_data'][] = array(
                'key' => 'eo_account_id',
                'value' => $eo_account_id,
            );
        }
    }
    // Loop through line items in and add the eo_item_id to the line item
    foreach ($payload['line_items'] as &$item) {
        // Get the product ID associated with the line item
        $product_id = $item['product_id'];
        // Get the product meta value based on the product ID and meta key
        $eo_item_id = get_post_meta($product_id, 'eo_item_id', true);
        // Add the product meta to the line item
        if (!empty($eo_item_id)) {
            $item['meta'][] = array(
                'key' => 'eo_item_id',
                'value' => $eo_item_id,
            );
        }
    }

    return $payload;
}

