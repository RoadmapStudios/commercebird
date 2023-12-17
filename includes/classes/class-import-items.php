<?php
/**
 * Class to import Products from Zoho to WooCommerce
 *
 * @package  WooZo Inventory
 */

class ImportProductClass
{

    public function __construct()
    {
        $this->config = [
            'ProductZI' => [
                'OID' => get_option('zoho_inventory_oid'),
                'APIURL' => get_option('zoho_inventory_url'),
            ],
        ];
    }

    /**
     * Function to retrieve item details and sync items.
     *
     * @param string $url - URL to get details.
     * @return mixed return true if data false if error.
     */
    public function zi_item_bulk_sync($url)
    {
        // $fd = fopen(__DIR__ . '/manual_item_updated.txt', 'a+');

        global $wpdb;
        $executeCurlCallHandle = new ExecutecallClass();
        $json = $executeCurlCallHandle->ExecuteCurlCallGet($url);
        $code = $json->code;

        /* Conditional code to load file only if source is cron. */
        $current_user = wp_get_current_user();
        $admin_author_id = $current_user->ID;
        if (empty($current_user)) {
            $admin_author_id = '1';
        }

        // $message = $json->message;
        // fwrite($fd, PHP_EOL . '$json->item : ' . print_r($json, true));
        if (0 == $code || '0' == $code) {

            foreach ($json->items as $arr) {
                if (isset($arr->custom_field_hash)) {
                    $item_tags_hash = $arr->custom_field_hash;
                    if (isset($item_tags_hash->cf_tags)) {
                        $item_tags = $item_tags_hash->cf_tags;
                    }
                }

                // fwrite($fd, PHP_EOL . '$arr : ' . print_r($arr, true));
                if ((!empty($arr->item_id)) && !($arr->is_combo_product)) {
                    // fwrite($fd, PHP_EOL . 'Item Id found : ' . $arr->item_id);

                    $tbl = $wpdb->prefix;
                    $product_res = $wpdb->get_row('SELECT * FROM ' . $tbl . "postmeta WHERE meta_key='zi_item_id' AND meta_value='" . $arr->item_id . "'");
                    if (!empty($product_res->post_id)) {
                        // fwrite($fd, PHP_EOL . 'Product Already there ');
                        $pdt_id = $product_res->post_id;
                    }

                    if (!empty($pdt_id)) {
                        // Load the Product Object
                        $product = wc_get_product($pdt_id);

                        $zi_disable_itemdescription_sync = get_option('zoho_disable_itemdescription_sync_status');
                        if (!empty($arr->description) && $zi_disable_itemdescription_sync != 'true') {
                            $product->set_short_description($arr->description);
                        }

                        if (!empty($arr->status)) {
                            $status = $arr->status == 'active' ? 'publish' : 'draft';
                            $product->set_status($status);
                        }

                        $zi_disable_itemname_sync = get_option('zoho_disable_itemname_sync_status');
                        if (($zi_disable_itemname_sync != 'true') && !empty($arr->name)) {
                            $product->set_name(stripslashes($arr->name));
                        }

                        if (!empty($arr->sku)) {
                            $product->set_sku($arr->sku);
                        }

                        $zi_disable_itemprice_sync = get_option('zoho_disable_itemprice_sync_status');
                        if (!empty($arr->rate) && $zi_disable_itemprice_sync != 'true') {
                            $product->set_regular_price($arr->rate);
                            $sale_price = $product->get_sale_price();
                            if (empty($sale_price)) {
                                $product->set_price($arr->rate);
                            }
                        }

                        if (!empty($item_tags)) {
                            $final_tags = explode(',', $item_tags);
                            $product->set_category_ids($final_tags);
                        }

                        if (!empty($arr->image_document_id)) {
                            $imageClass = new ImageClass();
                            $imageClass->args_attach_image($arr->item_id, $arr->name, $pdt_id, $arr->image_name, $admin_author_id);
                        }

                        $details = $arr->package_details;
                        $product->set_weight(floatval($details->weight));
                        $product->set_length(floatval($details->length));
                        $product->set_width(floatval($details->width));
                        $product->set_height(floatval($details->height));

                        // To check status of stock sync option.
                        $zi_stock_sync = get_option('zoho_stock_sync_status');
                        if ($zi_stock_sync != 'true') {
                            // Update stock
                            $accounting_stock = get_option('zoho_enable_accounting_stock_status');
                            // Sync from specific warehouse check
                            $zi_enable_warehousestock = get_option('zoho_enable_warehousestock_status');
                            $warehouse_id = get_option('zoho_warehouse_id');
                            $warehouses = $arr->warehouses;
                            if ($zi_enable_warehousestock == true) {
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
                                $product->set_manage_stock(true);
                                $product->set_stock_quantity(number_format($stock, 0, '.', ''));
                                if ($stock > 0) {
                                    $status = 'instock';
                                    $product->set_stock_status($status);
                                } else {
                                    $backorder_status = $product->get_backorders();
                                    $status = ($backorder_status === 'yes') ? 'onbackorder' : 'outofstock';
                                    $product->set_stock_status($status);
                                }
                            }
                        }

                        if (!empty($arr->tax_id)) {
                            $zi_common_class = new ZI_CommonClass();
                            $woo_tax_class = $zi_common_class->get_woo_tax_class_from_zoho_tax_id($arr->tax_id);
                            $product->set_tax_status('taxable');
                            $product->set_tax_class($woo_tax_class);
                            $product->save();
                        } else {
                            // Save the changes
                            $product->save();
                        }

                        wc_delete_product_transients($pdt_id); // Clear/refresh cache

                    }
                }
            }
        } else {
            return false;
        }
        // Return if synced.
        return true;
        // fclose($fd);
    }

    /**
     * Function to add items recursively by cron job.
     *
     * @param [number] $page  - Page number for getting item with pagination.
     * @param [number] $category - Category id to get item of specific category.
     * @param [string] $source - Source from where function is calling : 'cron'/'sync'.
     * @return mixed
     */
    public function sync_item_recursively()
    {
        // $fd = fopen(__DIR__ . '/simple-items-sync.txt', 'a+');

        $args = func_get_args();
        if (!empty($args)) {
            // Unpack the arguments
            foreach ($args as $innerArray) {
                if (isset($innerArray['page'])) {
                    $page = $innerArray['page'];
                }
                if (isset($innerArray['category'])) {
                    $category = $innerArray['category'];
                }
            }

            // fwrite($fd, PHP_EOL . 'category: '. $category);

            // Keep backup of current syncing page of particular category.
            update_option('simple_item_sync_page_cat_id_' . $category, $page);

            $zoho_inventory_oid = $this->config['ProductZI']['OID'];
            $zoho_inventory_url = $this->config['ProductZI']['APIURL'];
            $urlitem = $zoho_inventory_url . 'api/v1/items?organization_id=' . $zoho_inventory_oid . '&category_id=' . $category . '&page=' . $page . '&per_page=100&sort_column=last_modified_time';
            $executeCurlCallHandle = new ExecutecallClass();
            $json = $executeCurlCallHandle->ExecuteCurlCallGet($urlitem);
            $code = $json->code;

            global $wpdb;

            /* Response for item sync with sync button. For cron sync blank array will return. */
            $response_msg = array();
            if ($code == '0' || $code == 0) {
                $item_ids = [];
                foreach ($json->items as $arr) {

                    $prod_id = $this->get_product_by_sku($arr->sku);
                    $is_bundle = $arr->is_combo_product;
                    if (isset($arr->group_id)) {
                        $is_grouped = $arr->group_id;
                    }
                    // Flag to enable or disable sync.
                    $allow_to_import = false;
                    // Check if product exists with same sku.
                    if ($prod_id) {
                        $zi_item_id = get_post_meta($prod_id, 'zi_item_id', true);
                        if (empty($zi_item_id)) {
                            // Map existing item with zoho id.
                            update_post_meta($prod_id, 'zi_item_id', $arr->item_id);
                            $allow_to_import = true;
                        }
                    }
                    if ('' == $is_bundle && empty($is_grouped)) {
                        // If product not exists normal behavior of item sync.
                        $allow_to_import = true;
                    }
                    if (!empty($is_grouped)) {
                        $allow_to_import = false;
                        $this->sync_variation_of_group($arr);
                        continue;
                    }

                    // Get the post id
                    $pdt_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'zi_item_id' AND meta_value = '%s' LIMIT 1", $arr->item_id));

                    if (empty($pdt_id) && $allow_to_import == true) {
                        $product_class = new ProductClass();
                        $pdt_id = $product_class->zi_product_to_woocommerce($arr, '', '');
                        if ($pdt_id) {
                            update_post_meta($pdt_id, 'zi_item_id', $arr->item_id);
                        }
                    }

                    if ($pdt_id) {
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
                            // check if the Brand or Brands taxonomy exists and then update the term
                            if(taxonomy_exists('product_brand')) {
                                wp_set_object_terms($pdt_id, $arr->brand, 'product_brand');
                            } elseif(taxonomy_exists('product_brands')) {
                                wp_set_object_terms($pdt_id, $arr->brand, 'product_brands');
                            }
                        }
                        $item_ids[] = $arr->item_id;
                    } // end of wpdb post_id check
                }
                $item_id_str = implode(",", $item_ids);
                // fwrite($fd, PHP_EOL . 'Before Bulk sync');
                $item_details_url = "{$zoho_inventory_url}api/v1/itemdetails?item_ids={$item_id_str}&organization_id={$zoho_inventory_oid}";
                $this->zi_item_bulk_sync($item_details_url);

                if ($json->page_context->has_more_page) {
                    $data['page'] = $page + 1;
                    $this->sync_item_recursively($data);
                } else {
                    // If there is no more page to sync last backup page will be starting from 1.
                    // This we have used because in shared hosting only 1000 records are syncing.
                    update_option('simple_item_sync_page_cat_id_' . $category, 1);
                }
                array_push($response_msg, $this->zi_response_message($code, $json->message));
            }
            //fclose($fd);
            return $response_msg;
        }
    }

    /**
     * Function to add group items recursively by manual sync
     *
     * @param [number] $page  - Page number for getting group item with pagination.
     * @param [number] $category - Category id to get group item of specific category.
     * @param [string] $source - Source from where function is calling : 'cron'/'sync'.
     * @return mixed
     */
    public function sync_groupitem_recursively()
    {
        // $fd = fopen(__DIR__ . '/sync_groupitem_recursively.txt', 'w');

        $args = func_get_args();
        if (!empty($args)) {
            // Unpack the arguments
            foreach ($args as $innerArray) {
                if (isset($innerArray['page'])) {
                    $page = $innerArray['page'];
                }
                if (isset($innerArray['category'])) {
                    $category = $innerArray['category'];
                }
            }

            // Keep backup of current syncing page of particular category.
            update_option('group_item_sync_page_cat_id_' . $category, $page);

            // fwrite($fd, PHP_EOL . 'Test name Update ' . print_r($data, true));
            global $wpdb;
            $zoho_inventory_oid = $this->config['ProductZI']['OID'];
            $zoho_inventory_url = $this->config['ProductZI']['APIURL'];

            $url = $zoho_inventory_url . 'api/v1/itemgroups/?organization_id=' . $zoho_inventory_oid . '&category_id=' . $category . '&page=' . $page . '&per_page=100&sort_column=last_modified_time';
            // fwrite($fd, PHP_EOL . '$url : ' . $url);

            $executeCurlCallHandle = new ExecutecallClass();
            $json = $executeCurlCallHandle->ExecuteCurlCallGet($url);

            $code = $json->code;
            // $message = $json->message;

            $response_msg = array();

            if ($code == '0' || $code == 0) {
                // fwrite($fd, PHP_EOL . '$json->itemgroups : ' . print_r($json->itemgroups, true));
                foreach ($json->itemgroups as $gpArr) {
                    $zi_group_id = $gpArr->group_id;
                    $zi_group_name = $gpArr->group_name;

                    // fwrite($fd, PHP_EOL . '$itemGroup : ' . print_r($gpArr, true));

                    // skip if there is no first attribute
                    $zi_group_attribute1 = $gpArr->attribute_id1;
                    if (empty($zi_group_attribute1)) {
                        continue;
                    }

                    // Get Group ID
                    $group_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'zi_item_id' AND meta_value = '%s' LIMIT 1", $zi_group_id));
                    array_push($response_msg, $this->zi_response_message('SUCCESS', 'Zoho Group Item Synced: ' . $zi_group_name, $group_id));
                    /// end insert group product
                    // variable items
                    // fwrite($fd, PHP_EOL . '$group_id exists ' . $group_id);
                    if (!empty($group_id)) {
                        $existing_parent_product = wc_get_product($group_id);
                        // fwrite($fd, PHP_EOL . 'Existing group Id');
                        $zi_disable_itemdescription_sync = get_option('zoho_disable_itemdescription_sync_status');
                        if (!empty($gpArr->description) && $zi_disable_itemdescription_sync != 'true') {
                            $existing_parent_product->set_short_description($gpArr->description);
                        }

                        $attributes = $existing_parent_product->get_attributes();
                        if (empty($attributes)) {
                            // Create or Update the Attributes
                            $attr_created = $this->sync_attributes_of_group($gpArr, $group_id);
                            // fwrite($fd, PHP_EOL . '$attr_created ' . $attr_created);
                            if ($attr_created) {
                                // Enqueue and schedule the action using WC Action Scheduler
                                $existing_schedule = as_has_scheduled_action('import_variable_product_cron', array($zi_group_id, $group_id));
                                if (!$existing_schedule) {
                                    as_schedule_single_action(time(), 'import_variable_product_cron', array($zi_group_id, $group_id));
                                }
                                // $this->import_variable_product_variations($gpArr, $group_id);
                            }
                        }

                        $existing_parent_product->save();
                        // Tags update.
                        if (isset($gpArr->custom_field_hash)) {
                            $item_tags_hash = $gpArr->custom_field_hash;
                            $item_tags = $item_tags_hash->cf_tags;
                        }
                        // Tags
                        if (!empty($item_tags)) {
                            $final_tags = explode(',', $item_tags);
                            wp_set_object_terms($group_id, $final_tags, 'product_tag');
                        }
                    } else {
                        if ($gpArr->status == 'active') {
                            // Create the parent variable product
                            $parent_product = new WC_Product_Variable();
                            $parent_product->set_name($zi_group_name);
                            $parent_product->set_status('publish');
                            $parent_product->set_short_description($gpArr->description);
                            $parent_product->add_meta_data('zi_item_id', $zi_group_id);
                            $group_id = $parent_product->save();

                            // fwrite($fd, PHP_EOL . 'New $group_id ' . $group_id);

                            // Create or Update the Attributes
                            $attr_created = $this->sync_attributes_of_group($gpArr, $group_id);

                            if (!empty($group_id) && $attr_created) {
                                // Enqueue and schedule the action using WC Action Scheduler
                                $existing_schedule = as_has_scheduled_action('import_variable_product_cron', array($zi_group_id, $group_id));
                                if (!$existing_schedule) {
                                    as_schedule_single_action(time(), 'import_variable_product_cron', array($zi_group_id, $group_id));
                                } // $this->import_variable_product_variations($gpArr, $group_id);
                            } // end for each item loop
                        }
                    } // create variable product
                } // end foreach group items

                if ($json->page_context->has_more_page) {
                    $data['page'] = $page + 1;
                    $this->sync_groupitem_recursively($data);
                } else {
                    // If there is no more page to sync last backup page will be starting from 1.
                    // This we have used because in shared hosting only 1000 records are syncing.
                    update_option('group_item_sync_page_cat_id_' . $category, 1);
                }
                array_push($response_msg, $this->zi_response_message($code, $json->message));
            }
            return $response_msg;
        }
        // fclose($fd);
    }

    /**
     * Callback function for importing a variable product and its variations.
     *
     * @param object $gpArr Group item details.
     * @param int $group_id Parent variable Product ID.
     */
    public function import_variable_product_variations()
    {
        // $fd = fopen(__DIR__ . '/import_variable_product_variations.txt', 'w');
        $args = func_get_args();
        $zi_group_id = $args[0];
        $group_id = $args[1];

        if (empty($zi_group_id) || empty($group_id)) {
            return;
        }
        $zoho_inventory_oid = $this->config['ProductZI']['OID'];
        $zoho_inventory_url = $this->config['ProductZI']['APIURL'];
        $url = $zoho_inventory_url . 'api/v1/itemgroups/' . $zi_group_id . '?organization_id=' . $zoho_inventory_oid;
        $executeCurlCallHandle = new ExecutecallClass();
        $json = $executeCurlCallHandle->ExecuteCurlCallGet($url);
        $code = $json->code;

        global $wpdb;
        $admin_author_id = '1';
        // fwrite($fd, PHP_EOL . '$admin_author_id : ' . $admin_author_id);

        // Accounting stock mode check
        $accounting_stock = get_option('zoho_enable_accounting_stock_status');
        $zi_stock_sync = get_option('zoho_stock_sync_status');
        $product = wc_get_product($group_id);

        if ($code == '0' || $code == 0) {

            // Sync category first
            $this->sync_groupitem_category($json, $group_id);

            foreach ($json->item_group as $key => $arr) {

                if ($key == 'items') {
                    $items = $arr;
                }
                if ($key == 'attribute_name1') {
                    $attribute_name1 = $arr;
                }
                if ($key == 'attribute_name2') {
                    $attribute_name2 = $arr;
                }
                if ($key == 'attribute_name3') {
                    $attribute_name3 = $arr;
                }

                foreach ($items as $item) {
                    $variation_data = array(); // reset this array
                    $attribute_arr = array();

                    $zi_item_id = $item->item_id;
                    $variation_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'zi_item_id' AND meta_value = '%s' LIMIT 1", $zi_item_id));

                    if (!empty($variation_id)) {
                        $product = wc_get_product($variation_id);
                        $product_type = $product->get_type();
                        if ($product_type == 'simple') {
                            wp_delete_post($variation_id, true);
                        }
                    }
                    // SKU check of the variation, if exits then remove it
                    if (!empty($item->sku)) {
                        $sku_prod_id = $this->get_product_by_sku($item->sku);
                        if (!empty($sku_prod_id)) {
                            wp_delete_post($sku_prod_id, true);
                        }
                    }

                    // Stock mode check
                    $zi_enable_warehousestock = get_option('zoho_enable_warehousestock_status');
                    $warehouse_id = get_option('zoho_warehouse_id');
                    $warehouses = $item->warehouses;

                    if ($zi_enable_warehousestock == true) {
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
                            $stock = $item->available_stock;
                        } else {
                            $stock = $item->actual_available_stock;
                        }
                    }

                    $attribute_name11 = $item->attribute_option_name1;
                    $attribute_name12 = $item->attribute_option_name2;
                    $attribute_name13 = $item->attribute_option_name3;

                    if (!empty($attribute_name11)) {

                        $attribute_arr[$attribute_name1] = $attribute_name11;
                    }
                    if (!empty($attribute_name12)) {

                        $attribute_arr[$attribute_name2] = $attribute_name12;
                    }
                    if (!empty($attribute_name13)) {

                        $attribute_arr[$attribute_name3] = $attribute_name13;
                    }

                    // Get the variation attributes with correct attribute values
                    $variation_attributes = array();
                    foreach ($attribute_arr as $attribute => $term_name) {
                        $taxonomy = wc_attribute_taxonomy_name($attribute);
                        // $taxonomy_slug = wc_attribute_taxonomy_slug($attribute);
                        $term = get_term_by('name', $term_name, $taxonomy)->slug;
                        if ($term) {
                            $variation_attributes[$taxonomy] = urldecode($term);
                        }
                    }
                    // Add the variation data to the variations array
                    $variation_data['regular_price'] = $item->rate;
                    $variation_data['sku'] = $item->sku;
                    $variation_data['attributes'] = $variation_attributes;
                    $variation_data['featured_image'] = $item->image_document_id;

                    // fwrite($fd, PHP_EOL . '$variation_attributes : ' . print_r($variation_attributes, true));
                    // Loop through the variations and create them
                    $variation = new WC_Product_Variation();
                    $variation->set_parent_id($group_id);
                    $variation->set_status('publish');
                    $variation->set_props($variation_data);
                    if ($zi_stock_sync != 'true') {
                        $variation->set_stock_quantity($stock);
                        $variation->set_manage_stock(true);
                        $variation->set_stock_status('');
                    } else {
                        $variation->set_manage_stock(false);
                    }
                    $variation->add_meta_data('zi_item_id', $item->item_id);
                    // $variation->set_attributes($variation_attributes);
                    $variation_id = $variation->save();

                    // Loop through the variation attributes and assign them to the variation
                    foreach ($variation_attributes as $taxonomy => $term_slug) {
                        // Get the attribute slug from the taxonomy
                        update_post_meta($variation_id, 'attribute_' . $taxonomy, $term_slug);
                    }

                    // Featured Image of variation
                    if (!empty($variation_data['featured_image'])) {
                        $imageClass = new ImageClass();
                        $imageClass->args_attach_image($item->item_id, $item->name, $variation_id, $item->image_name, $admin_author_id);
                    }

                    // Sync the data of the variation in the parent variable product
                    $product->sync($variation_id);
                }
                // End group item add process
                // array_push($response_msg, $this->zi_response_message('SUCCESS', 'Zoho variable item created for zoho item id ' . $zi_item_id, $variation_id));
            }

            $data_store = $product->get_data_store();
            $data_store->sort_all_product_variations($group_id);
            // End of Logging
            // fclose($fd);
        }
    }

    /**
     * Update or Create the Product Attributes for the Variable Item Sync
     *
     * @param: $group_id - the parent product id in WooCommerce
     * @return: bool - true if attributes were created successfully, false otherwise
     */
    public function sync_attributes_of_group($gpArr, $group_id)
    {
        // $fd = fopen(__DIR__ . '/sync_attributes_of_group.txt', 'a+');
        // Create attributes
        $success = true; // Track the success of attribute creation
        $attributes_data = array();
        $attribute_count = 0;
        $attribute_options_map = array(); // Track unique attribute options

        // Loop through the attribute names
        for ($i = 1; $i <= 3; $i++) {
            $attribute_name_key = 'attribute_name' . $i;
            $attribute_option_name_key = 'attribute_option_name' . $i;

            // Get the attribute name
            $attribute_name = $gpArr->$attribute_name_key;

            if (!empty($attribute_name)) {
                // Check if the attribute is already added to the attributes array
                if (!isset($attributes_data[$attribute_name])) {
                    // Create the attribute and add it to the attributes array
                    $attribute = array(
                        'name' => $attribute_name,
                        'position' => $attribute_count,
                        'visible' => true,
                        'variation' => true,
                        'options' => array(),
                    );

                    // Loop through the items and retrieve attribute options
                    $attribute_options = array();
                    foreach ($gpArr->items as $item) {
                        $attribute_option = $item->$attribute_option_name_key;
                        if (!empty($attribute_option) && !in_array($attribute_option, $attribute_options_map)) {
                            $attribute_options[] = $attribute_option;
                            $attribute_options_map[] = $attribute_option;
                        }
                    }

                    // Set the attribute options
                    $attribute['options'] = $attribute_options;

                    $attributes_data[] = $attribute;
                    $attribute_count++;
                }
            }
        }
        // fwrite($fd, PHP_EOL . '$attributes : ' . print_r($attributes_data, true));

        // Assign the attributes to the parent product
        if (sizeof($attributes_data) > 0) {
            $attributes = array(); // Initializing

            // Loop through defined attribute data
            foreach ($attributes_data as $key => $attribute_array) {
                if (isset($attribute_array['name']) && isset($attribute_array['options'])) {
                    // Clean attribute name to get the taxonomy
                    $taxonomy = 'pa_' . wc_sanitize_taxonomy_name($attribute_array['name']);

                    $option_term_ids = array(); // Initializing

                    // Create the attribute if it doesn't exist
                    if (!taxonomy_exists($taxonomy)) {
                        // Clean attribute label for better display
                        $attribute_label = ucfirst($attribute_array['name']);

                        // Register the new attribute taxonomy
                        $attribute_args = array(
                            'slug' => $taxonomy,
                            'name' => $attribute_label,
                            'type' => 'select',
                            'order_by' => 'menu_order',
                            'has_archives' => false,
                        );

                        $result = wc_create_attribute($attribute_args);
                        register_taxonomy($taxonomy, array('product'), array());

                        if (!is_wp_error($result)) {
                            // fwrite($fd, PHP_EOL . 'result : ' . $result);
                            // Loop through defined attribute data options (terms values)
                            foreach ($attribute_array['options'] as $option) {
                                // Check if the term exists for the attribute taxonomy
                                $term = term_exists($result, $taxonomy);
                                if (empty($term)) {
                                    // Term doesn't exist, create a new one
                                    $term_id = wp_insert_term($option, $taxonomy);

                                    if (!is_wp_error($term_id)) {
                                        $term_id = $term_id['term_id'];
                                    } else {
                                        $success = false;
                                        $error_string = $term_id->get_error_message();
                                        // fwrite($fd, PHP_EOL . 'error_string : ' . $error_string);
                                    }
                                } else {
                                    // Get the existing term ID
                                    $term_id = $term['term_id'];
                                }
                                $option_term_ids[] = $term_id;
                            }

                            // Add the new attribute to the product attributes array
                            $attributes[$taxonomy] = array(
                                'name' => $taxonomy,
                                'value' => $option_term_ids, // Need to be term IDs
                                'position' => $key + 1,
                                'is_visible' => $attribute_array['visible'],
                                'is_variation' => $attribute_array['variation'],
                                'is_taxonomy' => '1',
                            );

                            // Get the existing terms for the taxonomy
                            $existing_terms = get_terms(array(
                                'taxonomy' => $taxonomy,
                                'hide_empty' => false,
                            ));

                            // Loop through existing terms and assign them to the product
                            foreach ($existing_terms as $existing_term) {
                                $existing_term_ids[] = $existing_term->term_id;
                            }

                            // Set the selected terms for the product
                            wp_set_object_terms($group_id, $existing_term_ids, $taxonomy);
                        } else {
                            $success = false;
                        }
                    } else {
                        // Add existing attribute with its selected terms to the product attributes array
                        $existing_terms = get_terms(array(
                            'taxonomy' => $taxonomy,
                            'hide_empty' => false,
                        ));

                        if ($existing_terms) {
                            $existing_term_ids = array();
                            foreach ($attribute_array['options'] as $option) {
                                $match_found = false;
                                foreach ($existing_terms as $existing_term) {
                                    if ($existing_term->name === $option) {
                                        $existing_term_ids[] = $existing_term->term_id;
                                        $match_found = true;
                                        break;
                                    }
                                }
                                if (!$match_found) {
                                    // Check if the term exists for the attribute taxonomy
                                    $term = term_exists($option, $taxonomy);

                                    if (empty($term)) {
                                        // Term doesn't exist, create a new one
                                        $term = wp_insert_term($option, $taxonomy);

                                        if (!is_wp_error($term)) {
                                            // Get the term ID
                                            $term_id = $term['term_id'];
                                            $existing_term_ids[] = $term_id;
                                        } else {
                                            $success = false;
                                        }
                                    } else {
                                        // Get the existing term ID
                                        $term_id = $term['term_id'];
                                        $existing_term_ids[] = $term_id;
                                    }
                                }
                            }

                            if (!empty($existing_term_ids)) {
                                $attributes[$taxonomy] = array(
                                    'name' => $taxonomy,
                                    'value' => $existing_term_ids,
                                    'position' => $key + 1,
                                    'is_visible' => $attribute_array['visible'],
                                    'is_variation' => $attribute_array['variation'],
                                    'is_taxonomy' => '1',
                                );

                                // Set the selected terms for the product
                                wp_set_object_terms($group_id, $existing_term_ids, $taxonomy, false);
                            }
                        } else {
                            $option_term_ids = array(); // Initializing

                            // Loop through defined attribute data options (terms values)
                            foreach ($attribute_array['options'] as $option) {
                                // Check if the term exists for the attribute taxonomy
                                $term = term_exists($option, $taxonomy);

                                if (empty($term)) {
                                    // Term doesn't exist, create a new one
                                    $term = wp_insert_term($option, $taxonomy);

                                    if (!is_wp_error($term)) {
                                        // Get the term ID
                                        $term_id = $term['term_id'];
                                        $option_term_ids[] = $term_id;
                                    } else {
                                        $success = false;
                                    }
                                } else {
                                    // Get the existing term ID
                                    $term_id = $term['term_id'];
                                    $option_term_ids[] = $term_id;
                                }
                            }

                            // Set the selected terms for the product
                            wp_set_object_terms($group_id, $option_term_ids, $taxonomy, false);

                            // Add the new attribute to the product attributes array
                            $attributes[$taxonomy] = array(
                                'name' => $taxonomy,
                                'value' => $option_term_ids,
                                'position' => $key + 1,
                                'is_visible' => $attribute_array['visible'],
                                'is_variation' => $attribute_array['variation'],
                                'is_taxonomy' => '1',
                            );
                        }
                    }
                }
            }

            // Save the meta entry for product attributes
            update_post_meta($group_id, '_product_attributes', $attributes);
        }
        // fclose($fd);
        return $success;
    }

    /**
     * Update or create variation in WooCommerce if Group-ID already exists in wpdB
     *
     * @param:  $arr - item object coming in from simple item recursive function
     * @return: void
     */
    public function sync_variation_of_group($itemArr)
    {
        // $fd = fopen(__DIR__ . '/sync_from_zoho.txt', 'a+');
        $item = $itemArr;
        // Stock mode check
        $accounting_stock = get_option('zoho_enable_accounting_stock_status');
        if ($accounting_stock == 'true') {
            $stock = $item->available_stock;
        } else {
            $stock = $item->actual_available_stock;
        }
        $item_id = $item->item_id;
        // $item_category = $item->category_name;
        $groupid = $item->group_id;
        $stock_quantity = $stock < 0 ? 0 : $stock;
        // fwrite($fd, PHP_EOL . 'Before group item sync : ' . $groupid);
        if (!empty($groupid)) {
            // fwrite($fd, PHP_EOL . 'Inside item sync : ' . $item_name);
            // find parent variable product
            global $wpdb;
            $group_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'zi_item_id' AND meta_value = '%s' LIMIT 1", $groupid));
            // fwrite($fd, PHP_EOL . 'Row Data : ' . print_r($row, true));
            // fwrite($fd, PHP_EOL . 'Row $group_id : ' . $group_id);

            if (isset($item->custom_field_hash)) {
                $item_tags_hash = $item->custom_field_hash;
                if (isset($item_tags_hash->cf_tags)) {
                    $item_tags = $item_tags_hash->cf_tags;
                }
            }
            if (isset($item->brand)) {
                $item_brand = $item->brand;
            }

            // Tags
            if (!empty($item_tags)) {
                $final_tags = explode(',', $item_tags);
                wp_set_object_terms($group_id, $final_tags, 'product_tag');
            }
            // Brand
            if ($item_brand) {
                wp_set_object_terms($group_id, $item_brand, 'product_brand');
            }

            $variation_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'zi_item_id' AND meta_value = '%s' LIMIT 1", $item_id));
            if ($variation_id) {
                // SKU - Imported
                if (!empty($item->sku)) {
                    update_post_meta($variation_id, '_sku', $item->sku);
                }

                // Price - Imported
                $zi_disable_itemprice_sync = get_option('zoho_disable_itemprice_sync_status');
                $variation_sale_price = get_post_meta($variation_id, '_sale_price', true);
                if (empty($variation_sale_price) && $zi_disable_itemprice_sync != 'true') {
                    update_post_meta($variation_id, '_price', $item->rate);
                    // $variation->set_price($itemArr->rate);
                }
                update_post_meta($variation_id, '_regular_price', $item->rate);

                // Stock Imported code
                update_post_meta($variation_id, '_stock', $stock_quantity);
                update_post_meta($variation_id, 'stock_qty', $stock_quantity);
                if ($stock_quantity > 0) {
                    $status = 'instock';
                    update_post_meta($variation_id, '_stock_status', wc_clean($status));
                    // wp_set_post_terms($variation_id, $status, 'product_visibility', true);
                    update_post_meta($variation_id, 'manage_stock', true);
                } else {
                    // $variation->set_manage_stock(false);
                    $backorder_status = get_post_meta($variation_id, '_backorders', true);
                    $status = ($backorder_status === 'yes') ? 'onbackorder' : 'outofstock';
                    update_post_meta($variation_id, '_stock_status', wc_clean($status));
                    // update_post_meta($variation_id, '_stock_status', wc_clean('outofstock'));
                    update_post_meta($variation_id, 'manage_stock', false);
                }
            } else {
                // create new variation
                // fwrite($fd, PHP_EOL . 'Variations not');

                $attribute_name11 = $item->attribute_option_name1;
                $attribute_name12 = $item->attribute_option_name2;
                $attribute_name13 = $item->attribute_option_name3;

                if (!empty($attribute_name11)) {

                    $attribute_arr[$item->attribute_name1] = $attribute_name11;
                }
                if (!empty($attribute_name12)) {

                    $attribute_arr[$item->attribute_name2] = $attribute_name12;
                }
                if (!empty($attribute_name13)) {

                    $attribute_arr[$item->attribute_name3] = $attribute_name13;
                }
                $variation_data = array(
                    'attributes' => $attribute_arr,
                    'sku' => $item->sku,
                    'regular_price' => $item->rate,
                    'stock_qty' => $stock_quantity,
                );

                $status = ($item->status == 'active') ? 'publish' : 'draft';
                $variation_post = array(
                    'post_title' => $item->name,
                    'post_name' => $item->name,
                    'post_status' => $status,
                    'post_parent' => $group_id,
                    'post_type' => 'product_variation',
                    'guid' => get_the_permalink($group_id),
                );

                // Map variation based on sku
                if (!empty($variation_data['sku'])) {
                    // here we do actual mapping based on same sku
                    // fwrite($fd, PHP_EOL . 'Before SKU :' . $variation_data['sku']);
                    $sku_prod_id = $this->get_product_by_sku($variation_data['sku']);
                    // fwrite($fd, PHP_EOL . 'Before $sku_prod_id :' . $sku_prod_id);
                    if (!empty($sku_prod_id)) {
                        // fwrite($fd, PHP_EOL . 'sku_prod_id :' . $sku_prod_id);
                        $variation_id = $sku_prod_id;
                        // fwrite($fd, PHP_EOL . 'This is product having already exists sku: ' . print_r($variation, true));
                    } else {
                        // here actually create new variation because sku not found
                        $variation_id = wp_insert_post($variation_post);
                        update_post_meta($variation_id, '_sku', $variation_data['sku']);
                        // fwrite($fd, PHP_EOL . '$variation_2 : ');
                    }
                }
                // Creating the product variation
                // $variation_id = wp_insert_post($variation_post);

                // fwrite($fd, PHP_EOL . 'This is Variations : ' . print_r($variation, true));
                // Iterating through the variations attributes
                foreach ($variation_data['attributes'] as $attribute => $term_name) {
                    update_post_meta($variation_id, 'attribute_' . strtolower(str_replace(' ', '-', $attribute)), trim($term_name));
                    update_post_meta($variation_id, 'group_id_store', $group_id);
                }

                // Prices
                // $variation->set_regular_price($variation_data['regular_price']);
                update_post_meta($variation_id, '_regular_price', $variation_data['regular_price']);
                $variation_sale_price = get_post_meta($variation_id, '_sale_price', true);
                if (empty($variation_sale_price)) {
                    // $variation->set_price($variation_data['regular_price']);
                    update_post_meta($variation_id, '_price', $variation_data['regular_price']);
                }
                // Stock
                update_post_meta($variation_id, '_stock', $stock_quantity);
                update_post_meta($variation_id, 'stock_qty', $stock_quantity);
                if (!empty($variation_data['stock_qty'])) {
                    // update_post_meta($variation_id, '_stock', $variation_data['stock_qty']);
                    update_post_meta($variation_id, 'manage_stock', true);
                    update_post_meta($variation_id, '_stock_status', 'instock');
                    // $variation->set_stock_status('');
                } else {
                    $backorder_status = get_post_meta($variation_id, '_backorders', true);
                    if ($backorder_status === 'yes') {
                        update_post_meta($variation_id, '_stock_status', 'onbackorder');
                    } else {
                        update_post_meta($variation_id, '_stock_status', 'outofstock');
                    }
                    update_post_meta($variation_id, 'manage_stock', false);
                }
                update_post_meta($variation_id, 'zi_item_id', $item_id);
                WC_Product_Variable::sync($group_id);

                // End group item add process
                wc_delete_product_transients($variation_id); // Clear/refresh the variation cache
                unset($attribute_arr);
            }
            // end of grouped item updating
        } else {
            // fwrite($fd, PHP_EOL . 'Group item empty');
        }
        // fwrite($fd, PHP_EOL . 'After group item sync');
        // fclose($fd);
    }

    /**
     * Function to get product_id from sku
     * @param [string] - sku of product
     * @return product_id
     */
    public function get_product_by_sku($sku)
    {
        global $wpdb;
        $product_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku));
        return $product_id;
    }

    // variable category check functionality
    public function sync_groupitem_category($json, $pdt_id)
    {
        // $fd = fopen(__DIR__ . '/category.txt', 'w+');
        $term_id = 0;

        if ($json) {
            // fwrite($fd, PHP_EOL . '$json->item_group : ' . print_r($json->item_group,true));
            foreach ($json->item_group as $key => $arr) {
                // fwrite($fd, PHP_EOL . '$term_id 3 : ' . $term_id);

                if ($key == 'category_id') {
                    $zi_category_id = $arr;
                }
                if ($key == 'category_name') {
                    $zi_category_name = $arr;
                }

                if (!empty($pdt_id) && $zi_category_name != '') {
                    // fwrite($fd, PHP_EOL . '$term_id : 5' . $term_id);
                    // $category_id = get_cat_ID( $arr->category_name );
                    $term = get_term_by('name', $zi_category_name, 'product_cat');
                    $term_id = $term->term_id;
                    // fwrite($fd, PHP_EOL . 'Term Id by Name ' . $term_id);
                    if (empty($term_id)) {
                        $term = wp_insert_term(
                            $zi_category_name,
                            'product_cat',
                            array(
                                'parent' => 0,
                            )
                        );
                        $term_id = $term->term_id;
                    }
                    // fwrite($fd, PHP_EOL . 'Term Id after insert ' . $term_id);
                    // If term id and zoho category id then import category.
                    if ($term_id && $zi_category_id) {
                        // update_post_meta($pdt_id, 'zi_category_id', $zi_category_id);
                        // update_post_meta($pdt_id, 'category_id', $term_id);
                        // $resp = wp_set_object_terms($pdt_id, $term_id, 'product_cat');
                        $existingTerms = wp_get_object_terms($pdt_id, 'product_cat');
                        if ($existingTerms && count($existingTerms) > 0) {
                            $isTermsExist = $this->zi_check_terms_exists($existingTerms, $term_id);
                            if (!$isTermsExist) {
                                update_post_meta($pdt_id, 'zi_category_id', $zi_category_id);
                                wp_add_object_terms($pdt_id, $term_id, 'product_cat');
                            }
                        } else {
                            update_post_meta($pdt_id, 'zi_category_id', $zi_category_id);
                            wp_set_object_terms($pdt_id, $term_id, 'product_cat');
                        }
                    }
                    // Remove "uncategorized" category if assigned
                    $uncategorized_term = get_term_by('slug', 'uncategorized', 'product_cat');
                    if ($uncategorized_term && has_term($uncategorized_term->term_id, 'product_cat', $pdt_id)) {
                        wp_remove_object_terms($pdt_id, $uncategorized_term->term_id, 'product_cat');
                    }
                }
                // } // closing of category check.
            } // item for each end
        }
        // fwrite($fd, PHP_EOL . 'Return $term_id : ' . $term_id);
        // fclose($fd);
        return $term_id;
    }

/**
 * Helper Function to check if child of composite items already synced  or not
 *
 * @param string $composite_zoho_id - zoho composite item id to check if it's child are already synced.
 * @param string $zi_url - zoho api url.
 * @param string $zi_key - zoho access token.
 * @param string $zi_org_id - zoho organization id.
 * @return array of child id and metadata if child item already synced else will return false.
 */
    public function zi_check_if_child_synced_already($composite_zoho_id, $zi_url, $zi_org_id, $prod_id)
    {
        if ($prod_id) {
            $bundle_childs = WC_PB_DB::query_bundled_items(
                array(
                    'return' => 'id=>product_id',
                    'bundle_id' => array($prod_id),
                )
            );
        }
        global $wpdb;

        $url = $zi_url . 'api/v1/compositeitems/' . $composite_zoho_id . '?organization_id=' . $zi_org_id;

        $executeCurlCallHandle = new ExecutecallClass();
        $json = $executeCurlCallHandle->ExecuteCurlCallGet($url);
        $code = $json->code;
        // Flag to allow sync of parent composite item.
        $allow_sync = false;
        // Array of child object metadata.
        $product_array = array(); // [{prod_id:'',metadata:{key:'',value:''}},...].
        if ('0' === $code || 0 === $code) {
            foreach ($json->composite_item->mapped_items as $child_item) {
                $prod_meta = $wpdb->get_row('SELECT * FROM ' . $wpdb->postmeta . " WHERE meta_key='zi_item_id' AND meta_value='" . $child_item->item_id . "'");
                // If any child will not have zoho id in meta field then process will return false and syncing will be skipped for given item.
                if (!empty($prod_meta->post_id)) {

                    $allow_sync = true;
                    $prod_obj = (object) array(
                        'prod_id' => $prod_meta->post_id,
                        'metadata' => (object) array(
                            'quantity_min' => max(1, $child_item->quantity),
                            'quantity_max' => max(1, $child_item->quantity),
                            'stock_status' => ($child_item->stock_on_hand) ? 'in_stock' : 'out_of_stock',
                            'max_stock' => $child_item->stock_on_hand,
                        ),
                    );
                    if (is_array($bundle_childs) && !empty($bundle_childs)) {
                        $index = array_search($prod_meta->post_id, $bundle_childs);
                        unset($bundle_childs[$index]);
                    }
                    array_push($product_array, $prod_obj);
                } else {
                    // Stop execution
                    return false;
                }
            }
        }
        if (is_array($bundle_childs) && !empty($bundle_childs)) {
            foreach ($bundle_childs as $item_id => $val) {
                WC_PB_DB::delete_bundled_item($item_id);
            }
        }
        if ($allow_sync) {
            return $product_array;
        }
        return false;
    }
/**
 * Mapping of bundled product
 *
 * @param number $product_id - Product id of child item of bundle product.
 * @param number $bundle_id  - BUndle id of product.
 * @param number $menu_order - Listing order of child product ($menu_order will useful at composite product details page).
 * @return void
 */
    public function add_bundle_product($product_id, $bundle_id, $menu_order = 0)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'woocommerce_bundled_items';
        $bundle_items = $wpdb->get_results("SELECT * FROM $table WHERE bundle_id = $bundle_id AND product_id = $product_id");

        if (count($bundle_items) > 0) {
            $wpdb->get_results("UPDATE $table SET menu_order = $menu_order WHERE product_id = $product_id AND bundle_id = $bundle_id");
            return $bundle_items[0]->bundled_item_id;
        } else {
            $wpdb->get_results("INSERT INTO $table (product_id, bundle_id, menu_order) VALUES ( $product_id, $bundle_id, $menu_order)");

            $bundle_items = $wpdb->get_results("SELECT * FROM $table WHERE bundle_id = $bundle_id AND product_id = $product_id");
            if (count($bundle_items) > 0) {
                return $bundle_items[0]->bundled_item_id;
            } else {
                return false;
            }
        }
    }

/**
 * Create or update bundle item metadata
 *
 * @param number $bundle_item_id bundle item id.
 * @param string $key - metadata key.
 * @param string $value - metadata value.
 * @return void
 */

    public function zi_update_bundle_meta($bundle_item_id, $key, $value)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'woocommerce_bundled_itemmeta';
        $metadata = $wpdb->get_results("SELECT * FROM $table WHERE bundled_item_id = $bundle_item_id AND meta_key = '$key'");
        if (count($metadata) > 0) {
            $wpdb->get_results("UPDATE $table SET meta_value = '$value' WHERE bundled_item_id = $bundle_item_id AND meta_key = '$key'");
        } else {
            $wpdb->get_results("INSERT INTO $table (bundled_item_id, meta_key, meta_value) VALUES ( $bundle_item_id, '$key', '$value')");
        }
    }

/**
 * Function to sync composite item from zoho to woocommerce
 *
 * @param integer $page - Page number of composite item data.
 * @param string $category - Category id of composite data.
 * @param string $source - Source of calling function.
 * @return mixed - mostly array of response message.
 */
    public function recursively_sync_composite_item_from_zoho($page, $category, $source)
    {
        // Start logging
        // $fd = fopen(__DIR__ . '/recursively_sync_composite_item_from_zoho.txt', 'a+');

        global $wpdb;
        $tbl_prefix = $wpdb->prefix;
        $zi_org_id = $this->config['ProductZI']['OID'];
        $zi_url = $this->config['ProductZI']['APIURL'];
        // Conditional code to load file only if source is cron.
        if ('cron' === $source) {
            // get admin user id who started the cron job.
            $admin_author_id = get_option('zi_cron_admin');
        } else {
            $current_user = wp_get_current_user();
            $admin_author_id = $current_user->ID;
        }

        $url = $zi_url . 'api/v1/compositeitems/?organization_id=' . $zi_org_id . '&filter_by=Status.Active&category_id=' . $category . '&page=' . $page;

        $executeCurlCallHandle = new ExecutecallClass();
        $json = $executeCurlCallHandle->ExecuteCurlCallGet($url);
        $code = $json->code;
        // $message = $json->message;
        // fwrite($fd, PHP_EOL . '$json  : ' . print_r($json, true));
        // Response for item sync with sync button. For cron sync blank array will return.
        $response_msg = array();
        if ($code == '0' || $code == 0) {
            if (empty($json->composite_items)) {
                array_push($response_msg, $this->zi_response_message('ERROR', 'No composite item to sync for category : ' . $category));
                return $response_msg;
            }
            // Accounting stock mode check
            $accounting_stock = get_option('zoho_enable_accounting_stock_status');
            foreach ($json->composite_items as $comp_item) {

                // Sync stock from specific warehouse check
                $zi_enable_warehousestock = get_option('zoho_enable_warehousestock_status');
                $warehouse_id = get_option('zoho_warehouse_id');
                $warehouses = $comp_item->warehouses;

                if ($zi_enable_warehousestock == true) {
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
                        $stock = $comp_item->available_for_sale_stock;
                    } else {
                        $stock = $comp_item->actual_available_for_sale_stock;
                    }
                }

                // ----------------- Create composite item in woocommerce--------------.
                // Code to skip sync with item already exists with same sku.
                $prod_id = $this->get_product_by_sku($comp_item->sku);
                // Flag to enable or disable sync.
                $allow_to_import = false;
                // Check if product exists with same sku.
                if ($prod_id) {
                    $zi_item_id = get_post_meta($prod_id, 'zi_item_id', true);
                    if ($zi_item_id) {
                        // If product is with same sku and zi_item_id mapped.
                        // Do not import ...
                        $allow_to_import = false;
                    } else {
                        // Map existing item with zoho id.
                        update_post_meta($prod_id, 'zi_item_id', $comp_item->composite_item_id);
                        $allow_to_import = false;
                    }
                } else {
                    // If product not exists normal bahaviour of item sync.
                    $allow_to_import = true;
                }
                $zoho_comp_item_id = $comp_item->composite_item_id;
                if ($comp_item->composite_item_id) {
                    $child_items = $this->zi_check_if_child_synced_already($zoho_comp_item_id, $zi_url, $zi_org_id, $prod_id);
                    // Check if child items already synced with zoho.
                    if (!$child_items) {
                        array_push($response_msg, $this->zi_response_message('ERROR', 'Child not synced for composite item : ' . $zoho_comp_item_id));
                        continue;
                    }
                    $product_res = $wpdb->get_row('SELECT * FROM ' . $wpdb->postmeta . " WHERE meta_key='zi_item_id' AND meta_value='" . $zoho_comp_item_id . "'");
                    if (!empty($product_res->post_id)) {
                        $com_prod_id = $product_res->post_id;
                    } else {
                        // Check if item is allowed to import or not.
                        if ($allow_to_import) {
                            $product_class = new ProductClass();
                            $com_prod_id = $product_class->zi_product_to_woocommerce($comp_item, $stock, 'composite');
                            update_post_meta($com_prod_id, 'zi_item_id', $zoho_comp_item_id);
                        } else {
                            // Import not allowed.
                        }
                    }
                }
                // Map composite items to database.
                if (!empty($com_prod_id)) {
                    wp_set_object_terms($com_prod_id, 'bundle', 'product_type');
                    foreach ($child_items as $child_prod) {
                        // Adding product to bundle.
                        $child_bundle_id = $this->add_bundle_product($child_prod->prod_id, $com_prod_id);
                        if ($child_bundle_id) {
                            foreach ($child_prod->metadata as $bundle_meta_key => $bundle_meta_val) {
                                $this->zi_update_bundle_meta($child_bundle_id, $bundle_meta_key, $bundle_meta_val);
                            }
                        }
                    }
                }
                // --------------------------------------------------------------------.

                $is_synced_flag = false; // loggin purpose only .

                // Tags update.
                if (!empty($com_prod_id)) {
                    $item_tags_hash = $comp_item->custom_field_hash;
                    $item_tags = $item_tags_hash->cf_tags;

                    if (!empty($item_tags)) {
                        $final_tags = explode(',', $item_tags);
                        wp_set_object_terms($com_prod_id, $final_tags, 'product_tag');
                    }
                }

                foreach ($comp_item as $key => $value) {
                    if ($key == 'status') {
                        if (!empty($com_prod_id)) {
                            $status = $value == 'active' ? 'publish' : 'draft';
                            $wpdb->update($tbl_prefix . 'posts', array('post_status' => $status), array('ID' => $com_prod_id), array('%s'), array('%d'));
                        }
                    }
                    if ($key == 'description') {
                        if (!empty($com_prod_id) && !empty($value)) {
                            $wpdb->update($tbl_prefix . 'posts', array('post_excerpt' => $value), array('ID' => $com_prod_id), array('%s'), array('%d'));
                        }
                    }
                    if ($key == 'name') {
                        if (!empty($com_prod_id)) {
                            $wpdb->update($tbl_prefix . 'posts', array('post_title' => $value), array('ID' => $com_prod_id), array('%s'), array('%d'));
                        }
                    }
                    if ($key == 'sku') {
                        if (!empty($com_prod_id)) {
                            update_post_meta($com_prod_id, '_sku', $value);
                        }
                    }
                    // Check if stock sync allowed by plugin.
                    if ($key === 'available_stock' || $key === 'actual_available_stock') {
                        $zi_stock_sync = get_option('zoho_stock_sync_status');
                        if ($zi_stock_sync != 'true') {
                            if ($stock) {
                                if (!empty($com_prod_id)) {
                                    // If value is less than 0 default 1.
                                    $stock_quantity = $stock < 0 ? 0 : $stock;
                                    update_post_meta($com_prod_id, '_stock', number_format($stock_quantity, 0, '.', ''));
                                    if ($stock_quantity > 0) {
                                        $status = 'instock';
                                    } else {
                                        $backorder_status = get_post_meta($com_prod_id, '_backorders', true);
                                        $status = ($backorder_status === 'yes') ? 'onbackorder' : 'outofstock';
                                    }
                                    update_post_meta($com_prod_id, '_stock_status', wc_clean($status));
                                    wp_set_post_terms($com_prod_id, $status, 'product_visibility', true);
                                    update_post_meta($com_prod_id, '_wc_pb_bundled_items_stock_status', wc_clean($status));
                                }
                            }
                        }
                    }
                    if ($key == 'rate') {
                        if (!empty($com_prod_id)) {
                            $sale_price = get_post_meta($com_prod_id, '_sale_price', true);
                            if (empty($sale_price)) {
                                update_post_meta($com_prod_id, '_price', $value);
                                update_post_meta($com_prod_id, '_regular_price', $value);
                                update_post_meta($com_prod_id, '_wc_pb_base_price', $value);
                                update_post_meta($com_prod_id, '_wc_pb_base_regular_price', $value);
                                update_post_meta($com_prod_id, '_wc_sw_max_regular_price', $value);
                            } else {
                                update_post_meta($com_prod_id, '_regular_price', $value);
                                update_post_meta($com_prod_id, '_wc_pb_base_price', $value);
                                update_post_meta($com_prod_id, '_wc_pb_base_regular_price', $value);
                                update_post_meta($com_prod_id, '_wc_sw_max_regular_price', $value);
                            }
                        }
                    } elseif ($key == 'image_document_id') {
                        if (!empty($com_prod_id) && !empty($value)) {
                            $imageClass = new ImageClass();
                            $imageClass->args_attach_image($zoho_comp_item_id, $comp_item->name, $com_prod_id, $comp_item->image_name, $admin_author_id);
                        }
                    } elseif ($key == 'category_name') {
                        if (!empty($com_prod_id) && $comp_item->category_name != '') {
                            $term = get_term_by('name', $comp_item->category_name, 'product_cat');
                            $term_id = $term->term_id;
                            if (empty($term_id)) {
                                $term = wp_insert_term(
                                    $comp_item->category_name,
                                    'product_cat',
                                    array(
                                        'parent' => 0,
                                    )
                                );
                                $term_id = $term['term_id'];
                            }
                            if ($term_id) {
                                // update_post_meta($com_prod_id, 'zi_category_id', $category);
                                // wp_set_object_terms($com_prod_id, $term_id, 'product_cat');
                                $existingTerms = wp_get_object_terms($com_prod_id, 'product_cat');
                                if ($existingTerms && count($existingTerms) > 0) {
                                    $isTermsExist = $this->zi_check_terms_exists($existingTerms, $term_id);
                                    if (!$isTermsExist) {
                                        update_post_meta($com_prod_id, 'zi_category_id', $category);
                                        wp_add_object_terms($com_prod_id, $term_id, 'product_cat');
                                    }
                                } else {
                                    update_post_meta($com_prod_id, 'zi_category_id', $category);
                                    wp_set_object_terms($com_prod_id, $term_id, 'product_cat');
                                }
                            }
                            // Remove "uncategorized" category if assigned
                            $uncategorized_term = get_term_by('slug', 'uncategorized', 'product_cat');
                            if ($uncategorized_term && has_term($uncategorized_term->term_id, 'product_cat', $pdt_id)) {
                                wp_remove_object_terms($pdt_id, $uncategorized_term->term_id, 'product_cat');
                            }
                        }
                    }
                }

                // sync dimensions and weight
                $item_url = "{$zi_url}api/v1/compositeitems/{$zoho_comp_item_id}?organization_id={$zi_org_id}";
                $this->zi_item_dimension_weight($item_url, $com_prod_id, true);

                // If item synced append to log : logging purpose only.
                if ($is_synced_flag) {
                    array_push($response_msg, $this->zi_response_message('SUCCESS', 'Composite item synced for id : ' . $comp_item->composite_item_id, $com_prod_id));
                }
            }

            if ($json->page_context->has_more_page) {
                $page++;
                $this->recursively_sync_composite_item_from_zoho($page, $category, $source);
            }

        } else {
            array_push($response_msg, $this->zi_response_message($code, $json->message));
        }
        // fclose($fd); // End of logging
        return $response_msg;
    }

    /**
     * Function to retrieve item details, update weight and dimensions.
     *
     * @param string $url - URL to ge details.
     * @return mixed return true if data false if error.
     */
    public function zi_item_dimension_weight($url, $product_id, $is_composite = false)
    {
        // $fd = fopen(__DIR__ . '/zi_item_dimension_weight.txt', 'a+');
        // Check if item is for syncing purpose.
        $executeCurlCallHandle = new ExecutecallClass();
        $json = $executeCurlCallHandle->ExecuteCurlCallGet($url);
        $code = $json->code;
        $message = $json->message;
        if (0 == $code || '0' == $code) {
            if ($is_composite) {
                // fwrite($fd, PHP_EOL . '$json  : ' . print_r($json, true));
                $details = $json->composite_item->package_details;
            } else {
                $details = $json->item->package_details;
            }
            $product = wc_get_product($product_id);
            $product->set_weight(floatval($details->weight));
            $product->set_length(floatval($details->length));
            $product->set_width(floatval($details->width));
            $product->set_height(floatval($details->height));
            $product->save();
        } else {
            false;
        }
        // fclose($fd);
    }

    /**
     * Create response object based on data.
     *
     * @param mixed  $index_col - Index value error message.
     * @param string $message - Response message.
     * @return object
     */
    public function zi_response_message($index_col, $message, $woo_id = '')
    {
        return (object) array(
            'resp_id' => $index_col,
            'message' => $message,
            'woo_prod_id' => $woo_id,
        );
    }

    /**
     * Helper Function to check if terms already exists.
     */
    public function zi_check_terms_exists($existingTerms, $term_id)
    {
        foreach ($existingTerms as $woo_existing_term) {
            if ($woo_existing_term->term_id === $term_id) {
                return true;
            } else {
                return false;
            }
        }
    }

}
$importProductClass = new ImportProductClass;
