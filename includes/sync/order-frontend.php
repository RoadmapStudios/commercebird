<?php

/**
 * All Frontend Order sync related functions.
 *
 * @package  WooZo Inventory
 * @category Zoho Integration
 * @author   Roadmap Studios
 * @link     https://wooventory.com
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sync the order from frontend to Zoho Inventory
 */
add_action('woocommerce_thankyou', 'zi_sync_frontend_order');
function zi_sync_frontend_order($order_id) {
    // Check if the transient flag is set
    if (get_transient('your_thankyou_callback_executed_' . $order_id)) {
        return;
    }
    // Use WC Action Scheduler to sync the order to Zoho Inventory
    $existing_schedule = as_has_scheduled_action('sync_zi_order', array($order_id));
    if (!$existing_schedule) {
        as_schedule_single_action(time(), 'sync_zi_order', array($order_id));
    }
    // Set the transient flag to prevent multiple executions
    set_transient('your_thankyou_callback_executed_' . $order_id, true, 60);
}


/**
 * Function to map customer on checkout before placing order
 */
add_action('template_redirect', 'zoho_contact_check');
function zoho_contact_check()
{

    if (is_user_logged_in() && is_checkout()) {

        $current_user = wp_get_current_user();
        $customer_id = intval(get_user_meta($current_user->ID, 'zi_contact_id', true));
        $customer_email = $current_user->user_email;
        $zoho_inventory_oid = get_option('zoho_inventory_oid');
        $zoho_inventory_url = get_option('zoho_inventory_url');

        if ($customer_id == 0) {
            $url = $zoho_inventory_url . 'api/v1/contacts?organization_id=' . $zoho_inventory_oid . '&email=' . $customer_email;

            $executeCurlCallHandle = new ExecutecallClass();
            $json = $executeCurlCallHandle->ExecuteCurlCallGet($url);
            //echo '<pre>'; print_r($json);
            $code = $json->code;
            if ($code == 0) {
                if (count($json->contacts) > 2) {
                    foreach ($json->contacts as $val) {
                        if ($val->contact_id > 0) {
                            update_user_meta($current_user->ID, 'zi_contact_id', $val->contact_id);
                        }
                    }
                }
            }
        }
    }
}


