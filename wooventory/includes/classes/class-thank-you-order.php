<?php

/**
 * Class for syncing the Thankyou Order To Zoho
 *
 * @package  WooZo Inventory
 * @category Zoho Integration
 * @author   Roadmap Studios
 * @link     https://wooventory.com
 */

class OrderClass
{

    public function __construct()
    {
        $config = [

            'OrderZI' => [
                'OID' => get_option('zoho_inventory_oid'),
                'APIURL' => get_option('zoho_inventory_url'),
            ],

        ];

        return $this->config = $config;
    }

    public function zi_order_sync($order_id)
    {

        // $fd = fopen(__DIR__ . '/order-frontend.txt', 'w+');
        $order = wc_get_order($order_id);
        $zi_order_id = $order->get_meta('zi_salesorder_id', true);
        if (!empty($zi_order_id) || empty($order)) {
            return;
        }

        if (intval($zi_order_id) == 0) {
            global $wpdb;

            $zoho_sync_status = get_option('zoho_sync_status');
            if ($zoho_sync_status != 'true') {
                // get discount total
                $discount_total = $order->get_discount_total();
                // first check if discount exists and continue to get discount data if exists
                if ($discount_total > 0) {
                    // sleep(10);
                    do {
                        global $wpdb;
                        $tb = $wpdb->prefix . 'wc_order_product_lookup';
                        // fwrite($fd, PHP_EOL. 'searching order');
                        $order_db_check = $wpdb->get_row($wpdb->prepare("select * from " . $tb . " where order_id = " . $order_id), ARRAY_A);
                    } while (empty($order_db_check) || count($order_db_check) <= 0);
                    // fwrite($fd, PHP_EOL. 'order_db_check : found ');
                }

                $i = 1;
                $note = $order->get_customer_note();
                $notes = preg_replace('/[^A-Za-z0-9\-]/', ' ', $note);
                $sale_order['order']['customer_id'] = $order->get_user_id();
                $sale_order['order']['status'] = $order->get_status();
                $sale_order['order']['subtotal'] = $order->get_subtotal();
                $sale_order['order']['total_tax'] = $order->get_total_tax();
                $sale_order['order']['total_shipping'] = $order->get_total_shipping();
                $sale_order['order']['shipping_method'] = $order->get_shipping_method();
                $sale_order['order']['total_discount'] = $order->get_total_discount();
                // Get applied taxes to an order
                $applied_tax_zohoid = '';
                foreach ($order->get_items('tax') as $tax_item) {
                    $tax_total = $tax_item->get_tax_total(); // Get tax total amount (for this rate)
                    if ($tax_total > 0) {
                        $rate_id = $tax_item->get_rate_id(); // Get rate Id
                        $tax_option = get_option('zoho_inventory_tax_rate_' . $rate_id);
                        if (!empty($tax_option)) {
                            $applied_tax_zohoid = explode('##', $tax_option)[0];
                        }
                    }
                    if (!empty($applied_tax_zohoid)) {
                        break; // If first applied tax has zoho id found. break loop.
                    }
                }
                foreach ($order->get_items() as $item_id => $item) {

                    $sale_order['order']['suborder'][$i]['order_id'] = $item_id;
                    $sale_order['order']['suborder'][$i]['product_id'] = $item->get_product_id();
                    $sale_order['order']['suborder'][$i]['variation_id'] = $item->get_variation_id();

                    $item_data = $item->get_data();
                    $sale_order['order']['suborder'][$i]['quantity'] = $item_data['quantity'];
                    $sale_order['order']['suborder'][$i]['post_order_id'] = $item_data['order_id'];
                    // $sale_order['order']['suborder'][ $i ]['subtotal']      = $item_data['total'];
                    // $sale_order['order']['suborder'][ $i ]['tax_class']     = $item_data['tax_class'];
                    $sale_order['order']['suborder'][$i]['total'] = round($item_data['total'], 2);
                    $sale_order['order']['suborder'][$i]['subtotal'] = round($item_data['subtotal'], 2);
                    $sale_order['order']['suborder'][$i]['item_price'] = $item_data['subtotal'] / $item_data['quantity'];

                    // Check if product is Bundled By to add simple and variations product.
                    $order_item_meta_table = "{$wpdb->prefix}woocommerce_order_itemmeta";
                    $itemMeta = $wpdb->get_row("SELECT meta_value FROM $order_item_meta_table WHERE meta_key = '_bundled_by' AND order_item_id= $item_id LIMIT 1");
                    $sale_order['order']['suborder'][$i]['bundled_by'] = !empty($itemMeta) && $itemMeta->meta_value ? $itemMeta->meta_value : '';

                    //echo '<pre>'; print_r($item_data);
                    // WC Product-Addons support
                    $formatted_meta_data = $item->get_formatted_meta_data();

                    if (!empty($formatted_meta_data)) {
                        foreach ($formatted_meta_data as $metavalue) {

                            $metaArr[] = $metavalue->display_key . ' : ' . trim(strip_tags($metavalue->display_value)) . '\n';
                        }
                        if (is_array($metaArr)) {

                            $product_meta_str = implode("", $metaArr);

                            if ($product_meta_str) {
                                $sale_order['order']['suborder'][$i]['product_desc'] = $product_meta_str;
                            } else {
                                $sale_order['order']['suborder'][$i]['product_desc'] = '';
                            }
                        }
                    }
                    $i++;
                }

                if (is_array($sale_order)) {

                    foreach ($sale_order as $orderKey => $valOrder) {
                        $userid = $valOrder['customer_id'];
                        $zi_customer_id = get_user_meta($userid, 'zi_contact_id', true);
                        $billing_id = get_user_meta($userid, 'zi_billing_address_id', true);
                        $shipping_id = get_user_meta($userid, 'zi_shipping_address_id', true);
                        $user_company = get_user_meta($userid, 'billing_company', true);
                        $user_email = get_user_meta($userid, 'billing_email', true);

                        $total_shipping = $valOrder['total_shipping'];
                        $shipping_method = $valOrder['shipping_method'];
                        $discount_amt = $valOrder['total_discount'];
                        $discount_amount = ($discount_amt) ? $discount_amt : 0;
                        $order_status = $valOrder['status'];
                        $enable_incl_tax = get_option('woocommerce_prices_include_tax');

                        // Check if order has user id.
                        if (!empty($userid)) {
                            // Quick check to see if contact still exists in Zoho
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
                                } else {
                                    $contactClassHandle = new ContactClass();
                                    $contactClassHandle->ContactUpdateFunction($userid);
                                }
                            }
                            // Syncing customer to Zoho
                            if (!$zi_customer_id) {

                                // First check based on customer email address
                                $zoho_inventory_oid = get_option('zoho_inventory_oid');
                                $zoho_inventory_url = get_option('zoho_inventory_url');
                                // fwrite($fd,PHP_EOL.'$zi_customer_id : '.$user_email);
                                $url = $zoho_inventory_url . 'api/v1/contacts?organization_id=' . $zoho_inventory_oid . '&email=' . $user_email;

                                $executeCurlCallHandle = new ExecutecallClass();
                                $json = $executeCurlCallHandle->ExecuteCurlCallGet($url);

                                $code = $json->code;
                                $message = $json->message;
                                if ($code == 0 || $code == '0') {
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
                                                    $contactClassHandle->Create_contact_person($userid);
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
                                echo $message;
                                // return;
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
                        } else {
                            $order->add_order_note('Zoho Sync: Guest Orders are not supported');
                            $order->save();
                            return; // END of User ID Check - we don't support guest orders
                        }

                        if ($order_status != 'failed') {
                            $order_items = $order->get_items('coupon');
                            $discount_type = '';
                            foreach ($order->get_coupon_codes() as $coupon_code) {
                                // Get the WC_Coupon object
                                $coupon = new WC_Coupon($coupon_code);
                                $discount_type = $coupon->get_discount_type(); // Get coupon discount type
                            }

                            $index = 0;
                            foreach ($valOrder['suborder'] as $key => $val) {

                                $proid = $val['product_id'];
                                $proidv = $val['variation_id'];
                                // $post_order_id = $val['post_order_id'];
                                $is_variable_item = false;
                                if ($proidv > 0) {
                                    $proid = $proidv;
                                    $item_id = get_post_meta($proid, 'zi_item_id', true);
                                    $is_variable_item = true;
                                } else {
                                    $is_variable_item = false;
                                    $item_id = get_post_meta($proid, 'zi_item_id', true);
                                }

                                $product_desc = $val['product_desc'];
                                // $subtotal = $val['subtotal'];
                                $item_price = $val['item_price'];
                                // $item_total = $val['total'];
                                $qty = ($val['quantity']) ? $val['quantity'] : 1;
                                // adding warehouse_id in line items array
                                $warehouse_id = get_option('zoho_warehouse_id');
                                if ($warehouse_id > 0) {
                                    $warehouse_id = '"warehouse_id": "' . $warehouse_id . '"';
                                } else {
                                    $warehouse_id = '';
                                }
                                // Coupons used in the order
                                $discount_per_item = '';
                                // fwrite($fd,PHP_EOL.'Discount Items : '.print_r($order_items,true));
                                if (!empty($order_items) && 'percent' === $discount_type) {
                                    // fwrite($fd,PHP_EOL.'Inside discount calculation');
                                    global $wpdb;
                                    $discount_per_item = 0;
                                    $table = $wpdb->prefix . 'wc_order_product_lookup';
                                    // fwrite($fd,PHP_EOL.'$tb : '.$tb.' | $order_id : '.$order_id.'| $proid : '.$proid);
                                    do {
                                        if ($is_variable_item) {
                                            $res_coupon = $wpdb->get_row($wpdb->prepare("select * from " . $table . " where order_id = " . $order_id . " and variation_id = " . $proid), ARRAY_A);
                                        } else {
                                            $res_coupon = $wpdb->get_row($wpdb->prepare("select * from " . $table . " where order_id = " . $order_id . " and product_id = " . $proid), ARRAY_A);
                                        }
                                    } while (empty($res_coupon));
                                    // fwrite($fd,PHP_EOL.'$res_coupon : '.print_r($res_coupon,true));
                                    if ($res_coupon['product_net_revenue'] || $discount_amount) {
                                        // fwrite($fd,PHP_EOL.'net revenue : '.$res_coupon['product_net_revenue']);
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
                                $vat_exempt = $order->get_meta('is_vat_exempt');
                                $zoho_tax_id = '';
                                $taxid = '';
                                $tax_value = $order->get_total_tax();
                                if ($vat_exempt == 'yes' || empty($tax_value)) {
                                    $zoho_tax_id = get_option('zi_vat_exempt', true);
                                    $taxid = '"tax_id": "' . $zoho_tax_id . '",';
                                } else {
                                    foreach ($order->get_items('tax') as $item_key => $item) {
                                        $tax_rate_id = $item->get_rate_id(); // Tax rate ID
                                        $tax_percent = WC_Tax::get_rate_percent($tax_rate_id);
                                        $tax_total   = $item_price1 * ($tax_percent / 100);
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

                                global $wpdb;
                                $table_prefix = $wpdb->prefix;
                                $row_match = $wpdb->get_row("select * from " . $table_prefix . "options where option_name LIKE '%zoho_inventory_tax_rate_%' and option_value LIKE '%##" . $percentage . "%'", ARRAY_A);

                                if ($row_match['option_value']) {

                                    $shipping_tax_total_ex = explode('##', $row_match['option_value']);

                                    $shipping_tax_id = $shipping_tax_total_ex[0];
                                    $shipping_tax_per = end($shipping_tax_total_ex);
                                }
                            }

                            $impot = implode(',', $pdt_items);

                            $zi_customer_id = get_user_meta($userid, 'zi_contact_id', true);
                            //Lets create the body
                            $pdt1 = '"customer_id": "' . $zi_customer_id . '","is_discount_before_tax": "true","discount_type": "item_level","line_items": [' . $impot . '],"price_precision":"2","notes": "' . $notes . '","billing_address_id": "' . $billing_id . '","shipping_address_id": "' . $shipping_id . '","delivery_method": "' . $shipping_method . '"';

                            // if there is shipping tax
                            if (!empty($shipping_tax)) {
                                $pdt1 .= ',"shipping_charge_tax_id": "' . $shipping_tax_id . '"';
                            }

                            // if there are order fees
                            $order_fees = $order->get_fees();
                            // $transaction_fee = $this->get_transaction_order_fees($order_id);
                            if (!empty($order_fees)) {
                                foreach ($order_fees as $order_fee) {
                                    $fee_name = $order_fee->get_name();
                                    $fee_total = $order_fee->get_total();
                                }
                                $pdt1 .= ',"adjustment":' . $fee_total . '';
                                $pdt1 .= ',"adjustment_description":"' . $fee_name . '"';
                            }
                            // } elseif (!empty($transaction_fee)) {
                            //     $pdt1 .= ',"adjustment":"' . -$transaction_fee . '"';
                            //     $pdt1 .= ',"adjustment_description":"Stripe Fee"';
                            // }

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

                            // If auto order number is enabled.
                            $enabled_auto_no = get_option('zoho_enable_auto_no_status');
                            $transaction_id = $order->get_meta('_transaction_id', true);
                            if (empty($transaction_id)) {
                                $transaction_id = $order->get_meta('_order_number', true);
                            }
                            $order_prefix = get_option('order-prefix');
                            $reference_no = '';
                            if (class_exists('WCJ_Order_Numbers') && class_exists('WC_Seq_Order_Number_Pro')) {
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
                            // $enabled_auto_no = get_option( 'zoho_enable_auto_no_status' );
                            $ignore_auto_no = ('true' === $enabled_auto_no) ? 'false' : 'true';

                            // Custom Field mapping with zoho.
                            $getmappedfield = get_option('wootozoho_custom_fields');
                            $customfield = ',"custom_fields":[';
                            if ($getmappedfield && count($getmappedfield) > 0) {
                                foreach ($getmappedfield as $key => $value) {
                                    $metavalue = $order->get_meta($key, true);
                                    $customfield .= '{"customfield_id": "' . $value . '","value":"' . $metavalue . '"}';

                                    if (count($getmappedfield) - 1 > 0) {
                                        $customfield .= ',';
                                    }
                                }
                            }
                            $pdt1 .= $customfield . ']';

                            $zoho_order_id = $order->get_meta('zi_salesorder_id', true);
                            if (!empty($zoho_order_id)) {
                                return;
                            }
                            // fwrite($fd,PHP_EOL.'Request Body : '.print_r($jsonData,true));
                            $zoho_inventory_oid = get_option('zoho_inventory_oid');
                            $zoho_inventory_url = get_option('zoho_inventory_url');
                            $enabled_auto_no = get_option('zoho_enable_auto_no_status');
                            $ignore_auto_no = ('true' === $enabled_auto_no) ? 'false' : 'true';
                            $data = array(
                                'JSONString' => '{' . $pdt1 . '}',
                                'organization_id' => $zoho_inventory_oid,
                            );
                            $order->update_meta_data('zi_body_request', implode($data));

                            // fwrite($fd,PHP_EOL.'Request Body : '.'{' . print_r($pdt1, true) . '}');
                            $url = $zoho_inventory_url . 'api/v1/salesorders?ignore_auto_number_generation=' . $ignore_auto_no;

                            $executeCurlCallHandle = new ExecutecallClass();
                            $json = $executeCurlCallHandle->ExecuteCurlCallPost($url, $data);

                            $code = $json->code;
                            $notes = 'Zoho Order Sync: ' . $json->message;
                            // fwrite($fd,PHP_EOL.'JSON Respoonse : '.print_r($json,true));
                            if ($code == '0' || $code == 0) {
                                foreach ($json->salesorder as $key => $value) {
                                    if ($key == 'salesorder_id') {
                                        $res['zi_salesorder_id'] = $value;
                                        $order->update_meta_data('zi_salesorder_id', $value);
                                    }
                                    if ($key == 'customer_id') {
                                        $res['zi_customer_id'] = $value;
                                    }
                                }
                                // action hook to be used by custom functions
                                do_action('zi_thankyou_order_synced');
                            } else {
                                // API request not processed properly add notes and return.
                                $order->add_order_note($notes);
                                $order->save();
                                return;
                            }
                            // sales order package code
                            $zoho_package_status = get_option('zoho_package_zoho_sync_status');
                            if ('true' === $zoho_package_status) {
                                $packageCurlCallHandle = new PackageClass();
                                $package_json = $packageCurlCallHandle->PackageCreateFunction($order_id, $json);
                            }

                            $order->add_order_note($notes);
                            $order->save();
                        }
                    }
                }
            }
        } // Zoho order check condition end

        // fclose($fd);
    }

    /**
     * Get Transaction Order Fees
     */
    public function get_transaction_order_fees($order_id)
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
