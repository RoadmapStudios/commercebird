<?php

/**
 * All WooCommerce related functions.
 *
 * @category WooCommerce
 * @package  CommerceBird
 * @author   Fawad Tiemoerie <info@roadmapstudios.com>
 * @license  GNU General Public License v3.0
 * @link     https://commercebird.com
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Helper functions to ensure correct handling of Data being transferred via rest api
 */

function commercebird_clear_product_cache($object, $request, $is_creating)
{
    if (!$is_creating) {
        $product_id = $object->get_id();
        $zoho_inventory_access_token = get_option('zoho_inventory_access_token');
        if (!empty($zoho_inventory_access_token)) {
            $productHandler = new ProductClass();
            $productHandler->zi_product_sync($product_id);
        }
        wc_delete_product_transients($product_id);
    }
}
add_action('woocommerce_rest_insert_product_object', 'commercebird_clear_product_cache', 10, 3);



/**
 * Function to update the Contact in Zoho when customer updates address on frontend
 * @param $userid
 */
function zi_update_contact_via_accountpage($user_id)
{
    $zoho_inventory_access_token = get_option('zoho_inventory_access_token');
    if (!$zoho_inventory_access_token) {
        return;
    }
    $contactClassHandle = new ContactClass();
    $contactClassHandle->ContactUpdateFunction($user_id);
}
add_action('profile_update', 'zi_update_contact_via_accountpage');

/**
 * Function to be called by hook when new product is added in woocommerce.
 * This function sync item to zoho when admin adds / update any item in woocommerce.
 */
add_action('woocommerce_update_product', 'zi_product_sync_class', 10, 1);
add_action('wp_ajax_zi_product_sync_class', 'zi_product_sync_class');
function zi_product_sync_class($product_id)
{
    if (!is_admin()) {
        return;
    }
    if (!$product_id) {
        $product_id = $_POST['arg_product_data'];
    }
    $zi_product_sync = get_option('zoho_product_sync_status');
    $zoho_inventory_access_token = get_option('zoho_inventory_access_token');
    if ($zi_product_sync != 'true' && $zoho_inventory_access_token) {
        $productHandler = new ProductClass();
        $productHandler->zi_product_sync($product_id);
    }

    // TODO: Trigger Webhook if product is updated and webhook is enabled
    /*
    $webhookHandler = new WebhookClass();
    // Check if Webhook "CommerceBird Product" is enabled
    $webhook_status = $webhookHandler->get_status('CommerceBird Product');
    if (!$webhook_status) {
        // Log or handle the case where the webhook is not enabled
        return;
    }
    try {
        // Assuming trigger_webhook method might throw exceptions on failure
        $webhookHandler->trigger_webhook('CommerceBird Product', $product_id);
    } catch (Exception $e) {
        // Log or handle the exception
        echo 'Webhook trigger failed: ' . $e->getMessage();
    }
    */
}

/**
 * Bulk-action to sync products from WooCommerce to Zoho
 * @param: $bulk_array
 */
add_filter('bulk_actions-edit-product', 'zi_sync_all_items_to_zoho');
function zi_sync_all_items_to_zoho($bulk_array)
{
    $bulk_array['sync_item_to_zoho'] = 'Sync to Zoho';
    return $bulk_array;
}

add_filter('handle_bulk_actions-edit-product', 'zi_sync_all_items_to_zoho_handler', 10, 3);
function zi_sync_all_items_to_zoho_handler($redirect, $action, $object_ids)
{
    // let's remove query args first
    $redirect = remove_query_arg('sync_item_to_zoho_done', $redirect);

    // do something for "Make Draft" bulk action
    if ($action == 'sync_item_to_zoho') {

        foreach ($object_ids as $post_id) {
            $productHandler = new ProductClass();
            $productHandler->zi_product_sync($post_id);
        }

        // do not forget to add query args to URL because we will show notices later
        $redirect = add_query_arg('sync_item_to_zoho_done', count($object_ids), $redirect);

    }

    return $redirect;
}

// output the message of bulk action
add_action('admin_notices', 'sync_item_to_zoho_notices');
function sync_item_to_zoho_notices()
{
    if (!empty($_REQUEST['sync_item_to_zoho_done'])) {
        echo '<div id="message" class="updated notice is-dismissible">
			<p>Products Synced. If product is not synced, please click on Edit Product to see the API response.</p>
		</div>';
    }
}

/**
 * Function to be called by ajax hook when unmap button called.
 * This function remove zoho mapped id.
 */
add_action('wp_ajax_zi_product_unmap_hook', 'zi_product_unmap_hook');
function zi_product_unmap_hook($product_id)
{
    if (!$product_id) {
        $product_id = $_POST['product_id'];
    }

    if ($product_id) {
        $product = wc_get_product($product_id);
        // If this is variable items then unmap all of it's variations.
        if ($product->is_type('variable')) {
            $variations = $product->get_available_variations();
            if (isset($variations) && count($variations) > 0) {
                foreach ($variations as $child) {
                    delete_post_meta($child['variation_id'], 'zi_item_id');
                    delete_post_meta($child['variation_id'], 'zi_account_id');
                    delete_post_meta($child['variation_id'], 'zi_account_name');
                    delete_post_meta($child['variation_id'], 'zi_category_id');
                    delete_post_meta($child['variation_id'], 'zi_inventory_account_id');
                    delete_post_meta($child['variation_id'], 'zi_purchase_account_id');
                }
            }
        }
        delete_post_meta($product_id, 'zi_item_id');
        delete_post_meta($product_id, 'zi_account_id');
        delete_post_meta($product_id, 'zi_account_name');
        delete_post_meta($product_id, 'zi_category_id');
        delete_post_meta($product_id, 'zi_inventory_account_id');
        delete_post_meta($product_id, 'zi_purchase_account_id');
        // update message
        update_post_meta($product_id, 'zi_product_errmsg', 'Product is Unmapped');
    }
}

/**
 * Function to be called by ajax hook when unmap button called.
 * This function remove zoho mapped id.
 */
add_action('wp_ajax_zi_customer_unmap_hook', 'zi_customer_unmap_hook');
function zi_customer_unmap_hook($order_id)
{
    if (!$order_id) {
        $order_id = $_POST['order_id'];
    }

    $order = wc_get_order($order_id);
    $customer_id = $order->get_user_id();

    if ($customer_id) {
        delete_user_meta($customer_id, 'zi_contact_id');
        delete_user_meta($customer_id, 'zi_contact_persons_id');
        delete_user_meta($customer_id, 'zi_contactperson_id_0');
        delete_user_meta($customer_id, 'zi_contactperson_id_1');
        delete_user_meta($customer_id, 'zi_currency_code');
        delete_user_meta($customer_id, 'zi_currency_id');
        delete_user_meta($customer_id, 'zi_created_time');
        delete_user_meta($customer_id, 'zi_last_modified_time');
        delete_user_meta($customer_id, 'zi_primary_contact_id');
        delete_user_meta($customer_id, 'zi_billing_address_id');
        delete_user_meta($customer_id, 'zi_shipping_address_id');

        $order->add_order_note('Zoho Sync: Customer is now unmapped. Please try syncing the order again');
        $order->save();
    }
}

/**
 * Add WordPress Meta box to show sync response
 */
function zoho_product_metabox()
{
    $zoho_inventory_access_token = get_option('zoho_inventory_access_token');
    if (!$zoho_inventory_access_token) {
        return;
    }
    add_meta_box(
        'zoho-product-sync',
        __('Zoho Inventory'),
        'zoho_product_metabox_callback',
        'product',
        'side',
        'high'
    );
}
function zoho_product_metabox_callback($post)
{
    $response = get_post_meta($post->ID, 'zi_product_errmsg');
    echo 'API Response: ' . implode($response) . '<br>';
    echo '<br><a href="javascript:void(0)" style="width:100%; text-align: center;" class="button button-primary" onclick="zoho_admin_product_ajax(' . $post->ID . ')">Sync Product</a>';
    echo '<br><a href="javascript:void(0)" style="margin-top:10px; background:#b32d2e; border-color: #b32d2e; width:100%; text-align: center;" class="button button-primary" onclick="zoho_admin_unmap_product_ajax(' . $post->ID . ')">Unmap this Product</a>';
    $product = wc_get_product($post->ID);
    $product_type = $product->get_type();
    if ('variable' === $product_type || 'variable-subscription' === $product_type) {
        echo '<p class="howto" style="color:#b32d2e;"><strong>Important : </strong> Please ensure all variations have price and SKU</p>';
    }

}
add_action('add_meta_boxes', 'zoho_product_metabox');

//Add Zoho Item ID field on Product Edit page
add_action('woocommerce_product_options_general_product_data', 'zoho_item_id_field');
add_action('woocommerce_variation_options_pricing', 'zoho_item_id_variation_field', 10, 3);
function zoho_item_id_field()
{
    woocommerce_wp_text_input(
        array(
            'id' => 'zi_item_id',
            'label' => __('Zoho Item ID'),
            'class' => 'readonly',
            'desc_tip' => true,
            'description' => __('This is the Zoho Item ID of this product. You cannot change this'),
        )
    );
}
function zoho_item_id_variation_field($loop, $variation_data, $variation)
{
    woocommerce_wp_text_input(
        array(
            'id' => 'zi_item_id[' . $loop . ']',
            'class' => 'readonly',
            'label' => __('Zoho Item ID'),
            'value' => get_post_meta($variation->ID, 'zi_item_id', true),
            'desc_tip' => true,
            'description' => __('This is the Zoho Item ID of this product. You cannot change this'),
        )
    );
}

//Add Exact Item ID field on Product Edit page
add_action('woocommerce_product_options_general_product_data', 'exact_item_id_field');
add_action('woocommerce_variation_options_pricing', 'exact_item_id_variation_field', 10, 3);
function exact_item_id_field()
{
    woocommerce_wp_text_input(
        array(
            'id' => 'eo_item_id',
            'label' => __('Exact Item ID'),
            'class' => 'readonly',
            'desc_tip' => true,
            'description' => __('This is the Exact Item ID of this product. You cannot change this'),
        )
    );
}
function exact_item_id_variation_field( $loop, $variation_data, $variation )
{
    woocommerce_wp_text_input(
        array(
            'id' => 'eo_item_id[' . $loop . ']',
            'class' => 'readonly',
            'label' => __('Exact Item ID'),
            'value' => get_post_meta($variation->ID, 'eo_item_id', true),
            'desc_tip' => true,
            'description' => __('This is the Exact Item ID of this product. You cannot change this'),
        )
    );
}

// Block wc fields in My-Account page to prevent broken sync
add_filter('woocommerce_billing_fields', 'zoho_readonly_billing_account', 25, 1);
function zoho_readonly_billing_account($billing_fields)
{
    $zoho_inventory_access_token = get_option('zoho_inventory_access_token');
    // Only my account billing address for logged in users
    if (is_user_logged_in() && !empty($zoho_inventory_access_token)) {

        $readonly = ['readonly' => 'readonly'];

        $billing_fields['billing_first_name']['custom_attributes'] = $readonly;
        $billing_fields['billing_last_name']['custom_attributes'] = $readonly;
        $billing_fields['billing_email']['custom_attributes'] = $readonly;
    }
    return $billing_fields;
}

// If Product Bundle in cart, do not allow other types of products to be added in cart
/*
add_filter('woocommerce_add_to_cart_validation', 'zoho_add_to_cart_validation_callback', 10, 3);
function zoho_add_to_cart_validation_callback($passed, $product_id, $quantity)
{
    // HERE set your alert text message
    $message = __('Bundles should be purchased separately.', 'woocommerce');
    $product_ = wc_get_product($product_id);
    $product_type = $product_->get_type();
    if ($product_type == 'bundle') {
        if (!WC()->cart->is_empty()) {
            // Checking cart items if its not bundle
            foreach (WC()->cart->get_cart() as $cart_item) {
                $products_ = wc_get_product($cart_item['product_id']);
                $products_type = $products_->get_type();
                if ($products_type != 'bundle') {
                    $passed = false;
                    wc_add_notice($message, 'error');
                    break;
                } else {
                    break;
                }
            }
        }
    } else {
        if (!WC()->cart->is_empty()) {
            // Checking cart items if its not bundle
            foreach (WC()->cart->get_cart() as $cart_item) {
                $products_ = wc_get_product($cart_item['product_id']);
                $products_type = $products_->get_type();
                if ($products_type == 'bundle') {
                    $passed = false;
                    wc_add_notice($message, 'error');
                    break;
                } else {
                    break;
                }
            }
        }
    }

    return $passed;
}
*/

/**
 * Adds 'Zoho Sync' column header to 'Orders' page immediately after 'Total' column.
 *
 * @param string[] $columns
 * @return string[] $new_columns
 */
function zi_sync_column_orders_overview($columns)
{

    $new_columns = array();

    foreach ($columns as $column_name => $column_info) {

        $new_columns[$column_name] = $column_info;

        if ('order_total' === $column_name) {
            $new_columns['zoho_sync'] = __('Zoho Sync', 'my-textdomain');
        }
    }

    return $new_columns;
}
add_filter('manage_woocommerce_page_wc-orders_columns', 'zi_sync_column_orders_overview', 20);

/**
 * Adding Sync Status for Orders Column
 *
 * @param string $column Column name.
 * @param int    $order_id $order id.
 * @return void
 */
function zi_add_zoho_orders_content($column, $order_id)
{
    $zi_url = get_option('zoho_inventory_url');
    switch ($column) {
        case 'zoho_sync':
            // Get custom order meta data.
            $order = wc_get_order($order_id);
            $zi_order_id = $order->get_meta('zi_salesorder_id', true, 'edit');
            $url = $zi_url . 'app#/salesorders/' . $zi_order_id;
            if ($zi_order_id) {
                echo '<span class="dashicons dashicons-yes-alt" style="color:green;"></span><a href="' . esc_url($url) . '" target="_blank"> <span class="dashicons dashicons-external" style="color:green;"></span> </a>';
            } else {
                echo '<span class="dashicons dashicons-dismiss" style="color:red;"></span>';
            }
            unset($order);
            break;
    }
}
add_action('manage_woocommerce_page_wc-orders_custom_column', 'zi_add_zoho_orders_content', 20, 2);

/**
 * Adds 'Zoho Sync' column content.
 *
 * @param string[] $column name of column being displayed
 */
function zi_add_zoho_column_content($column)
{
    global $post;
    $post_type = get_post_type($post);

    if ('zoho_sync' === $column && 'product' === $post_type) {
        $product_id = $post->ID;
        $zi_product_id = get_post_meta($product_id, 'zi_item_id');
        if ($zi_product_id) {
            echo '<span class="dashicons dashicons-yes-alt" style="color:green;"></span>';
        } else {
            echo '<span class="dashicons dashicons-dismiss" style="color:red;"></span>';
        }
    }

}
add_action('manage_product_posts_custom_column', 'zi_add_zoho_column_content');

/**
 * Adds 'Zoho Sync' column header to 'Products' page.
 *
 * @param string[] $columns
 * @return string[] $new_columns
 */
function zi_sync_column_products_overview($columns)
{

    $new_columns = array();

    foreach ($columns as $column_name => $column_info) {

        $new_columns[$column_name] = $column_info;

        if ('product_cat' === $column_name) {
            $new_columns['zoho_sync'] = __('Zoho Sync', 'my-textdomain');
        }
    }

    return $new_columns;
}
add_filter('manage_edit-product_columns', 'zi_sync_column_products_overview', 20);

/**
 * Make 'Zoho Sync' column filterable.
 */
function zi_sync_column_filterable()
{
    global $typenow;

    if ($typenow === 'product') {
        $value = isset($_GET['zoho_sync_filter']) ? $_GET['zoho_sync_filter'] : '';

        echo '<select name="zoho_sync_filter">';
        echo '<option value="">Zoho Sync Filter</option>';

        // Count synced products
        $synced_count = new WP_Query(
            array(
                'post_type' => 'product',
                'meta_query' => array(
                    array(
                        'key' => 'zi_item_id',
                        'compare' => 'EXISTS',
                    ),
                ),
                'fields' => 'ids',
            )
        );
        $synced_count = $synced_count->found_posts;
        $synced_label = 'Synced';
        if ($synced_count > 0) {
            $synced_label .= ' (' . $synced_count . ')';
        }
        echo '<option value="synced" ' . selected($value, 'synced', false) . '>' . $synced_label . '</option>';

        // Count not synced products
        $not_synced_count = new WP_Query(
            array(
                'post_type' => 'product',
                'meta_query' => array(
                    array(
                        'key' => 'zi_item_id',
                        'compare' => 'NOT EXISTS',
                    ),
                ),
                'fields' => 'ids',
            )
        );
        $not_synced_count = $not_synced_count->found_posts;
        $not_synced_label = 'Not Synced';
        if ($not_synced_count > 0) {
            $not_synced_label .= ' (' . $not_synced_count . ')';
        }
        echo '<option value="not_synced" ' . selected($value, 'not_synced', false) . '>' . $not_synced_label . '</option>';

        echo '</select>';
    }
}
add_action('restrict_manage_posts', 'zi_sync_column_filterable');

/**
 * Modify the product query based on the filter.
 *
 * @param WP_Query $query The query object.
 */
function zi_sync_column_filter_query($query)
{
    global $typenow, $pagenow;

    if ($typenow === 'product' && $pagenow === 'edit.php' && isset($_GET['zoho_sync_filter']) && $_GET['zoho_sync_filter'] !== '') {
        $value = $_GET['zoho_sync_filter'];

        $meta_query = array();

        if ($value === 'synced') {
            $meta_query[] = array(
                'key' => 'zi_item_id',
                'compare' => 'EXISTS',
            );
        } elseif ($value === 'not_synced') {
            $meta_query[] = array(
                'relation' => 'OR',
                array(
                    'key' => 'zi_item_id',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key' => 'zi_item_id',
                    'value' => '',
                    'compare' => '=',
                ),
            );
        }

        $query->set('meta_query', $meta_query);
    }
}
add_action('pre_get_posts', 'zi_sync_column_filter_query');

/**
 * Prevent Webhook delivery execution when order gets updated too often per minute
 * @param bool $should_deliver
 * @param WC_Webhook $webhook
 * @param array $arg
 * @return bool
 */
add_filter('woocommerce_webhook_should_deliver', 'cm_skip_webhook_delivery', 10, 3);
function cm_skip_webhook_delivery($should_deliver, $webhook, $arg)
{
    //$fd = fopen(__DIR__ . '/should_skip_webhook.txt', 'a+');
    // log the order id
    // fwrite($fd, PHP_EOL . '$arg : ' . $arg);

    $webhook_name_to_exclude = 'CommerceBird Orders';
    if ($webhook->get_name() === $webhook_name_to_exclude) {
        // Check if the order status is not "processing" or "completed"
        $order = wc_get_order($arg);
        $order_status = $order->get_status();
        // fwrite($fd, PHP_EOL . '$order_status : ' . $order_status);

        if (in_array($order_status, array('failed', 'pending', 'on-hold'))) {
            // fwrite($fd, PHP_EOL . 'Skipping webhook delivery for order ' . $arg);
            return false; // Skip webhook delivery for this order
        }
    }
    // fclose($fd);

    return $should_deliver; // Continue with normal webhook delivery
}

/**
 * Change Action Scheduler default purge to 1 week
 * @return int
 */
function commercebird_action_scheduler_purge()
{
    return WEEK_IN_SECONDS;
}
add_filter('action_scheduler_retention_period', 'commercebird_action_scheduler_purge');
