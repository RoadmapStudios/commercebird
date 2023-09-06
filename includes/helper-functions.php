<?php

/**
 * Helper functions to ensure correct handling of Data being transferred via rest api
 */

function wooventory_correct_order_timestamp($object, $request, $is_creating)
{
    if ($object) {
        // Get the current date and time in the required format
        $current_datetime = current_time('Y-m-d H:i:s');

        $order_id = $object->get_id();

        // Update the post_date and post_date_gmt
        $order_data = array(
            'ID' => $order_id,
            'post_date' => $current_datetime,
            'post_date_gmt' => get_gmt_from_date($current_datetime),
        );

        // Update the order post
        wp_update_post($order_data);
    }
}
add_action('woocommerce_rest_insert_shop_order_object', 'wooventory_correct_order_timestamp', 10, 3);
