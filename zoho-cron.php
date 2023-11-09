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

/**
 * Class ZohoSync
 *
 */
class ZohoSync
{

    /**
     * Constructor
     */
    public function __construct()
    {
        global $zi_plugin_prod_id;
        $this->sync_simple_item_to_zoho();
        // Sync composite item only if pro version.
        if (20832 === $zi_plugin_prod_id) {
            $this->syncCompositeItem();
        }
    }

    protected function syncCompositeItem()
    {
        if (in_array('woocommerce-product-bundles/woocommerce-product-bundles.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            $term_obj = get_term_by('name', 'bundle', 'product_type');
            $bundle_term_id = $term_obj->term_id;
            $irgs = array(
                'post_type' => array('product'),
                'posts_per_page' => '-1',
                'post_status' => 'publish',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_type',
                        'field' => 'term_id',
                        'terms' => $bundle_term_id,
                    ),
                ),
            );
            $my_query = new WP_Query($irgs);
            $bundled_product = $my_query->posts;
            // $this->logData( $bundled_product );
            if (count($bundled_product) > 0) {
                $table_log = '';
                foreach ($bundled_product as $prod) {
                    $bundle_childs = WC_PB_DB::query_bundled_items(
                        array(
                            'return' => 'id=>product_id', // 'id=>product_id'
                            'bundle_id' => array($prod->ID),
                        )
                    );
                    // $this->logData( $bundle_childs );
                    // Flag to allow sync if all child are synced with zoho.
                    $allow_sync = true;
                    if (count($bundle_childs) > 0) {
                        foreach ($bundle_childs as $child_item_id) {
                            $zoho_item_id = get_post_meta($child_item_id, 'zi_item_id', true);
                            if (empty($zoho_item_id)) {
                                $allow_sync = false;
                                break;
                            }
                        }
                    }
                    if ($allow_sync) {
                        $logs = $this->syncBundleItem($prod);
                        $table_log .= "<tr><td>$logs->code</td><td>$logs->message</td><td>$logs->woo_prod_id</td></tr>";
                    }
                }
                $message = 'Dear admin, we would like to notify you that all composite items have been synced to Zoho Inventory.';
                $this->send_log_message_to_admin($table_log, 'Bundled items are synced to Zoho', $message);
            }
        } //end of check for bundle plugin
    }

    /**
     * Sync bundle item from woocmmerce to zoho
     *
     * Override this method to perform any actions required on each
     * queue item. Return the modified item for further processing
     * in the next pass through. Or, return false to remove the
     * item from the queue.
     *
     * @param mixed $item Queue  bundle item to iterate over.
     *
     * @return mixed
     */
    protected function syncBundleItem($item)
    {
        if (in_array('woocommerce-product-bundles/woocommerce-product-bundles.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            $response = (object) array(
                'code' => 400,
                'status' => false,
                'message' => 'SUCCESS',
                'woo_prod_id' => '',
            );
            // Actions to perform
            if ($item && !$item->ID) {
                $response->message = 'ID not found';
                return $response;
            }
            $response->woo_prod_id = $item->ID;
            $child_items = $this->getBundleChildToSync($item->ID);
            $image = wp_get_attachment_image_src(get_post_thumbnail_id($item->ID), 'single-post-thumbnail');
            $val['priceR'] = get_post_meta($item->ID, '_regular_price', true);
            $val['priceS'] = get_post_meta($item->ID, '_sale_price', true);
            $val['image'] = $image[0];
            $val['ext'] = pathinfo($image[0], PATHINFO_EXTENSION);

            $proid = $item->ID;
            $item_name = $item->post_title;
            $name = preg_replace('/[^A-Za-z0-9\-]/', ' ', $item_name);
            $sku = $item->_sku;
            $product = wc_get_product($proid);
            $product_type = 'goods';
            $item_type = 'inventory';
            $description = '';
            $tax_rates = WC_Tax::get_base_tax_rates($product->get_tax_class());
            $tax_id_key = '';
            foreach ($tax_rates as $tax_key => $tax_value) {
                $tax_id_key = $tax_key;
                break;
            }
            $tax_option = get_option('zoho_inventory_tax_rate_' . $tax_id_key);
            $tax_id = explode('##', $tax_option)[0];
            if (!empty($tax_rates)) {
                $tax_rate = reset($tax_rates);
            }
            $in_stock = ($product->get_stock_quantity()) ? $product->get_stock_quantity() : 0;
            if ($val['priceS']) {
                $rate = $val['priceS'];
            } else {
                $rate = $val['priceR'];
            }

            $image = $val['image'];
            $ext = $val['ext'];

            $pdt1 = '{"name" : "' . $name . '","mapped_items":' . json_encode($child_items) . ', "product_type" : "' . $product_type . '","description" : "' . $description . '","tax_id" : "' . $tax_id . '","initial_stock" : "' . $in_stock . '","initial_stock_rate" : "' . $in_stock . '","rate" : "' . $rate . '","sku" : "' . $sku . '","image_name" : "' . $image . '","image_type" : "' . $ext . '", "item_type" : "' . $item_type . '"';
            // If zoho category id is not mapped to product, then assign mapped product category with zoho.
            $zi_category_id = $this->get_zoho_id_by_product_id($proid);

            if ($zi_category_id) {
                $pdt1 .= ',"category_id" : "' . $zi_category_id . '"';
            }
            // Dimension data append to update call.
            $product_dimensions = $this->getProductdimensions($product);
            if ($product_dimensions) {
                $pdt1 .= ',"package_details" : ' . json_encode($product_dimensions);
            }
            $pdt1 .= '}';

            $zoho_inventory_oid = get_option('zoho_inventory_oid');
            $zoho_inventory_url = get_option('zoho_inventory_url');
            $data = array(
                'JSONString' => $pdt1,
                'organization_id' => $zoho_inventory_oid,
            );
            $zoho_item_id = get_post_meta($proid, 'zi_item_id', true);
            if (!empty($zoho_item_id)) {
                $update_data_obj = (object) array(
                    'name' => $name,
                    'mapped_items' => $child_items,
                    'rate' => $rate,
                    'tax_id' => $tax_id,
                    'description' => $description,
                );
                $zi_category_id = $this->get_zoho_id_by_product_id($proid);
                if ($zi_category_id) {
                    // If zoho category id mapped to woo product then pass same category id for api call.
                    $update_data_obj->category_id = $zi_category_id;
                } else {
                    // If zoho category id is not mapped to product, then assign mapped product category with zoho.
                    $zi_category_id = get_post_meta($proid, 'zi_category_id', true);
                    if ($zi_category_id) {
                        $update_data_obj->category_id = $zi_category_id;
                    }
                }
                // Dimentiions data append to composite item update call.
                if ($product_dimensions) {
                    $update_data_obj->package_details = $product_dimensions;
                }
                $update_resp = $this->update_composite_item_to_zoho($zoho_item_id, json_encode($update_data_obj));
                // $response->code = $update_resp->code;
                // $response->message = $update_resp->message;
            } else {

                $handlefunction = new Classfunctions;

                $zoho_inventory_access_token = get_option('zoho_inventory_access_token');
                $zoho_inventory_refresh_token = get_option('zoho_inventory_refresh_token');
                $zoho_inventory_timestamp = get_option('zoho_inventory_timestamp');
                $current_time = strtotime(date('Y-m-d H:i:s'));

                if ($zoho_inventory_timestamp < $current_time) {

                    $respoAtJs = $handlefunction->GetServiceZIRefreshToken($zoho_inventory_refresh_token);

                    $zoho_inventory_access_token = $respoAtJs['access_token'];
                    update_option('zoho_inventory_access_token', $respoAtJs['access_token']);
                    update_option('zoho_inventory_timestamp', strtotime(date('Y-m-d H:i:s')) + $respoAtJs['expires_in']);

                }

                $url = $zoho_inventory_url . 'api/v1/compositeitems';
                $curl = curl_init($url);
                $headers = array('Content-Type:multipart/form-data');
                curl_setopt_array(
                    $curl,
                    array(
                        CURLOPT_POST => 1,
                        CURLOPT_POSTFIELDS => $data,
                        CURLOPT_HTTPHEADER => array(
                            "Authorization: Bearer " . $zoho_inventory_access_token,
                            "Content-Type:multipart/form-data",
                        ),
                        CURLOPT_RETURNTRANSFER => true,
                    )
                );
				usleep(500000);
                $result = curl_exec($curl);

                $json = json_decode($result);
                $code = $json->code;
                if ($code == '0' || 0 == $code) {
                    $response->code = 200;
                    $this->add_item_meta_data($json->composite_item, $proid);
                } else {
                    // Checking if item with same sku is available in zoho inventory.
                    if ($code == '1001' || $code == 1001) {
                        $url = $zoho_inventory_url . 'api/v1/compositeitems?organization_id=' . $zoho_inventory_oid . '&search_text=' . $sku;
                        $json = $this->execute_get_curl_call($url);
                        $code = $json->code;

                        if ($code == '0' || $code == 0) {
                            $composite_item_id = $json->composite_items[0]->composite_item_id;
                            $url = "{$zoho_inventory_url}api/v1/compositeitems/$composite_item_id?organization_id=$zoho_inventory_oid";
                            $json = $this->execute_get_curl_call($url);
                            if ($json->code == '0' || 0 == $json->code) {
                                $response->code = 200;
                                $metadata = (object) array(
                                    'composite_item_id' => $json->composite_item->composite_item_id,
                                    'purchase_account_id' => $json->composite_item->purchase_account_id,
                                    'account_id' => $json->composite_item->account_id,
                                    'account_name' => $json->composite_item->account_name,
                                    'inventory_account_id' => $json->composite_item->inventory_account_id,
                                );
                                $this->add_item_meta_data($metadata, $proid);
                            }
                        }
                    }
                }
                $response->code = $json->code;
                $response->message = $json->message;
            }
            return $response;
        } // end of check bundles plugin
    }

    /**
     * Execute curl call and return response as json.
     *
     * @param string $url - URL to execute.
     * @return object
     */
    protected function execute_get_curl_call($url)
    {

        $handlefunction = new Classfunctions;

        $zoho_inventory_oid = get_option('zoho_inventory_oid');
        $zoho_inventory_url = get_option('zoho_inventory_url');
        $zoho_inventory_access_token = get_option('zoho_inventory_access_token');
        $zoho_inventory_refresh_token = get_option('zoho_inventory_refresh_token');
        $zoho_inventory_timestamp = get_option('zoho_inventory_timestamp');
        $current_time = strtotime(date('Y-m-d H:i:s'));

        if ($zoho_inventory_timestamp < $current_time) {

            $respoAtJs = $handlefunction->GetServiceZIRefreshToken($zoho_inventory_refresh_token);

            $zoho_inventory_access_token = $respoAtJs['access_token'];
            update_option('zoho_inventory_access_token', $respoAtJs['access_token']);
            update_option('zoho_inventory_timestamp', strtotime(date('Y-m-d H:i:s')) + $respoAtJs['expires_in']);

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
		usleep(500000);
        $result = curl_exec($curl);
        return json_decode($result);
    }

    /**
     * Update zoho composite item
     *
     * @param string $comp_item_id - Composite item id to update.
     * @param string $item_data - JSON string of composite item data.
     * @return string - zoho response message.
     */
    protected function update_composite_item_to_zoho($comp_item_id, $item_data)
    {
        if (in_array('woocommerce-product-bundles/woocommerce-product-bundles.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            $handlefunction = new Classfunctions;

            $zoho_inventory_oid = get_option('zoho_inventory_oid');
            $zoho_inventory_url = get_option('zoho_inventory_url');
            $zoho_inventory_access_token = get_option('zoho_inventory_access_token');
            $zoho_inventory_refresh_token = get_option('zoho_inventory_refresh_token');
            $zoho_inventory_timestamp = get_option('zoho_inventory_timestamp');
            $current_time = strtotime(date('Y-m-d H:i:s'));

            if ($zoho_inventory_timestamp < $current_time) {

                $respoAtJs = $handlefunction->GetServiceZIRefreshToken($zoho_inventory_refresh_token);

                $zoho_inventory_access_token = $respoAtJs['access_token'];
                update_option('zoho_inventory_access_token', $respoAtJs['access_token']);
                update_option('zoho_inventory_timestamp', strtotime(date('Y-m-d H:i:s')) + $respoAtJs['expires_in']);

            }
            $url = $zoho_inventory_url . 'api/v1/compositeitems/' . $comp_item_id;
            $data = array(
                'JSONString' => $item_data,
                'organization_id' => $zoho_inventory_oid,
            );
            $curl = curl_init($url);

            curl_setopt_array(
                $curl,
                array(
                    CURLOPT_POSTFIELDS => $data,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => array(
                        "Authorization: Bearer " . $zoho_inventory_access_token,
                    ),
                )
            );

            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
			// Sleep for .5 sec for each api calls
			usleep(500000);
            $put = curl_exec($curl);
            $json = json_decode($put);
            return $json;
        } // end of check bundles plugin
    }

    /**
     * Get child product of for given product id.
     *
     * @param number $bundle_id - Bundle id for given product
     * @return mixed false on failed else return success.
     */
    protected function getBundleChildToSync($bundle_id)
    {
        if (in_array('woocommerce-product-bundles/woocommerce-product-bundles.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            $bundle_childs = WC_PB_DB::query_bundled_items(
                array(
                    'return' => 'id=>product_id',
                    'bundle_id' => array($bundle_id),
                )
            );
            $child_array = array();
            foreach ($bundle_childs as $child_id) {
                $meta_value = $this->zi_get_bundle_item_meta($child_id, $bundle_id, 'quantity_max');
                $zi_child_id = get_post_meta($child_id, 'zi_item_id', true);
                $json_child = (object) array(
                    'item_id' => $zi_child_id,
                    'quantity' => $meta_value[0]->meta_value,
                );
                array_push($child_array, $json_child);
            }
            return $child_array;
        } // end check of bundles plugin
    }

    /**
     * Get array of metadata.
     *
     * @param number $bundle_item_id bundle item id.
     * @return mixed metadata.
     */
    private function zi_get_bundle_item_meta($bundle_item_id, $bundle_id, $meta_key = '')
    {
        if (in_array('woocommerce-product-bundles/woocommerce-product-bundles.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            global $wpdb;
            $table_meta = $wpdb->prefix . 'woocommerce_bundled_itemmeta';
            $table_item = $wpdb->prefix . 'woocommerce_bundled_items';
            if ('' !== $meta_key) {
                $meta_key = "AND meta_key='$meta_key'";
            }
            $metadata = $wpdb->get_results("SELECT meta_key, meta_value FROM $table_meta, $table_item WHERE $table_meta.bundled_item_id = $table_item.bundled_item_id AND product_id=$bundle_item_id AND bundle_id = $bundle_id $meta_key");

            if (count($metadata) > 0) {
                return $metadata;
            } else {
                false;
            }
        } //end of check bundles plugin
    }

    /**
     * Get simple item from woocommerce and send to zoho.
     *
     * @return void
     */
    protected function sync_simple_item_to_zoho()
    {
        $irgs = array(
            'post_type' => array('product'),
            'posts_per_page' => '-1',
            'post_status' => 'publish',
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_type',
                    'field' => 'slug',
                    'terms' => 'simple',
                ),
            ),
        );
        $query = new WP_Query($irgs);
        $simple_product = $query->posts;
        $table_log = '';
        $message = 'Dear admin, we would like to notify you that all items have been synced to Zoho Inventory.';
        foreach ($simple_product as $item) {
            $logs = $this->simple_item_to_zoho($item);
            $table_log .= "<tr><td>$logs->code</td><td>$logs->message</td><td>$logs->woo_prod_id</td></tr>";
        }
        $this->send_log_message_to_admin($table_log, 'Items are synced to Zoho', $message);
    }

    /**
     * Function to get product dimensions.
     *
     * @param object $product - Product objects.
     * @return mixed - array of dimensions or false.
     */
    protected function getProductdimensions($product)
    {
            $dimensions = (object) array();
            $dimensions->length = $product->get_length();
            $dimensions->width = $product->get_width();
            $dimensions->height = $product->get_height();
            $dimensions->weight = $product->get_weight();
            return $dimensions;
    }

    /**
     * Sync Simple item to zoho.
     *
     * Override this method to perform any actions required on each
     * queue item. Return the modified item for further processing
     * in the next pass through. Or, return false to remove the
     * item from the queue.
     *
     * @param mixed $item simple item object.
     *
     * @return mixed
     */
    protected function simple_item_to_zoho($item)
    {
		// $fd = fopen(__DIR__.'/zoho-cron.txt','w+');

        $response = (object) array(
            'code' => 400,
            'status' => false,
            'message' => 'SUCCESS',
            'woo_prod_id' => '',
        );

        if ($item && !$item->ID) {
            $response->message = 'Item ID not found';
            return $response;
        }
        $proid = $item->ID;
        $product = wc_get_product($proid);
        if ($product->is_type('variable')) {
            $attributes = get_post_meta($proid, '_default_attributes', true);
            if ($attributes) {
                return;
                //$proid = $this->zi_find_matching_product_variation( $product, $attributes );
            }
        } else {
            // Simple product
        }
        $response->woo_prod_id = $item->ID;
        // $image = wp_get_attachment_image_src(get_post_thumbnail_id($proid), 'single-post-thumbnail');
        $val['priceR'] = get_post_meta($proid, '_regular_price', true);
        $val['priceS'] = get_post_meta($proid, '_sale_price', true);

        // Imp: Reset $proid id with item id (override if product is variable product).
        $proid = $item->ID;
        // $val['image'] = $image[0];
        // $val['ext'] = pathinfo($image[0], PATHINFO_EXTENSION);
        $item_name = $item->post_title;
        $name = preg_replace('/[^A-Za-z0-9\-]/', ' ', $item_name);
        $sku = $item->_sku;
        $product_type = 'goods';
        $item_type = 'inventory';
        $description = '';
        $tax_rates = WC_Tax::get_base_tax_rates($product->get_tax_class());
        $tax_id_key = '';
        foreach ($tax_rates as $tax_key => $tax_value) {
            $tax_id_key = $tax_key;
            break;
        }
        $tax_option = get_option('zoho_inventory_tax_rate_' . $tax_id_key);
        $tax_id = explode('##', $tax_option)[0];
        if (!empty($tax_rates)) {
            $tax_rate = reset($tax_rates);
        }
        $in_stock = ($product->get_stock_quantity()) ? $product->get_stock_quantity() : 0;
        if ($val['priceS']) {
            $rate = $val['priceS'];
        } else {
            $rate = $val['priceR'];
        }

        // $image = $val['image'];
        // $ext = $val['ext'];

        $pdt1 = '{"name" : "' . $name . '", "product_type" : "' . $product_type . '","description" : "' . $description . '","tax_id" : "' . $tax_id . '","initial_stock" : "' . $in_stock . '","initial_stock_rate" : "' . $in_stock . '","rate" : "' . $rate . '","sku" : "' . $sku . '","item_type" : "' . $item_type . '"';
        // If zoho category id is not mapped to product, then assign mapped product category with zoho.
        $zi_category_id = $this->get_zoho_id_by_product_id($proid);

        if ($zi_category_id) {
            $pdt1 .= ',"category_id" : "' . $zi_category_id . '"';
        }
        // Dimensions data.
        $product_dimensions = $this->getProductdimensions($product);
        if ($product_dimensions) {
            $pdt1 .= ',"package_details" : ' . json_encode($product_dimensions);
        }
        $pdt1 .= '}';

        $zoho_inventory_oid = get_option('zoho_inventory_oid');
        $zoho_inventory_url = get_option('zoho_inventory_url');

        $data = array(
            'JSONString' => $pdt1,
            'organization_id' => $zoho_inventory_oid,
        );
        $zoho_itemId = get_post_meta($proid, 'zi_item_id', true);

        if (!empty($zoho_itemId)) {
            $pdt3 = '"rate" : "' . $rate . '",';
            $pdt3 .= '"tax_id" : "' . $tax_id . '",';
            $pdt3 .= '"description" : "' . $description . '",';
            $pdt3 .= '"name" : "' . $name . '"';
            $zi_category_id = $this->get_zoho_id_by_product_id($proid);
            if ($zi_category_id) {
                // If zoho category id mapped to woo product then pass same category id for api call.
                $pdt3 = '"category_id" : "' . $zi_category_id . '",' . $pdt3;
            } else {
                // If zoho category id is not mapped to product, then assign mapped product category with zoho.
                $zi_category_id = get_post_meta($proid, 'zi_category_id', true);
                if ($zi_category_id) {
                    $pdt3 = '"category_id" : "' . $zi_category_id . '",' . $pdt3;
                }
            }
            // dimensions data append to add call.
            if ($product_dimensions) {
                $pdt3 = '"package_details" : ' . json_encode($product_dimensions) . ',' . $pdt3;
            }
            $update_resp = $this->product_zoho_update_inventory_post($proid, $zoho_itemId, $pdt3);
            $response->message = $update_resp->message;
            $response->code = $update_resp->code;
        } else {
            $handlefunction = new Classfunctions;

            $zoho_inventory_access_token = get_option('zoho_inventory_access_token');
            $zoho_inventory_refresh_token = get_option('zoho_inventory_refresh_token');
            $zoho_inventory_timestamp = get_option('zoho_inventory_timestamp');
            $current_time = strtotime(date('Y-m-d H:i:s'));

            if ($zoho_inventory_timestamp < $current_time) {

                $respoAtJs = $handlefunction->GetServiceZIRefreshToken($zoho_inventory_refresh_token);

                $zoho_inventory_access_token = $respoAtJs['access_token'];
                update_option('zoho_inventory_access_token', $respoAtJs['access_token']);
                update_option('zoho_inventory_timestamp', strtotime(date('Y-m-d H:i:s')) + $respoAtJs['expires_in']);

            }

            $url = $zoho_inventory_url . 'api/v1/items';
            $curl = curl_init($url);

            curl_setopt_array(
                $curl,
                array(
                    CURLOPT_POST => 1,
                    CURLOPT_POSTFIELDS => $data,
                    CURLOPT_HTTPHEADER => array(
                        "Authorization: Bearer " . $zoho_inventory_access_token,
                        "Content-Type:multipart/form-data",
                    ),
                    CURLOPT_RETURNTRANSFER => true,
                )
            );
			usleep(500000);
            $result = curl_exec($curl);

            $json = json_decode($result);
            $code = $json->code;
            $response->message = $json->message;

			// fwrite($fd, PHP_EOL. 'response_message: '. $response->message); //logging response
    		// fclose($fd); //end of logging

            if ($code == '0' || 0 == $code) {
                $this->add_item_meta_data($json->item, $proid);
            } else {
                // Checking if item with same sku is available in zoho inventory.
                if ($code == '1001' || $code == 1001) {
                    $url = $zoho_inventory_url . 'api/v1/items?organization_id=' . $zoho_inventory_oid . '&search_text=' . $sku;

                    $curl = curl_init($url);
                    $headers = array('Content-Type:multipart/form-data');
                    curl_setopt_array(
                        $curl,
                        array(
                            CURLOPT_HTTPHEADER => array(
                                "Authorization: Bearer " . $zoho_inventory_access_token,
                                "Content-Type:multipart/form-data",
                            ),
                            CURLOPT_RETURNTRANSFER => true,
                        )
                    );
					usleep(500000);
                    $result = curl_exec($curl);
                    $json = json_decode($result);
                    $code = $json->code;
                    if ($code == '0' || $code == 0) {
                        // $json->items[0]: first item of items array because filter with sku is always having one item.
                        $this->add_item_meta_data($json->items[0], $proid);
                    }
                    $response->message = $json->message;
                }
            }

            $response_code = $json->code;
        }
        echo '<br> : ' . $response->message;
        return $response;
    }

    /**
     * Find matching product variation
     *
     * @param WC_Product $product - Woo Product object.
     * @param array      $attributes - Filter attribute.
     * @return int Matching variation ID or 0.

    protected function zi_find_matching_product_variation( $product, $attributes ) {

    foreach ( $attributes as $key => $value ) {
    if ( strpos( $key, 'attribute_' ) === 0 ) {
    continue;
    }

    unset( $attributes[ $key ] );
    $attributes[ sprintf( 'attribute_%s', $key ) ] = $value;
    }

    if ( class_exists( 'WC_Data_Store' ) ) {

    $data_store = WC_Data_Store::load( 'product' );
    return $data_store->find_matching_product_variation( $product, $attributes );

    } else {

    return $product->get_matching_variation( $attributes );

    }

    }
     */

    /**
     * Function to update zoho item if already exists.
     *
     * @param number $proid - product number.
     * @param number $item_id - zoho item id.
     * @param mixed  $pdt3 - Zoho item object for post request.
     * @return string
     */
    protected function product_zoho_update_inventory_post($proid, $item_id, $pdt3)
    {

        $handlefunction = new Classfunctions;

        $zoho_inventory_oid = get_option('zoho_inventory_oid');
        $zoho_inventory_url = get_option('zoho_inventory_url');
        $zoho_inventory_access_token = get_option('zoho_inventory_access_token');
        $zoho_inventory_refresh_token = get_option('zoho_inventory_refresh_token');
        $zoho_inventory_timestamp = get_option('zoho_inventory_timestamp');
        $current_time = strtotime(date('Y-m-d H:i:s'));

        if ($zoho_inventory_timestamp < $current_time) {

            $respoAtJs = $handlefunction->GetServiceZIRefreshToken($zoho_inventory_refresh_token);

            $zoho_inventory_access_token = $respoAtJs['access_token'];
            update_option('zoho_inventory_access_token', $respoAtJs['access_token']);
            update_option('zoho_inventory_timestamp', strtotime(date('Y-m-d H:i:s')) + $respoAtJs['expires_in']);

        }
        $url_p = $zoho_inventory_url . 'api/v1/items/' . $item_id;
        $data_p = array(
            'JSONString' => '{' . $pdt3 . '}',
            'organization_id' => $zoho_inventory_oid,
        );
        $curl_p = curl_init($url_p);

        curl_setopt_array(
            $curl_p,
            array(
                CURLOPT_POSTFIELDS => $data_p,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer " . $zoho_inventory_access_token,
                    "Content-Type:multipart/form-data",
                ),
            )
        );

        curl_setopt($curl_p, CURLOPT_CUSTOMREQUEST, 'PUT');
        $put = curl_exec($curl_p);
        $json_p = json_decode($put);
        // $code   = $json_p->code;
        return $json_p;
    }

    /**
     * Function for adding/updating metadata of item
     *
     * @param [object] $item Object of item return by zoho.
     * @param [string] $proid Product id of woocommerce to add metadata of it.
     * @return void
     */
    private function add_item_meta_data($item, $proid)
    {
        foreach ($item as $key => $value) {
            if ($key == 'item_id' || $key == 'composite_item_id') {
                $item_id = $value;
            }
            if ($key == 'purchase_account_id') {
                $purchase_account_id = $value;
            }
            if ($key == 'account_id') {
                $account_id = $value;
            }
            if ($key == 'account_name') {
                $account_name = $value;
            }
            if ($key == 'inventory_account_id') {
                $inventory_account_id = $value;
            }
            if ($key == 'category_id' && !empty($value)) {
                update_post_meta($proid, 'zi_category_id', $value);
            }
        }
        if ($item_id) {
            update_post_meta($proid, 'zi_item_id', $item_id);
        }
        if ($purchase_account_id) {
            update_post_meta($proid, 'zi_purchase_account_id', $purchase_account_id);
        }
        if ($account_id) {
            update_post_meta($proid, 'zi_account_id', $account_id);
        }
        if ($account_name) {
            update_post_meta($proid, 'zi_account_name', $account_name);
        }
        if ($inventory_account_id) {
            update_post_meta($proid, 'zi_inventory_account_id', $inventory_account_id);
        }

    }

    /**
     * Function to get zoho category id from option value of category (terms) assigned to woocommerce product.
     *
     * @return mixed false if category not found else return product id.
     */
    protected function get_zoho_id_by_product_id($product_id)
    {
        // Check if product category already synced.
        $terms = get_the_terms($product_id, 'product_cat');
        if (count($terms) > 0) {
            foreach ($terms as $term) {
                $product_cat_id = $term->term_id;
                $zoho_cat_id = get_option("zoho_id_for_term_id_{$product_cat_id}");
                if ($zoho_cat_id) {
                    break;
                }
            }
        }
        // Check if product has already mapped category.
        if (empty($zoho_cat_id)) {
            $zoho_cat_id = get_post_meta($product_id, 'zi_category_id', true);
        }

        if ($zoho_cat_id) {
            return $zoho_cat_id;
        } else {
            return false;
        }
    }

    /**
     * Send log email to admin.
     *
     * @return void
     */
    protected function send_log_message_to_admin($log_text, $subject, $message)
    {
        $log_html = "<h3>$message</h3>";
        $log_html .= '<table><thead><tr><th>Action</th><th> Log message</th><th> Product Id</th></tr></thead><tbody>';
        $log_html .= $log_text;
        $log_html .= '</tbody></table>';
        $this->error_log_api_email($subject, $log_html);
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
        global $wpdb;

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
    /**
     * Log data
     *
     * @param mixed $data - Log date.
     * @return void
     */
    protected function logData($data)
    {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }

}

// do_action( 'zoho_item_sync' );

new ZohoSync();
exit();
