<?php

/**
 * Class for handling Zoho Inventory order sync related functions.
 */
class Sync_Order_Class
{

    /**
     * Initialize the class.
     */
    public function __construct()
    {
        add_action("woocommerce_rest_insert_shop_order_object", array($this, 'on_insert_rest_api'), 20, 3);
        add_filter('wcs_renewal_order_created', array($this, 'sync_renewal_order'), 10, 2);
        add_action('wp_ajax_zoho_admin_order_sync', array($this, 'zi_order_sync'));
        add_action('woocommerce_update_order', array($this, 'salesorder_void'));
    }

    /**
     * Sync order when its scheduled via the Action Scheduler.
     * 
     * @param int $order_id Order ID.
     * @return void
     */
    public function zi_orders_prepare_sync()
    {
        $args = func_get_args();
        $order_id = $args[0];
        if(!get_option('zoho_inventory_access_token') || !$order_id) {
            return;
        }
        $this->zi_order_sync($order_id);
    }

    /**
     * Sync order when it's created via the WC API.
     *
     * @param WC_Data $object Inserted object.
     * @param WP_REST_Request $request Request object.
     * @param boolean $creating True when creating object, false when updating.
     */
    public function on_insert_rest_api($object, $request, $is_creating)
    {
        if (!get_option('zoho_inventory_access_token')) {
            return;
        }
        // $fd = fopen(__DIR__ . '/on_insert_rest_api.txt', 'w+');
        $request_body = $request->get_body();
        $request_body_array = json_decode($request_body, true);
        $order_status = $request_body_array['status'];
        $order_id = $object->get_id();

        // Check how many keys there are in the request body array. If there are only two keys then we don't need to do anything.
        if (count($request_body_array) === 2) {
            if (in_array($order_status, array('cancelled', 'wc-merged'))) {
                $this->salesorder_void($order_id);
            }
        } else {
            $this->zi_order_sync($order_id);
        }
        // fclose($fd);
    }

    /**
     * Sync Renewal Order to Zoho once it's created.
     */
    public function sync_renewal_order($renewal_order, $subscription)
    {
        if (!get_option('zoho_inventory_access_token')) {
            return $renewal_order;
        }

        $order_id = $renewal_order->get_id();
        $this->zi_order_sync($order_id);

        return $renewal_order;
    }

    /**
     * Function to map customer on checkout before placing order
     * @param int $order_id Order ID.
     * 
     */
    public function zi_sync_customer_checkout($order_id)
    {
        // $fd = fopen(__DIR__ . '/zi_sync_customer_checkout.txt', 'w+');

        $order = wc_get_order($order_id);
        $userid = $order->get_user_id();
        $user_company = $order->get_billing_company();
        $user_email = $order->get_billing_email();
        $zi_customer_id = get_user_meta($userid, 'zi_contact_id', true);

        // Get currency code of the order
        $currency_id = intval(get_user_meta($userid, 'zi_currency_id', true));
        if (empty($currency_id)) {
            $currency_code = $order->get_currency();
            $multiCurrencyHandle = new MulticurrencyClass();
            $currency_id = $multiCurrencyHandle->ZohoCurrencyData($currency_code, $userid);
        }

        if ($zi_customer_id) {
            $zoho_inventory_oid = get_option('zoho_inventory_oid');
            $zoho_inventory_url = get_option('zoho_inventory_url');
            $get_url = $zoho_inventory_url . 'api/v1/contacts/' . $zi_customer_id . '/?organization_id=' . $zoho_inventory_oid;

            $executeCurlCallHandle = new ExecutecallClass();
            $json = $executeCurlCallHandle->ExecuteCurlCallGet($get_url);

            // fwrite($fd,PHP_EOL.'customer_json: '.print_r($json, true));

            $code = $json->code;
            if ($code != 0 || $code != '0') {
                delete_user_meta($userid, 'zi_contact_id');
                delete_user_meta($userid, 'zi_billing_address_id');
                delete_user_meta($userid, 'zi_primary_contact_id');
                delete_user_meta($userid, 'zi_shipping_address_id');
                delete_user_meta($userid, 'zi_created_time');
                delete_user_meta($userid, 'zi_last_modified_time');
                $zi_customer_id = '';
            }
        }

        /**
         * syncing customer if its not in Zoho yet
         */
        if (!$zi_customer_id) {

            // First check based on customer email address
            $zoho_inventory_oid = get_option('zoho_inventory_oid');
            $zoho_inventory_url = get_option('zoho_inventory_url');
            // fwrite($fd,PHP_EOL.'$user_mail : '.$user_email);
            $url = $zoho_inventory_url . 'api/v1/contacts?organization_id=' . $zoho_inventory_oid . '&email=' . $user_email;

            $executeCurlCallHandle = new ExecutecallClass();
            $json = $executeCurlCallHandle->ExecuteCurlCallGet($url);

            $code = $json->code;
            $message = $json->message;
            if ($code == 0 || $code == '0') {
                // fwrite($fd, PHP_EOL . 'customer_json: ' . print_r($json, true));
                if (empty($json->contacts)) {
                    // Second check based on Company Name
                    if ($user_company) {
                        $company_name = str_replace(" ", "%20", $user_company);
                        $url = $zoho_inventory_url . 'api/v1/contacts?organization_id=' . $zoho_inventory_oid . '&filter_by=Status.Active&search_text=' . $company_name;

                        $executeCurlCallHandle = new ExecutecallClass();
                        $json = $executeCurlCallHandle->ExecuteCurlCallGet($url);

                        $code = $json->code;
                        if ($code == 0 || $code == '0') {
                            if (empty($json->contacts)) {
                                $contactClassHandle = new ContactClass();
                                $zi_customer_id = $contactClassHandle->ContactCreateFunction($userid);
                            } else {
                                foreach ($json->contacts[0] as $key => $value) {
                                    if ($key == 'contact_id') {
                                        $zi_customer_id = $value;
                                        update_user_meta($userid, 'zi_contact_id', $zi_customer_id);
                                    }
                                }
                                $contactClassHandle = new ContactClass();
                                $zi_customer_id = $contactClassHandle->Create_contact_person($userid);
                            }
                        }
                    }
                    $contactClassHandle = new ContactClass();
                    $zi_customer_id = $contactClassHandle->ContactCreateFunction($userid);
                } else {
                    // fwrite($fd,PHP_EOL.'Contacts : '.print_r($json->contacts,true));
                    foreach ($json->contacts[0] as $key => $value) {
                        if ($key == 'contact_id') {
                            $zi_customer_id = $value;
                            update_user_meta($userid, 'zi_contact_id', $zi_customer_id);
                        }
                    }
                }
            }
            // Http request not processed properly.
            // echo $message;
            return $zi_customer_id;
        } else {
            $zoho_inventory_oid = get_option('zoho_inventory_oid');
            $zoho_inventory_url = get_option('zoho_inventory_url');
            $get_url = $zoho_inventory_url . 'api/v1/contacts/' . $zi_customer_id . '/contactpersons/?organization_id=' . $zoho_inventory_oid;

            $executeCurlCallHandle = new ExecutecallClass();
            $contactp_res = $executeCurlCallHandle->ExecuteCurlCallGet($get_url);

            // fwrite($fd, PHP_EOL . 'Contact Response: ' . print_r($contactp_res->code, true));

            // first check within contactpersons endpoint and then map it with that contactperson if email-id matches
            if ($contactp_res->code == 0 || $contactp_res->code == '0') {
                if (!empty($contactp_res->contact_persons)) {
                    foreach ($contactp_res->contact_persons as $key => $contact_persons) {
                        $person_email = trim($contact_persons->email);
                        if ($person_email == trim($user_email)) {
                            /* Match Contact */
                            $contactid = $contact_persons->contact_person_id;
                            update_user_meta($userid, 'zi_contactperson_id_' . $key, $contactid);
                            if ($contact_persons->is_primary_contact == true || $contact_persons->is_primary_contact == 1) {
                                $contactClassHandle = new ContactClass();
                                $contactClassHandle->ContactUpdateFunction($userid, $order_id);
                            } else {
                                $contactClassHandle = new ContactClass();
                                $res = $contactClassHandle->Update_contact_person($userid, $contactid);
                            }
                        }
                    }
                } else {
                    $get_url = $zoho_inventory_url . 'api/v1/contacts/' . $zi_customer_id . '/?organization_id=' . $zoho_inventory_oid;
                    $contact_res = $executeCurlCallHandle->ExecuteCurlCallGet($get_url);
                    if (($contact_res->code == 0 || $contact_res->code == '0') && !empty($contact_res->contact)) {
                        foreach ($contact_res as $contact_) {
                            if (trim($contact_->email) == trim($user_email)) {
                                $contactClassHandle = new ContactClass();
                                $contactClassHandle->ContactUpdateFunction($userid, $order_id);
                            } else {
                                $contactClassHandle = new ContactClass();
                                $contactClassHandle->Create_contact_person($userid);
                            }
                        }
                    }
                }
            }
        }
        // fclose($fd);
    }

    /**
     * Function for admin zoho sync call.
     *
     * @param int $order_id Order ID.
     */
    public function zi_order_sync($order_id)
    {
        // $fd = fopen(__DIR__ . '/backend_order.txt', 'a+');

        if (!$order_id) {
            $order_id = $_POST['arg_order_data'];
        }

        global $wpdb;
        $order = wc_get_order($order_id);
        $orders_date = $order->get_date_created()->format('Y-m-d');
        $i = 1;
        $zi_sales_order_id = $order->get_meta('zi_salesorder_id');
        $userid = $order->get_user_id();
        $order_status = $order->get_status();
        $note = $order->get_customer_note();
        $notes = preg_replace('/[^A-Za-z0-9\-]/', ' ', $note);
        $total_shipping = $order->get_shipping_total();
        $shipping_method = $order->get_shipping_method();
        $discount_amt = $order->get_total_discount();

        // // Get WC Subscription Signup fee
        // $adjustment = '';
        // if (class_exists('WC_Subscriptions_Order') && wcs_order_contains_subscription($order_id)) {
        //     $adjustment = WC_Subscriptions_Order::get_sign_up_fee($order);
        // }

        foreach ($order->get_items() as $item_id => $item) {
            // fwrite($fd, PHP_EOL . '-----------------------------------');
            $sale_order['order']['suborder'][$i]['order_id'] = $item_id;
            $sale_order['order']['suborder'][$i]['product_id'] = $item->get_product_id();
            $sale_order['order']['suborder'][$i]['variation_id'] = $item->get_variation_id();
            $item_data = $item->get_data();
            $sale_order['order']['suborder'][$i]['quantity'] = $item_data['quantity'];
            $sale_order['order']['suborder'][$i]['post_order_id'] = $item_data['order_id'];
            $sale_order['order']['suborder'][$i]['total'] = round($item_data['total'], 2);
            $sale_order['order']['suborder'][$i]['subtotal'] = round($item_data['subtotal'], 2);
            $sale_order['order']['suborder'][$i]['item_price'] = $item_data['subtotal'] / $item_data['quantity'];

            // WC Product-Addons support
            $formatted_meta_data = $item->get_formatted_meta_data();

            if (!empty($formatted_meta_data)) {
                foreach ($formatted_meta_data as $metavalue) {

                    $metaArr[] = $metavalue->display_key . ' : ' . trim(strip_tags($metavalue->display_value)) . '\n';
                }
                $product_meta_str = implode("", $metaArr);

                if ($product_meta_str) {
                    $sale_order['order']['suborder'][$i]['product_desc'] = $product_meta_str;
                } else {
                    $sale_order['order']['suborder'][$i]['product_desc'] = '';
                }
            }
            $i++;
        }

        if (is_array($sale_order)) {

            // If user id and email is empty then break process.
            if (empty($userid) && empty($user_email)) {
                // fwrite($fd,PHP_EOL.'ALL EMPTY');
                $order->add_order_note('Zoho Order Sync: guest orders are not supported');
                $order->save();
                return;
            }
            $valOrder = array_shift($sale_order);
            // fwrite($fd, PHP_EOL . 'USER ID : ' . $userid);
            $zi_customer_id = get_user_meta($userid, 'zi_contact_id', true);
            $billing_id = get_user_meta($userid, 'zi_billing_address_id', true);
            $shipping_id = get_user_meta($userid, 'zi_shipping_address_id', true);
            $discount_amount = ($discount_amt) ? $discount_amt : 0;
            $user_email = get_user_meta($userid, 'billing_email', true);
            $enable_incl_tax = get_option('woocommerce_prices_include_tax');

            if ($order_status != 'failed') {

                if(empty($zi_customer_id)) {
                    $zi_customer_id = $this->zi_sync_customer_checkout($order_id);
                }
                // fwrite($fd,PHP_EOL.'$zi_customer_id : '.$zi_customer_id);
                $order_items = $order->get_items('coupon');
                $discount_type = '';
                foreach ($order->get_coupon_codes() as $coupon_code) {
                    // Get the WC_Coupon object
                    $coupon = new WC_Coupon($coupon_code);
                    $discount_type = $coupon->get_discount_type(); // Get coupon discount type
                }
                // fwrite($fd,PHP_EOL.'Discount coupon type: '.$discount_type);
                $index = 0;
                foreach ($valOrder['suborder'] as $key => $val) {

                    $proid = $val['product_id'];
                    $proidv = $val['variation_id'];
                    $is_variable_item = false;
                    if ($proidv > 0) {
                        $proid = $proidv;
                        $item_id = get_post_meta($proid, 'zi_item_id', true);
                        $is_variable_item = true;
                    } else {
                        $is_variable_item = false;
                        $item_id = get_post_meta($proid, 'zi_item_id', true);
                    }
                    if (empty($item_id)) {
                        $productHandler = new ProductClass();
                        $product_response = $productHandler->zi_product_sync($proid);
                        // fwrite($fd,PHP_EOL.'Product sync: '.print_r($product_response, true));
                    }
                    // Skip bundled order items
                    if(function_exists('wc_pb_is_bundled_order_item')) {
                        $order_item = $order->get_item($val['order_id']);
                        $is_bundled = wc_pb_is_bundled_order_item( $order_item, $order );
                        if( $is_bundled ) {
                            continue;
                        }
                    }

                    $product_description = isset($val['product_desc']) ? $val['product_desc'] : '';
                    $product_desc = str_replace('"', '', $product_description);
                    $discount_per_item = '';

                    $qty = ($val['quantity']) ? $val['quantity'] : 1;
                    $item_price = $val['item_price'];
                    // adding warehouse_id in line items array
                    $warehouse_id = get_option('zoho_warehouse_id');
                    if ($warehouse_id > 0) {
                        $warehouse_id = '"warehouse_id": "' . $warehouse_id . '"';
                    } else {
                        $warehouse_id = '';
                    }
                    /* Coupons used in the order*/
                    // Check if coupon applied on order.
                    if (!empty($order_items) && 'percent' === $discount_type) {
                        global $wpdb;
                        $discount_per_item = 0;
                        $table = $wpdb->prefix . 'wc_order_product_lookup';
                        // fwrite($fd, PHP_EOL.'$tb : '.$tb.' | $order_id : '.$order_id.'| $proid : '.$proid);
                        do {
                            if ($is_variable_item) {
                                $res_coupon = $wpdb->get_row($wpdb->prepare("select * from " . $table . " where order_id = " . $order_id . " and variation_id = " . $proid), ARRAY_A);
                            } else {
                                $res_coupon = $wpdb->get_row($wpdb->prepare("select * from " . $table . " where order_id = " . $order_id . " and product_id = " . $proid), ARRAY_A);
                            }
                        } while (empty($res_coupon));
                        // fwrite($fd, PHP_EOL.'$res_coupon : '. print_r($res_coupon, true));
                        if ($res_coupon['product_net_revenue'] || $discount_amount) {
                            // Get net revenue per item.
                            $g_price = $res_coupon['product_net_revenue'] / $res_coupon['product_qty'];
                            $d_price = ($item_price - $g_price);
                            $d_price = (($d_price / $item_price) * 100);
                            $discount_per_item = round($d_price, 2) . '%';
                        }

                        $discount_per_item = '"discount": "' . $discount_per_item . '",';
                    } elseif (!empty($order_items)) { // fixed_product ===$discount_type
                        // fwrite($fd,PHP_EOL.'Going inside else');
                        $item_price = $val['total'] / $qty;
                    }
                    // Format item price upto two decimal places.
                    $item_price1 = round($item_price, 2);

                    // if there is vat exempt tax
                    $order_id = $val['post_order_id'];
                    $vat_exempt = $order->get_meta('is_vat_exempt');
                    $zoho_tax_id = '';
                    $taxid = '';
                    $tax_value = $order->get_total_tax();
                    // Apply tax rates zero only if order has no values
                    if ($vat_exempt == 'yes' || empty($tax_value)) {
                        $zoho_tax_id = get_option('zi_vat_exempt', true);
                        $taxid = '"tax_id": "' . $zoho_tax_id . '",';
                    } else {
                        foreach ($order->get_items('tax') as $item_key => $item) {
                            $tax_rate_id = $item->get_rate_id(); // Tax rate ID
                            $tax_percent = WC_Tax::get_rate_percent($tax_rate_id);
                            $tax_total = $item_price1 * ($tax_percent / 100);
                            $option_table = $wpdb->prefix . 'options';
                            $tax_option_object = $wpdb->get_row($wpdb->prepare("SELECT * FROM $option_table WHERE option_value LIKE '%s' LIMIT 1", "%##tax##$tax_percent"));
                            $tax_option = $tax_option_object->option_value;
                            if ($tax_option) {
                                // fwrite($fd, PHP_EOL.'Inside Tax Option: '. $tax_option);
                                $tax_id = explode('##', $tax_option)[0];
                            }
                            $taxid = '"tax_id": "' . $tax_id . '",';
                        }
                        $item_price = $tax_total + $item_price1;
                    }
                    if ($enable_incl_tax == 'yes') {
                        $pdt_items[] = '{"item_id": "' . $item_id . '","description": "' . $product_desc . '","quantity": "' . $qty . '",' . $taxid . '' . $discount_per_item . '"rate": "' . $item_price . '",' . $warehouse_id . '}';
                    } else {
                        $pdt_items[] = '{"item_id": "' . $item_id . '","description": "' . $product_desc . '","quantity": "' . $qty . '",' . $taxid . '' . $discount_per_item . '"rate": "' . $item_price1 . '",' . $warehouse_id . '}';
                    }
                    $index++;
                }

                // Shipping Tax
                $shipping_tax_id = '';
                $shipping_tax = $order->get_shipping_tax();
                $shipping_tax_total = $order->get_shipping_total();

                if (!empty($shipping_tax) && !empty($shipping_tax_total)) {

                    $zoho_enable_decimal_tax = get_option('zoho_enable_decimal_tax_status');
                    $tax_percentage = (($shipping_tax / $shipping_tax_total) * 100);

                    if ('true' == $zoho_enable_decimal_tax) {
                        $percentage = number_format($tax_percentage, 2);
                        $percent_decimal = $percentage * 100;
                        $decimal_place = $percent_decimal % 10;
                        if ($decimal_place === 0) {
                            $percentage = number_format($percentage, 1);
                        }
                    } else {
                        $percentage = round($tax_percentage);
                    }

                    $table_prefix = $wpdb->prefix;

                    $row_match = $wpdb->get_row("select * from " . $table_prefix . "options where option_name LIKE '%zoho_inventory_tax_rate_%' and option_value LIKE '%##" . $percentage . "%'", ARRAY_A);

                    //  fwrite($fd,PHP_EOL.'$row_match : '.print_r($row_match,true));
                    if ($row_match['option_value']) {

                        $shipping_tax_total_ex = explode('##', $row_match['option_value']);
                        //  fwrite($fd,PHP_EOL.'Option value : '.$row_match['option_value']);
                        //  fwrite($fd,PHP_EOL.'Option value : '.print_r($shipping_tax_total_ex,true));
                        $shipping_tax_id = $shipping_tax_total_ex[0];
                        $shipping_tax_per = end($shipping_tax_total_ex);
                    }
                }
                if (is_array($pdt_items)) {
                    $impot = implode(',', $pdt_items);
                }

                $pdt1 = '"customer_id": "' . $zi_customer_id . '","date": "' . $orders_date . '","line_items": [' . $impot . '],"is_discount_before_tax": "true","discount_type": "item_level","price_precision":"2","notes": "' . $notes . '","billing_address_id": "' . $billing_id . '","shipping_address_id": "' . $shipping_id . '","delivery_method": "' . $shipping_method . '"';

                // if there is shipping tax
                if (!empty($shipping_tax)) {
                    $pdt1 .= ',"shipping_charge_tax_id":"' . $shipping_tax_id . '"';
                }

                // Check if there are order fees total is more than 0
                $order_fees = $order->get_fees();
                // $transaction_fee = get_transaction_fees($order_id);
                if (!empty($order_fees)) {
                    foreach ($order_fees as $order_fee) {
                        $fee_name = $order_fee->get_name();
                        $fee_total = $order_fee->get_total();
                    }
                    if ($fee_total > 0) {
                        $pdt1 .= ',"adjustment":' . $fee_total . '';
                        $pdt1 .= ',"adjustment_description":"' . $fee_name . '"';
                    }
                }
                // } elseif (!empty($transaction_fee)) {
                //     $pdt1 .= ',"adjustment":"' . -$transaction_fee . '"';
                //     $pdt1 .= ',"adjustment_description":"Stripe Fee"';
                // }

                $response_msg = '';

                // Send orders as confirmed
                if ('true' == get_option('zoho_enable_order_status')) {
                    $pdt1 .= ',"order_status": "draft"';
                } else {
                    $pdt1 .= ',"order_status": "confirmed"';
                }

                // if items are incl. tax
                $total_shipping1 = $total_shipping + $shipping_tax;
                if ($enable_incl_tax == 'yes') {
                    $pdt1 .= ',"is_inclusive_tax": true';
                    $pdt1 .= ',"shipping_charge":"' . round($total_shipping1, 2) . '"';
                } else {
                    $pdt1 .= ',"is_inclusive_tax": false';
                    $pdt1 .= ',"shipping_charge":"' . round($total_shipping, 2) . '"';
                }

                // Custom Field mapping with zoho.
                $getmappedfields = get_option('wootozoho_custom_fields');
                $customfield = ',"custom_fields":[';

                $data = json_decode($getmappedfields, true);
                if ($data !== null) {
                    $count = count($data);
                    $i = 0;
                    foreach ($data as $key => $label) {
                        // Get the meta value which is the meta_key
                        $metavalue = $order->get_meta($key);
                        // Add the custom field to the JSON string
                        $customfield .= '{"label": "' . $label . '","value":"' . $metavalue . '"}';
                        // Add comma if it's not the last iteration
                        if (++$i < $count) {
                            $customfield .= ',';
                        }
                    }
                }
                $pdt1 .= $customfield . ']';

                // If auto order number is enabled.
                $enabled_auto_no = get_option('zoho_enable_auto_no_status');
                $transaction_id = $order->get_transaction_id();
                if (empty($transaction_id)) {
                    $transaction_id = $order->get_meta('_order_number', true);
                }
                $order_prefix = get_option('order-prefix');
                $reference_no = '';
                if (class_exists('WCJ_Order_Numbers') || class_exists('WC_Seq_Order_Number_Pro')) {
                    $reference_no = $order_prefix . $transaction_id;
                } elseif (!empty($order_prefix)) {
                    $reference_no = $order_prefix . '-' . $order_id;
                } else {
                    $reference_no = 'WC-' . $order_id;
                }

                if ($enabled_auto_no == 'true') {
                    $pdt1 .= ',"reference_number": "' . $reference_no . '"';
                } else {
                    $pdt1 .= ',"salesorder_number": "' . $order_id . '"';
                }

                // fwrite($fd, PHP_EOL . '$pdt1 : {' . $pdt1 . '}');

                if ($zi_sales_order_id != '') {
                    $response_msg = $this->single_saleorder_zoho_inventory_update($order_id, $zi_sales_order_id, $pdt1);
                    // fwrite($fd,PHP_EOL.'Update response : '.$response_msg);
                } else {
                    $response_msg = $this->single_saleorder_zoho_inventory($order_id, $pdt1);
                }
                // fwrite($fd,PHP_EOL.'Update response : '. print_r($response_msg, true));

                $order->update_meta_data('zi_body_request', $pdt1);

                $notes = 'Zoho Order Sync: ' . $response_msg['message'];

                // fclose($fd); // end logging
                $order->add_order_note($notes);
                $order->update_meta_data('zi_salesorder_id', $response_msg['zi_salesorder_id']);
                $order->save();
            }
            return;
        }
    }

    /**
     * Void the sales order if cancelled in WooCommerce.
     *
     * @param int $order_id Order ID.
     */
    public function salesorder_void($order_id)
    {
        if (!$order_id) {
            return;
        }

        $order = wc_get_order($order_id);
        // return if order is already voided
        if ($order->get_meta('zi_salesorder_void', true)) {
            return;
        }
        $order_status = $order->get_status();

        if ($order_status == 'cancelled' || $order_status == 'wc-merged') {
            $zi_sales_order_id = $order->get_meta('zi_salesorder_id', true);
            $zoho_inventory_oid = get_option('zoho_inventory_oid');
            $zoho_inventory_url = get_option('zoho_inventory_url');

            $url = $zoho_inventory_url . 'api/v1/salesorders/' . $zi_sales_order_id . '/status/void?organization_id=' .
                $zoho_inventory_oid;
            $data = '';
            $executeCurlCallHandle = new ExecutecallClass();
            $json = $executeCurlCallHandle->ExecuteCurlCallPost($url, $data);

            $errmsg = $json->message;
            $code = $json->code;
            if ($code == '0' || $code == 0) {
                // Add order meta key "zi_salesorder_void" to true
                $order->update_meta_data('zi_salesorder_void', true);
                $order->add_order_note("Zoho Order Void: " . $errmsg);
                $order->save();
                return;
            }
        } else {
            return;
        }
    }

    /**
     * Sync order from Woo to Zoho.
     * 
     * @param int $order_id Order ID.
     * @param string $pdt1 JSON string
     * @return string Error message
     */
    public function single_saleorder_zoho_inventory($order_id, $pdt1)
    {
        //start logging
        // $fd = fopen(__DIR__ . '/order-sync-backend.txt', 'w+');

        $zoho_inventory_oid = get_option('zoho_inventory_oid');
        $zoho_inventory_url = get_option('zoho_inventory_url');

        $data = array(
            'JSONString' => '{' . $pdt1 . '}',
            'organization_id' => $zoho_inventory_oid,
        );

        //logging
        // fwrite($fd, PHP_EOL . 'Data log : ' . print_r($data, true));

        $enabled_auto_no = get_option('zoho_enable_auto_no_status');
        $ignore_auto_no = ('true' === $enabled_auto_no) ? 'false' : 'true';
        $url = $zoho_inventory_url . 'api/v1/salesorders?ignore_auto_number_generation=' . $ignore_auto_no;

        $executeCurlCallHandle = new ExecutecallClass();
        $json = $executeCurlCallHandle->ExecuteCurlCallPost($url, $data);

        // fwrite($fd, PHP_EOL . 'Data log : ' . print_r($json, true));
        $response = array();
        $code = $json->code;
        // fwrite($fd, PHP_EOL . 'Code : ' . $code);

        if ($code == '0' || $code == 0) {
            foreach ($json->salesorder as $key => $value) {

                if ($key == 'salesorder_id') {
                    $response['zi_salesorder_id'] = $value;
                    // $order->add_meta_data('zi_salesorder_id', $value, true);
                }
                // saleorder package code
                $zoho_package_status = get_option('zoho_package_zoho_sync_status');
                if ($zoho_package_status === 'true') {
                    $packageCurlCallHandle = new PackageClass();
                    $json = $packageCurlCallHandle->PackageCreateFunction($order_id, $json);
                }
            }
        }
        $errmsg = $json->message;
        $response['message'] = $errmsg;
        return $response;
        // end logging
        // fclose($fd);
    }

    /**
     * Function for updating single sales order.
     * 
     * @param int $order_id Order ID.
     * @param string $zi_sales_order_id
     * @param string $pdt1 JSON string
     * @return string Error message
     */
    public function single_saleorder_zoho_inventory_update($order_id, $zi_sales_order_id, $pdt1)
    {
        // $fd = fopen(__DIR__.'/single_saleorder_update.txt','w+');
        $response = array();
        $zoho_inventory_oid = get_option('zoho_inventory_oid');
        $zoho_inventory_url = get_option('zoho_inventory_url');

        $url = $zoho_inventory_url . 'api/v1/salesorders/' . $zi_sales_order_id;
        $data = array(
            'JSONString' => '{' . $pdt1 . '}',
            'organization_id' => $zoho_inventory_oid,
        );

        $order = wc_get_order($order_id);

        // fwrite($fd, PHP_EOL. print_r($data, true)); //logging response

        $executeCurlCallHandle = new ExecutecallClass();
        $json = $executeCurlCallHandle->ExecuteCurlCallPut($url, $data);

        // $code = $json->code;
        $errmsg = $json->message;
        $response['message'] = $errmsg;
        $response['zi_salesorder_id'] = $zi_sales_order_id;

        // echo '<pre>'; print_r($errmsg);
        $zoho_package_status = get_option('zoho_package_zoho_sync_status');
        $package_id = $order->get_meta('zi_package_id', true);

        if (empty($package_id) && 'true' === $zoho_package_status) {
            // fwrite($fd, PHP_EOL. 'inside new package create');
            // create new package
            $packageCurlCallHandle = new PackageClass();
            $resp_package = $packageCurlCallHandle->PackageCreateFunction($order_id, $json);
            // save response
            $resp_msg = $resp_package->message;
            $order->add_order_note('Zoho Package: ' . $resp_msg);
            $order->save();
        } elseif (!empty($package_id)) {
            // fwrite($fd, PHP_EOL. 'inside package exists'); //logging response

            foreach ($json->salesorder as $key => $value) {

                if ($key == 'salesorder_id') {
                    $salesorder_id = $value;
                }

                if ($key == 'salesorder_number') {
                    $package_number = $value;
                }

                if ($key == 'date') {
                    $shipDate = $value;
                }

                if ($key == 'line_items') {

                    $array1 = json_encode($value);

                    foreach ($value as $kk => $vv) {

                        $lineItems[] = '{"so_line_item_id": "' . $vv->line_item_id . '","quantity": "' . $vv->quantity . '"}';
                    }
                    $impot = implode(',', $lineItems);

                    $json_package = '"date": "' . $shipDate . '","line_items": [' . $impot . ']';

                    $url_package = $zoho_inventory_url . 'api/v1/packages/' . $package_id;
                    $data3 = array(
                        'JSONString' => '{' . $json_package . '}',
                        'organization_id' => $zoho_inventory_oid,
                    );

                    $res_package = $executeCurlCallHandle->ExecuteCurlCallPut($url_package, $data3);
                }
            }
        }

        // fclose($fd); //end of logging
        return $response;
    }

    /**
     * TODO: Get Order Transaction Fees
     * 
     * @param int $order_id Order ID.
     * @return float Transaction fees
     */
    protected function get_transaction_fees($order_id)
    {
        $order = wc_get_order($order_id);
        switch (true) {
            // get fees from Stripe, if exists
            case $fees = $order->get_meta("_stripe_fee");
                break;
            // get fees from Paypal, if exists
            case $fees = $order->get_meta("_paypal_transaction_fee"):
                break;
            // otherwise fee is 0
            default:
                $fees = 0;
                break;
        }
        return $fees;
    }
}
$sync_order_class = new Sync_Order_Class();