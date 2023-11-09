<?php
define('WP_USE_THEMES', false);
if (isset($argv)) {
    foreach ($argv as $argument) {
        if (strpos($argument, 'host=') === 0) {
            $_SERVER['HTTP_HOST'] = substr($argument, 5);
        }
    }
}
require_once __DIR__ . '/../../../wp-load.php';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/class-client.php';
require_once __DIR__ . '/includes/classes/auth.php';
require_once __DIR__ . '/includes/classes/execute-curl-call-class.php';
require_once __DIR__ . '/includes/classes/class-common.php';
require_once __DIR__ . '/includes/classes/product-class.php';
require_once __DIR__ . '/includes/classes/import-image-class.php';

/**
 * Class ZohoSync
 *
 */
class ZohoToWoocommerce
{

    /**
     * Constructor
     */
    public function __construct()
    {
        global $zi_plugin_prod_id;
        global $zi_plugin_version;
        $wcam_lib = new WC_WooZo_Client(__FILE__, $zi_plugin_prod_id, $zi_plugin_version, 'plugin', 'https://roadmapstudios.com/', 'WooCommerce Zoho Inventory');
        $config = [
            'FromZohoZI' => [
                'OID' => get_option('zoho_inventory_oid'),
                'APIURL' => get_option('zoho_inventory_url'),
            ],
        ];
        if ($wcam_lib->get_api_key_status()) {
            $this->config = $config;
            $this->sync_simple_item_from_zoho();
        } // license check
        return;
    }

    /**
     * Function to check if terms already exists.
     */
    protected function zi_check_terms_exists($existingTerms, $term_id)
    {
        foreach ($existingTerms as $woo_existing_term) {
            if ($woo_existing_term->term_id === $term_id) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Syncing item from zoho to woocommerce
     *
     * @return void
     */
    protected function sync_simple_item_from_zoho()
    {
        $opt_category = get_option('zoho_item_category');
        if ($opt_category) {
            $opt_category = unserialize($opt_category);
        } else {
            $opt_category = array();
        }

        // Import item logs.
        $item_add_resp = array();
        $loop_completed = false; // Flag to track loop completion

        // Retrieve the last synced category index from the previous run
        $last_synced_category_index = get_option('last_synced_category_index', 0);

        // Slice the category array to start from the last synced category index
        $opt_category = array_slice($opt_category, $last_synced_category_index);

        foreach ($opt_category as $category_index => $category_id) {
            // get last backed up page number for particular category Id.
            // And start syncing from last synced page.
            // If no page number available, it will start from zero.
            $last_synced_page = get_option('simple_item_sync_page_cat_id_' . $category_id, 1);

            $response = $this->sync_item_recursively($last_synced_page, $category_id, '', 'sync');
            if (is_array($item_add_resp) && is_array($response)) {
                $item_add_resp = array_merge($item_add_resp, $response);
            }

            // Update the last synced category index in the options
            update_option('last_synced_category_index', $last_synced_category_index + $category_index + 1);
        }

        // Check if all categories have been processed
        $total_categories = count($opt_category);
        $processed_categories = $last_synced_category_index + $category_index + 1;

        if ($processed_categories >= $total_categories) {
            // Reset the last synced category index
            update_option('last_synced_category_index', 0);
            $loop_completed = true;
        }

        // Send log message to admin only if the loop completed
        if ($loop_completed) {
            $this->send_log_message_to_admin($item_add_resp, 'Log for manual sync of simple item', 'Simple item sync from zoho');
        }
    }

    /**
     * Function to add items recursively by cron job.
     *
     * @param [number] $page  - Page number for getting item with pagination.
     * @param [number] $category - Category id to get item of specific category.
     * @param [type]   $log_file - Log file with complete path to manage logs of request.
     * @param [string] $source - Source from where function is calling : 'cron'/'sync'.
     * @return mixed
     */
    protected function sync_item_recursively($page, $category, $log_file, $source)
    {
        global $wpdb;
        $zoho_inventory_oid = $this->config['FromZohoZI']['OID'];
        $zoho_inventory_url = $this->config['FromZohoZI']['APIURL'];

        $query = new WP_User_Query([
            'role' => 'Administrator',
            'count_total' => false,
        ]);
        $users = $query->get_results();
        if ($users) {
            $admin_author_id = $users[0]->ID;
        } else {
            $admin_author_id = '1';
        }

        // Keep backup of current syncing page of particular category.
        update_option('simple_item_sync_page_cat_id_' . $category, $page);

        // $fd = fopen(__DIR__ . '/bulk_sync.txt', 'a+');
        // fwrite($fd, PHP_EOL . 'Update : simple_item_sync_page_cat_id_' . $category . ':' . $page);

        // Check if item is of sync category.
        $url = $zoho_inventory_url . 'api/v1/items?organization_id=' . $zoho_inventory_oid . '&category_id=' . $category . '&page=' . $page . '&per_page=100&sort_column=last_modified_time';
        $json = $this->execute_get_curl_call($url);
        $code = $json->code;
        if ($code == '0' || $code == 0) {
            if (0 >= count($json->items)) {
                return;
            }
            $item_ids = [];
            $json = $this->execute_get_curl_call($url);
            $code = $json->code;
            if (0 == $code || '0' == $code) {
                foreach ($json->items as $arr) {
                    // fwrite($fd, PHP_EOL . '$arr : ' . print_r($arr, true));
                    // Code to skip sync with item already exists with same sku.
                    $item_id = $arr->item_id;
                    $prod_id = $this->get_product_by_sku($arr->sku);
                    $is_bundle = $arr->is_combo_product;
                    $is_grouped = $arr->group_id;
                    // Flag to enable or disable sync.
                    $allow_to_import = false;
                    // Check if product exists with same sku.
                    if ($prod_id) {
                        $allow_to_import = false;
                        $zi_item_id = get_post_meta($prod_id, 'zi_item_id', true);
                        if (empty($zi_item_id)) {
                            // Map existing item with zoho id.
                            update_post_meta($prod_id, 'zi_item_id', $arr->item_id);
                        }
                    }

                    if ('' == $is_bundle) {
                        // If product not exists normal behavior of item sync.
                        $allow_to_import = true;
                    }
                    if (!empty($is_grouped)) {
                        $allow_to_import = true;
                        $item_id = $is_grouped;
                    }

                    $pdt_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'zi_item_id' AND meta_value = '%s' LIMIT 1", $item_id));
                    if (empty($pdt_id) && $allow_to_import == true) {
                        // fwrite($fd, PHP_EOL . 'Product Import ');
                        if(empty($arr->group_id)) {
                            $pdt_id = $this->zi_product_to_woocommerce($arr, $admin_author_id);
                        }
                        // fwrite($fd, PHP_EOL . 'After Import Done : ');
                        if ($pdt_id) {
                            // fwrite($fd, PHP_EOL . 'Update post meta');
                            update_post_meta($pdt_id, 'zi_item_id', $arr->item_id);
                        }
                    }

                    if ($pdt_id && $allow_to_import == true) {
                        // Update ACF Fields
                        $this->sync_item_custom_fields($arr);

                        if (!empty($arr->category_name)) {
                            $term = get_term_by('name', $arr->category_name, 'product_cat');
                            $term_id = $term->term_id;
                            if (empty($term_id)) {
                                $term = wp_insert_term(
                                    $arr->category_name,
                                    'product_cat',
                                    array(
                                        'parent' => 0,
                                    )
                                );
                                $term_id = $term['term_id'];
                            }
                            if ($term_id) {
                                // update_post_meta($pdt_id, 'zi_category_id', $category);
                                // wp_set_object_terms($pdt_id, $term_id, 'product_cat');
                                $existingTerms = wp_get_object_terms($pdt_id, 'product_cat');
                                if ($existingTerms && count($existingTerms) > 0) {
                                    $isTermsExist = $this->zi_check_terms_exists($existingTerms, $term_id);
                                    if (!$isTermsExist) {
                                        update_post_meta($pdt_id, 'zi_category_id', $category);
                                        wp_add_object_terms($pdt_id, $term_id, 'product_cat');
                                    }
                                } else {
                                    update_post_meta($pdt_id, 'zi_category_id', $category);
                                    wp_set_object_terms($pdt_id, $term_id, 'product_cat');
                                }
                            }
                            // Remove "uncategorized" category if assigned
                            $uncategorized_term = get_term_by('slug', 'uncategorized', 'product_cat');
                            if ($uncategorized_term && has_term($uncategorized_term->term_id, 'product_cat', $pdt_id)) {
                                wp_remove_object_terms($pdt_id, $uncategorized_term->term_id, 'product_cat');
                            }
                        }

                        if (!empty($arr->brand)) {
                            wp_set_object_terms($pdt_id, $arr->brand, 'product_brand');
                        }
                        // sync via itemDetails as well for other keys
                        $item_ids[] = $arr->item_id;

                    } // end of wpdb post_id check
                }
            }
            $item_id_str = implode(",", $item_ids);
            // fwrite($fd, PHP_EOL . 'Before Bulk sync');
            $item_details_url = "{$zoho_inventory_url}api/v1/itemdetails?item_ids={$item_id_str}&organization_id={$zoho_inventory_oid}";
            $this->zi_item_bulk_sync($item_details_url);
            // fwrite($fd, PHP_EOL . 'After Bulk sync');
            foreach ($json->page_context as $key => $has_more) {
                if ($key === 'has_more_page') {
                    if ($has_more) {
                        $page++;
                        $this->sync_item_recursively($page, $category, $log_file, $source);
                    } else {
                        // If there is no more page to sync last backup page will be starting from 1.
                        // This we have used because in shared hosting only 1000 records are syncing.
                        update_option('simple_item_sync_page_cat_id_' . $category, 1);
                    }
                }
            }
        }
        // fclose($fd);
        // end logging
    }


    /**
     * Update or Create Custom Fields of Product
     *
     * @param $arr - item object coming in from simple item recursive
     * @return void
     */
    protected function sync_item_custom_fields($arr)
    {
        global $wpdb;
        $groupids = [];

        foreach ($arr as $key => $value) {
            if ($key == 'group_id') {
                if (!in_array($value, $groupids)) {
                    $group_id = $value;
                    $pdt_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'zi_item_id' AND meta_value = '%s' LIMIT 1", $group_id));
                    $groupids[] = $value;
                } else {
                    $pdt_id = 0;
                }
            } elseif ($key == 'item_id') {
                $item_id = $value;
                $pdt_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'zi_item_id' AND meta_value = '%s' LIMIT 1", $item_id));
            }
            $cmp = str_split($key, 3);
            if ((trim($cmp[0]) == 'cf_') && $pdt_id > 0) {
                update_post_meta($pdt_id, $key, $value);
            }
        }
    }

    /**
     * Function to retrieve item details and sync items.
     *
     * @param string $url - URL to get details.
     * @return mixed return true if data false if error.
     */
    protected function zi_item_bulk_sync($url)
    {
        // $fd = fopen(__DIR__ . '/zi_item_bulk_sync.txt', 'a+');
        // fwrite($fd, PHP_EOL . '$url :' . $url);
        global $wpdb;
        $json = $this->execute_get_curl_call($url);
        $code = $json->code;
        // $message = $json->message;
        // fwrite($fd, PHP_EOL . '$json->item : ' . print_r($json, true));
        if (0 == $code || '0' == $code) {

            foreach ($json->items as $arr) {
                $item_id = $arr->item_id;
                $item_tags_hash = $arr->custom_field_hash;
                $item_tags = $item_tags_hash->cf_tags;
                $is_bundle = $arr->is_combo_product;
                // fwrite($fd, PHP_EOL . '------------------------');

                $groupid = $arr->group_id;
                // find parent variable product
                $group_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'zi_item_id' AND meta_value = '%s' LIMIT 1", $groupid));
                if ($group_id) {
                    $item_id = $group_id;
                }

                $tbl = $wpdb->prefix;
                $pdt_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'zi_item_id' AND meta_value = '%s' LIMIT 1", $item_id));

                if (!empty($pdt_id)) {
                    $zi_disable_itemdescription_sync = get_option('zoho_disable_itemdescription_sync_status');
                    if (!empty($arr->description) && $zi_disable_itemdescription_sync != 'true') {
                        $wpdb->update($tbl . 'posts', array('post_excerpt' => $arr->description), array('ID' => $pdt_id), array('%s'), array('%d'));
                    }

                    if (!empty($arr->status)) {
                        $status = $arr->status == 'active' ? 'publish' : 'draft';
                        $wpdb->update($tbl . 'posts', array('post_status' => $status), array('ID' => $pdt_id), array('%s'), array('%d'));
                    }

                    $zi_disable_itemname_sync = get_option('zoho_disable_itemname_sync_status');
                    if (($zi_disable_itemname_sync != 'true') && !empty($arr->name)) {
                        $wpdb->update($tbl . 'posts', array('post_title' => $arr->name), array('ID' => $pdt_id), array('%s'), array('%d'));
                    }

                    if (!empty($arr->sku)) {
                        update_post_meta($pdt_id, '_sku', $arr->sku);
                    }

                    $zi_disable_itemprice_sync = get_option('zoho_disable_itemprice_sync_status');
                    if (!empty($arr->rate) && $zi_disable_itemprice_sync != 'true') {
                        $sale_price = get_post_meta($pdt_id, '_sale_price', true);
                        if (empty($sale_price)) {
                            update_post_meta($pdt_id, '_price', $arr->rate);
                            update_post_meta($pdt_id, '_regular_price', $arr->rate);
                            // fwrite($fd, PHP_EOL . 'Product Price updated of'.  $pdt_id);
                            if ($is_bundle) {
                                update_post_meta($pdt_id, '_wc_pb_base_price', $arr->rate);
                                update_post_meta($pdt_id, '_wc_pb_base_regular_price', $arr->rate);
                                update_post_meta($pdt_id, '_wc_sw_max_regular_price', $arr->rate);
                            }
                        }
                    }

                    if (!empty($item_tags)) {
                        $final_tags = explode(',', $item_tags);
                        wp_set_object_terms($pdt_id, $final_tags, 'product_tag');
                    }

                    $details = $arr->package_details;
                    update_post_meta($pdt_id, '_weight', floatval($details->weight));
                    update_post_meta($pdt_id, '_length', floatval($details->length));
                    update_post_meta($pdt_id, '_width', floatval($details->width));
                    update_post_meta($pdt_id, '_height', floatval($details->height));
                    update_post_meta($pdt_id, '_weight_unit', $details->weight_unit);
                    update_post_meta($pdt_id, '_dimension_unit', $details->dimension_unit);


                    // To check status of stock sync option.
                    $zi_stock_sync = get_option('zoho_stock_sync_status');
                    if ($zi_stock_sync != 'true') {
                        $accounting_stock = get_option('zoho_enable_accounting_stock_status');
                        // Sync from specific warehouse check
                        $zi_enable_warehousestock = get_option('zoho_enable_warehousestock_status');
                        $warehouse_id = get_option('zoho_warehouse_id');
                        $warehouses = $arr->warehouses;

                        if($zi_enable_warehousestock == true) {
                            foreach ($warehouses as $warehouse) {
                                if ($warehouse->warehouse_id === $warehouse_id) {
                                    if ($accounting_stock == 'true') {
                                        $stock = $warehouse->warehouse_available_for_sale_stock;
                                    } else {
                                        $stock = $warehouse->warehouse_actual_available_for_sale_stock;
                                    }
                                }
                            }
                        } else {
                            if ($accounting_stock == 'true') {
                                $stock = $arr->available_for_sale_stock;
                            } else {
                                $stock = $arr->actual_available_for_sale_stock;
                            }
                        }

                        if (!empty($stock)) {
                            $manage_stock = get_post_meta($pdt_id, "_manage_stock", true);
                            if ($stock > 0) {
                                update_post_meta($pdt_id, "_manage_stock", "yes");
                                update_post_meta($pdt_id, '_stock', number_format($stock, 0, '.', ''));
                                $status = 'instock';
                                update_post_meta($pdt_id, '_stock_status', wc_clean($status));
                                wp_set_post_terms($pdt_id, $status, 'product_visibility', true);
                            } else {
                                $backorder_status = get_post_meta($pdt_id, '_backorders', true);
                                $status = ($backorder_status === 'yes') ? 'onbackorder' : 'outofstock';
                                if ('yes' === $manage_stock) {
                                    update_post_meta($pdt_id, '_stock', number_format($stock, 0, '.', ''));
                                    update_post_meta($pdt_id, '_stock_status', wc_clean($status));
                                    wp_set_post_terms($pdt_id, $status, 'product_visibility', true);
                                }
                            }
                        }
                    }

                    // Sync Image
                    $zi_disable_itemimage_sync = get_option('zoho_disable_itemimage_sync_status');
                    if (!empty($arr->image_document_id) && $zi_disable_itemimage_sync != 'true') {
                        $imageClass = new ImageClass();
                        $imageClass->args_attach_image($arr->item_id, $arr->name, $pdt_id, $arr->image_name, '1');
                    }

                    if (!empty($arr->tax_id)) {
                        $zi_common_class = new ZI_CommonClass();
                        $woo_tax_class = $zi_common_class->get_woo_tax_class_from_zoho_tax_id($arr->tax_id);
                        update_post_meta($pdt_id, '_tax_class', $woo_tax_class);
                        update_post_meta($pdt_id, '_tax_status', 'taxable');
                    }
                    // Clear/refresh cache
                    wc_delete_product_transients($pdt_id); 
                }
            }
        } else {
            return false;
        }

        // fclose($fd);
        // Return if synced.
        return true;
    }

    /**
     * Function for adding new product from zoho to woocommerce.
     *
     * @param $prod - Product object for adding new product in woocommerce.
     * @param $user_id - Current Active user Id
     */
    protected function zi_product_to_woocommerce($prod, $user_id)
    {
        if ($prod->status != 'active') {
            return;
        }
        $post = array(
            'post_author' => $user_id,
            'post_content' => '',
            'post_status' => 'publish',
            'post_title' => $prod->name,
            'post_parent' => '',
            'post_type' => 'product',
        );
        $post_id = wp_insert_post($post);

        if (is_wp_error($post_id)) {
            // $err_message = $post_id->get_error_message();
            $post_id = '';
        }
        update_post_meta($post_id, '_manage_stock', 'yes');
        return $post_id;
    }

    /**
     * Execute curl call and return response as json.
     *
     * @param string $url - URL to execute.
     * @return object
     */
    protected function execute_get_curl_call($url)
    {

        // Sleep for .5 sec for each api calls
        usleep(500000);
        $handlefunction = new Classfunctions();
        $zoho_inventory_access_token = get_option('zoho_inventory_access_token');
        $zoho_inventory_refresh_token = get_option('zoho_inventory_refresh_token');
        $zoho_inventory_timestamp = get_option('zoho_inventory_timestamp');
        $current_time = time();

        if ($zoho_inventory_timestamp < $current_time) {

            $respoAtJs = $handlefunction->GetServiceZIRefreshToken($zoho_inventory_refresh_token);

            $zoho_inventory_access_token = $respoAtJs['access_token'];
            update_option('zoho_inventory_access_token', $respoAtJs['access_token']);
            update_option('zoho_inventory_timestamp', time() + $respoAtJs['expires_in']);
        }

        $curl = curl_init($url);
        curl_setopt_array(
            $curl,
            array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer " . $zoho_inventory_access_token,
                ),
            )
        );

        $result = curl_exec($curl);
        return json_decode($result);
    }

    /**
     * Log data
     *
     * @param mixed $data - Log date.
     * @return void
     */
    // protected function logData( $data ) {
    // echo '<pre>';
    // print_r( $data );
    // echo '</pre>';
    // }

    /**
     * Function to get product Id from sku
     * @param [string] $sku - of product import
     * @return product_id
     */
    protected function get_product_by_sku($sku)
    {

        global $wpdb;
        $product_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku));
        // if ( $product_id ) return new WC_Product( $product_id );
        return $product_id;
    }

    /**
     * Create response object based on data.
     *
     * @param mixed  $index_col - Index value error message.
     * @param string $message - Response message.
     * @return object
     */
    protected function zi_response_message($index_col, $message, $woo_id = '')
    {
        return (object) array(
            'resp_id' => $index_col,
            'message' => $message,
            'woo_prod_id' => $woo_id,
        );
    }

    /**
     * Send log message to admin
     *
     * @return void
     */
    protected function send_log_message_to_admin($sync_logs)
    {
        // $fd = fopen(__DIR__ . '/send_email.txt', 'w+');
        // fwrite($fd, PHP_EOL . 'Email Logs : ' . print_r($sync_logs, true));
        if ($sync_logs) {
            $email_body = "<p>Dear Admin,</p>";
            $email_body .= "<p>These below items could not be imported due to WordPress errors. Please create them manually in your store.</p>";
            $email_body .= "<ul>";
            $subject = "Zoho Inventory Cron Log";
            foreach ($sync_logs as $logs) {
                // $email_body .= "<li>https://inventory.zoho.com/item/12450696966 [variation]</li>";
                $email_body .= "<li>$logs</li>";
            }
            $email_body .= "</ul>";
            $this->error_log_api_email($subject, $email_body);
        }
        // fwrite($fd, PHP_EOL . 'Email Body : ' . $email_body);
        // fclose($fd);
        // exit();
    }

    /**
     * Zoho Api Error Email
     *
     * @param [string] $subject - Email subject.
     * @param [string] $message - Message.
     * @return void
     */
    protected function error_log_api_email($subject, $message)
    {
        $domain = get_site_url();

        if (strpos($domain, 'www.') !== false) {
            $domain = explode('www.', $domain)[1];
        } else {
            $domain = explode('//', $domain)[1];
        }

        $to = get_bloginfo('admin_email');

        $headers = 'From: info@' . $domain . "\r\n";
        $headers .= 'Reply-To: info@' . $domain . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

        $messages = '<html><body>';
        $messages .= '<p>' . $message . '</p>';
        $messages .= '<p>' . $to . '</p>';
        $messages .= '</body></html>';

        wp_mail($to, $subject, $message, $headers);
    }
}

// do_action( 'zoho_item_sync_from_zoho' );

// Initialize sync from zoho.
new ZohoToWoocommerce();
exit();
