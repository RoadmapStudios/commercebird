<?php

/**
 * Helper functions to ensure correct handling of Data being transferred via rest api
 */

function wooventory_clear_product_cache($object, $request, $is_creating)
{
    if (!$is_creating) {
        $product_id = $object->get_id();
        wc_delete_product_transients($product_id);
    }
}
add_action('woocommerce_rest_insert_product_object', 'wooventory_clear_product_cache', 10, 3);
