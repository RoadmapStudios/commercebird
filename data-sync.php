<?php

/**
 * File for ZOHO inventory plugin initialization.
 *
 * @category Zoho_Integration
 * @package  Wooventory
 * @author   Wooventory
 * @license  GNU General Public License v3.0
 * @link     https://wooventory.com
 */

// require RMS_DIR_PATH . 'background-process.php';

/**
 * Zoho Api Error Email
 *
 * @param [string] $subject - Email subject.
 * @param [string] $message - Message.
 * @return void
 */
function error_log_api_email($subject, $message)
{
    // $domain = get_site_url();

    $to = get_bloginfo('admin_email');

    $headers = 'From: ' . $to . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

    $messages = '<html><body>';
    $messages .= '<p>' . $message . '</p>';
    $messages .= '<p>' . $to . '</p>';
    $messages .= '</body></html>';

    wp_mail($to, $subject, $messages, $headers);
    exit();
}

/**
 * Background sync all customers to Zoho
 */
function zoho_all_contacts_api_func()
{
    $args = array(
        'role__in' => array('customer', 'subscriber'),
        'number' => -1,
    );

    $user_query = new WP_User_Query($args);
    $users = $user_query->get_results();

    // $bg_process = new WP_Zoho_Background_Process('contact');
    foreach ($users as $user) {
        // $bg_process->push_to_queue($user);
    }
    // $bg_process->save()->dispatch();
    $response = array();
    $response['message'] = 'success';
    $response['code'] = 200;
    echo json_encode($response, true);
    exit();
}

/**
 * Function to be called at variable item sync from zoho to woo ajax call.
 */

add_action('wp_ajax_zoho_ajax_call_variable_item_from_zoho', 'zoho_ajax_call_variable_item_from_zoho');
add_action('wp_ajax_nopriv_zoho_ajax_call_variable_item_from_zoho', 'zoho_ajax_call_variable_item_from_zoho');
function zoho_ajax_call_variable_item_from_zoho()
{
    // Clear Orphan data.
    $zi_common_class = new ZI_CommonClass();
    $zi_common_class->clear_orphan_data();

    // get category to filter by category
    $opt_category = get_option('zoho_item_category');

    if ($opt_category) {
        $opt_category = unserialize($opt_category);
    } else {
        $opt_category = array();
    }

    $item_add_resp = array();
    $loop_completed = false; // Flag to track loop completion

    // Retrieve the last synced category index from the previous run
    $last_synced_category_index = get_option('last_synced_category_index_groupitems', 0);

    // Slice the category array to start from the last synced category index
    $opt_category = array_slice($opt_category, $last_synced_category_index);

    if (!empty($opt_category)) {
        foreach ($opt_category as $category_index => $category_id) {
            $data_arr = (object) array();
            // get last backed up page number for particular category Id.
            // And start syncing from the last synced page.
            // If no page number available, it will start from zero.
            $last_synced_page = get_option('group_item_sync_page_cat_id_' . $category_id);
            $data_arr->page = $last_synced_page;
            $data_arr->category = $category_id;
            $existing_schedule = as_has_scheduled_action('import_group_items_cron', array($data_arr));

            // Check if the scheduled action exists
            if (!$existing_schedule) {
                // Schedule the cron job
                $response = as_schedule_single_action(time(), 'import_group_items_cron', array($data_arr));
                if ($response !== null) {
                    // $item_add_resp = array_merge($item_add_resp, $response);
                }
            }

            // Update the last synced category index in the options
            update_option('last_synced_category_index_groupitems', $last_synced_category_index + $category_index + 1);
        }
        // Set the loop completion flag to true
        $loop_completed = true;
    }

    // Check if all categories have been imported or passed to the loop
    $total_categories = count($opt_category);
    $processed_categories = $last_synced_category_index + $category_index + 1;

    if ($processed_categories >= $total_categories) {
        // Reset the last synced category index
        update_option('last_synced_category_index_groupitems', 0);
        $loop_completed = true;
    }

    wp_send_json_success();
    // Send log message to admin only if the loop completed
    if ($loop_completed) {
        // send_log_message_to_admin($item_add_resp, 'Log for manual sync of variable item', 'Variable item sync from zoho');
    }

    // Send the final response and terminate the AJAX call
    wp_die();
}


/**
 * Function to be called at simple item sync from zoho to woo ajax call.
 */

add_action('wp_ajax_zoho_ajax_call_item_from_zoho', 'zoho_ajax_call_item_from_zoho_func');
add_action('wp_ajax_nopriv_zoho_ajax_call_item_from_zoho', 'zoho_ajax_call_item_from_zoho_func');
function zoho_ajax_call_item_from_zoho_func()
{
    // $fd = fopen(__DIR__ . '/simple_items_from_zoho.txt', 'a+');

    // get category to filter by category
    $opt_category = get_option('zoho_item_category');

    if ($opt_category) {
        $opt_category = unserialize($opt_category);
    } else {
        $opt_category = array();
    }

    // fwrite($fd, PHP_EOL . 'opt_category: '. print_r($opt_category, true));
    $item_add_resp = array();
    $loop_completed = false; // Flag to track loop completion

    // Retrieve the last synced category index from the previous run
    $last_synced_category_index = get_option('last_synced_category_index', 0);

    // Slice the category array to start from the last synced category index
    $opt_category = array_slice($opt_category, $last_synced_category_index);

    if (!empty($opt_category)) {
        foreach ($opt_category as $category_index => $category_id) {
            $data_arr = (object) array();

            // get last backed up page number for particular category Id.
            // And start syncing from last synced page.
            // If no page number available, it will start from zero.
            $last_synced_page = get_option('simple_item_sync_page_cat_id_' . $category_id);

            $data_arr->page = $last_synced_page;
            $data_arr->category = $category_id;
            $existing_schedule = as_has_scheduled_action('import_simple_items_cron', array($data_arr));

            // Check if the scheduled action exists
            if (!$existing_schedule) {
                // Schedule the cron job
                $response = as_schedule_single_action(time(), 'import_simple_items_cron', array($data_arr));
                if ($response !== null) {
                    // $item_add_resp = array_merge($item_add_resp, $response);
                }
            }

            // Update the last synced category index in the options
            update_option('last_synced_category_index', $last_synced_category_index + $category_index + 1);
        }
        // Set the loop completion flag to true
        $loop_completed = true;
    }

    // Check if all categories have been imported or passed to the loop
    $total_categories = count($opt_category);
    $processed_categories = $last_synced_category_index + $category_index + 1;

    if ($processed_categories >= $total_categories) {
        // Reset the last synced category index
        update_option('last_synced_category_index', 0);
        $loop_completed = true;
    }

    // Send log message to admin only if the loop completed
    if ($loop_completed) {
        send_log_message_to_admin($item_add_resp, 'Log for manual sync of simple item', 'Simple item sync from zoho');
    }

    // Terminate the AJAX call
    wp_send_json_success();
    wp_die();
}

/**
 * Ajax Function sync items from WooCommerce to Zoho in Background
 */
add_action('wp_ajax_zoho_ajax_call_item', 'zoho_ajax_call_item');
add_action('wp_ajax_nopriv_zoho_ajax_call_item', 'zoho_ajax_call_item');
function zoho_ajax_call_item()
{
    // $fd = fopen(__DIR__ . '/zoho_ajax_call_item.txt', 'a+');

    global $wpdb;

    $query = $wpdb->prepare(
        "SELECT p.ID
        FROM {$wpdb->prefix}posts AS p
        LEFT JOIN {$wpdb->prefix}postmeta AS pm ON p.ID = pm.post_id AND pm.meta_key = 'zi_item_id'
        WHERE p.post_type = 'product'
        AND p.post_status = 'publish'
        AND pm.meta_id IS NULL"
    );

    $post_ids = $wpdb->get_col($query);

    // Create an array to hold the product IDs
    $product_ids = array();

    // fwrite($fd, PHP_EOL . '------------- $post_ids : ' . print_r($post_ids, true));
    // adding all items in the queue
    foreach ($post_ids as $post_id) {
        // Add post ID to the array
        $product_ids[] = $post_id;

        // Check if the array contains 10 product IDs
        if (count($product_ids) === 10) {
            // Pass the array of product IDs to the scheduler function
            as_schedule_single_action(time(), 'sync_zi_product_cron', array($product_ids));

            // Clear the array for the next batch of product IDs
            $product_ids = array();
        }
    }

    // Check if there are any remaining product IDs in the array
    if (count($product_ids) > 0) {
        // Pass the remaining product IDs to the scheduler function
        as_schedule_single_action(time(), 'sync_zi_product_cron', array($product_ids));
    }

    // fclose($fd);
    // Send Success Response and Terminate AJAX Call
    wp_send_json_success();
    wp_die();
}

/**
 * Function to be called at contact sync ajax call
 */
// TODO: refactor to use WC Action Scheduler
add_action('wp_ajax_zoho_ajax_call_contact', 'zoho_ajax_call_contact');
add_action('wp_ajax_nopriv_zoho_ajax_call_contact', 'zoho_ajax_call_contact');
function zoho_ajax_call_contact()
{
    // Function calling for contact sync.
    zoho_all_contacts_api_func();
    wp_die();
}

/* Importing Zoho contact function */
add_action('wp_ajax_import_zoho_contacts', 'zoho_contacts_import');
add_action('wp_ajax_nopriv_import_zoho_contacts', 'zoho_contacts_import');
function zoho_contacts_import($page = '')
{
    if (empty($page)) {
        $page = 1;
    }

    /* Get Zoho contacts using new contact function */
    $contact_class = new ContactClass();
    $res = $contact_class->Get_Zoho_Contacts($page);

    if ($res->message == 'success') {
        //if success and have contacts
        if (!empty($res->contacts)) {
            foreach ($res->contacts as $contacts) {
                $contact_type = trim($contacts->contact_type);
                $zohocontact_id = $contacts->contact_id;
                $phone = $contacts->phone;
                $company_name = $contacts->company_name;
                $first_name = $contacts->first_name;
                $last_name = $contacts->last_name;
                $email = trim($contacts->email);
                //check if user type is customer
                if (!empty($email) && !empty($first_name) && !empty($last_name)) {
                    if ($contact_type == 'customer' && !email_exists($email)) {
                        /* Create Wp User */
                        $userData = array(
                            "user_login" => $first_name . '.' . $last_name,
                            "display_name" => $first_name . ' ' . $last_name,
                            "user_email" => $email,
                            "first_name" => $first_name,
                            "last_name" => $last_name,
                            "role" => "customer",
                        );
                        $user_id = wp_insert_user($userData);
                        if (!is_wp_error($user_id)) {
                            update_user_meta($user_id, 'zi_contact_id', $zohocontact_id);
                            update_user_meta($user_id, 'billing_company', $company_name);
                            update_user_meta($user_id, 'billing_phone', $phone);
                        } else {
                            echo $user_id->get_error_message();
                        }
                    } elseif (email_exists($email)) {
                        /* Update Wp User if already exist */
                        $user_data = get_user_by('email', $email);
                        $user_id = $user_data->ID;
                        $is_err = wp_update_user(array(
                            'ID' => $user_id,
                            "display_name" => $first_name . ' ' . $last_name,
                            'user_email' => $email,
                            'first_name' => $first_name,
                            'last_name' => $last_name,
                        ));
                        if (!is_wp_error($is_err)) {
                            update_user_meta($user_id, 'zi_contact_id', $zohocontact_id);
                            update_user_meta($user_id, 'billing_company', $company_name);
                            update_user_meta($user_id, 'billing_phone', $phone);
                        } else {
                            echo $is_err->get_error_message();
                        }
                    }
                }
            }
        }
        foreach ($res->page_context as $key => $has_more) {
            if ($key === 'has_more_page') {
                if ($has_more) {
                    $page++;
                    zoho_contacts_import($page);
                }
            }
        }
    } else {
        return false;
    }
}

/**
 * Sync composite item from zoho to woocommerce.
 *
 * @return void
 */
add_action('wp_ajax_zoho_ajax_call_composite_item_from_zoho', 'zi_sync_composite_item_from_zoho');
add_action('wp_ajax_nopriv_zoho_ajax_call_composite_item_from_zoho', 'zi_sync_composite_item_from_zoho');
function zi_sync_composite_item_from_zoho()
{

    $opt_category = get_option('zoho_item_category');
    if ($opt_category) {
        $opt_category = unserialize($opt_category);
    } else {
        $opt_category = array();
    }

    $item_add_resp = array();
    foreach ($opt_category as $category_id) {
        $productClass = new ImportProductClass();
        $response = $productClass->recursively_sync_composite_item_from_zoho(1, $category_id, 'sync');
        $item_add_resp = array_merge($item_add_resp, $response);
    }
    echo json_encode($item_add_resp);
    send_log_message_to_admin($item_add_resp, 'Log Message for manual sync', 'Composite item sync from zoho');
    wp_die();
}

/**
 * Send log message to admin
 *
 * @return void
 */
function send_log_message_to_admin($sync_logs, $subject, $message)
{
    $table_root = "<h3>$message</h3>";
    $table_root .= '<table><thead><tr><th>Action</th><th> Log message</th></tr></thead><tbody>';

    foreach ($sync_logs as $logs) {
        $table_root .= "<tr><td>$logs->resp_id</td><td>$logs->message</td></tr>";
    }
    $table_root .= '</tbody></table>';
    error_log_api_email($subject, $table_root);
}

// Ajax api call hook to sync composite products from WooCommerce to Zoho.
add_action('wp_ajax_zoho_ajax_call_composite_item', 'zi_sync_composite_item_to_zoho');
add_action('wp_ajax_nopriv_zoho_ajax_call_composite_item', 'zi_sync_composite_item_to_zoho');
function zi_sync_composite_item_to_zoho()
{

    $irgs = array(
        'post_type' => array('product'),
        'posts_per_page' => '-1',
        'post_status' => 'publish',
        'tax_query' => array(
            array(
                'taxonomy' => 'product_type',
                'field' => 'slug',
                'terms' => 'bundle',
            ),
        ),
    );

    $my_query = new WP_Query($irgs);
    $bundled_product = $my_query->posts;
    if (count($bundled_product) > 0) {
        // $composite_sync = new WP_Zoho_Background_Process('bundle_item');
        foreach ($bundled_product as $prod) {
            $bundle_childs = WC_PB_DB::query_bundled_items(
                array(
                    'return' => 'id=>product_id', // 'objects'
                    'bundle_id' => array($prod->ID),
                )
            );
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
                // $composite_sync->push_to_queue($prod);
            }
        }
        // $composite_sync->save()->dispatch();
    } else {
        // No composite item is available to sync.
        // Send mail may be.
    }

    $response = array();
    $response['message'] = 'Sync Started';
    $response['code'] = 200;
    echo json_encode($response, true);

    exit();
}
// Check if all child item synced or not.

/**
 * Create response object based on data.
 *
 * @param mixed  $index_col - Index value error message.
 * @param string $message - Response message.
 * @return object
 */
function zi_response_message($index_col, $message, $woo_id = '')
{
    return (object) array(
        'resp_id' => $index_col,
        'message' => $message,
        'woo_prod_id' => $woo_id,
    );
}

/**
 * Sync zoho sub category to woocommerce.
 *
 * @return void
 */

add_action('wp_ajax_zoho_ajax_call_subcategory', 'ajax_subcategory_sync_call');
function ajax_subcategory_sync_call()
{
    $response = array(); // Response array.
    $zoho_subcategories = get_zoho_item_categories();
    // Import category from zoho to woocommerce.
    array_push($response, zi_response_message('-', '-', '--- Importing Sub Category from zoho ---'));
    $zoho_subcategories = $zoho_subcategories['categories'];
    //echo '<pre>'; print_r($zoho_categories);
    foreach ($zoho_subcategories as $subcategory) {
        if ($subcategory['parent_category_id'] > 0) {
            if ($subcategory['category_id'] != '-1' && $subcategory['category_id'] > 0) {
                $term = get_term_by('name', $subcategory['name'], 'product_cat');
                $term_id = $term->term_id;

                if ($subcategory['parent_category_id'] > 0) {

                    $zoho_pid = intval(subcategories_term_id($subcategory['parent_category_id']));
                }

                if (empty($term_id) && $zoho_pid > 0) {
                    $child_term = wp_insert_term(
                        $subcategory['name'],
                        'product_cat',
                        array(
                            'parent' => $zoho_pid,
                        )
                    );
                    // Check if there is error in creating child category add message.
                    if (is_wp_error($child_term)) {
                        array_push($response, zi_response_message($subcategory['category_id'], $child_term->get_error_message(), '-'));
                    } else {
                        $term_id = $child_term['term_id'];
                    }
                }

                if ($term_id && $zoho_pid > 0) {
                    // Update zoho sub category id for term(sub category) of woocommerce.
                    update_option('zoho_id_for_term_id_' . $term_id, $subcategory['category_id']);
                }
                array_push($response, zi_response_message($subcategory['category_id'], $subcategory['name'], $term_id));
            }
        }
    }
    // Closing of import of category from woo to zoho .

    $categories_terms = get_terms('product_cat', array('parent' => 0, 'hide_empty' => false));
    $log_head = '---Exporting Sub Category to zoho---';
    array_push($response, zi_response_message('-', '-', $log_head));
    $c = 0;
    if ($categories_terms && count($categories_terms) > 0) {

        foreach ($categories_terms as $parent_term) {

            $subcategories_terms = get_terms('product_cat', array('parent' => $parent_term->term_id, 'hide_empty' => false));

            if ($subcategories_terms && count($subcategories_terms) > 0) {

                foreach ($subcategories_terms as $term) {

                    $zoho_cat_id = get_option('zoho_id_for_term_id_' . $term->term_id);
                    if (empty($zoho_cat_id)) {

                        $zoho_cat_id = get_option('zoho_id_for_term_id_' . $parent_term->term_id);
                        $pid = $zoho_cat_id;

                        $addresponse = create_woo_cat_to_zoho($term->name, $term->term_id, $pid);
                        array_push($response, $addresponse);
                    } else {
                        array_push($response, zi_response_message($zoho_cat_id, 'Sub Category name : "' . $term->name . '" already synced with zoho', $term->term_id));
                    }
                    $c++;
                }
            }
        }
    }

    if ($c == 0) {
        array_push($response, zi_response_message('-', 'Sub Categories not available to export', '-'));
    }
    echo json_encode($response);
    exit();
}

/**
 * Cron job to update category per day.
 */
if (!wp_next_scheduled('zoho_sync_category_cron')) {
    wp_schedule_event(time(), '1day', 'zoho_sync_category_cron');
}
add_action('zoho_sync_category_cron', 'ajax_category_sync_call');
/**
 * Sync zoho category to woocommerce.
 *
 * @return void
 */

add_action('wp_ajax_zoho_ajax_call_category', 'ajax_category_sync_call');
function ajax_category_sync_call()
{
    $response = array(); // Response array.
    $zoho_categories = get_zoho_item_categories();
    // Import category from zoho to woocommerce.
    array_push($response, zi_response_message('-', '-', '--- Importing Category from zoho ---'));

    $zoho_categories = $zoho_categories['categories'];

    foreach ($zoho_categories as $category) {

        if ($category['parent_category_id'] == '-1') {

            if ($category['category_id'] != '-1' && $category['category_id'] > 0) {
                $term = get_term_by('name', $category['name'], 'product_cat');
                $term_id = $term->term_id;
                if (empty($term_id)) {
                    $term = wp_insert_term(
                        $category['name'],
                        'product_cat',
                        array(
                            'parent' => 0,
                        )
                    );
                    if (is_wp_error($term)) {
                        array_push($response, zi_response_message($category['category_id'], $term->get_error_message(), '-'));
                    } else {
                        $term_id = $term['term_id'];
                    }
                }
                if ($term_id) {
                    // Update zoho category id for term(category) of woocommerce.
                    update_option('zoho_id_for_term_id_' . $term_id, $category['category_id']);
                }
                array_push($response, zi_response_message($category['category_id'], $category['name'], $term_id));
            }
        }
    }
    // Closing of import of category from woo to zoho .
    $categories_terms = get_terms('product_cat', array('parent' => 0, 'hide_empty' => false));
    $log_head = '---Exporting Category to zoho---';
    array_push($response, zi_response_message('-', '-', $log_head));
    if ($categories_terms && count($categories_terms) > 0) {

        foreach ($categories_terms as $term) {

            $zoho_cat_id = get_option('zoho_id_for_term_id_' . $term->term_id);
            if (empty($zoho_cat_id)) {
                $addresponse = create_woo_cat_to_zoho($term->name, $term->term_id);
                array_push($response, $addresponse);
            } else {
                array_push($response, zi_response_message($zoho_cat_id, 'Category name : "' . $term->name . '" already synced with zoho', $term->term_id));
            }
        }
    } else {
        array_push($response, zi_response_message('-', 'Categories not available to export', '-'));
    }
    echo json_encode($response);
    exit();
}

/**
 * Create woocommerce category in zoho store.
 *
 * @return void
 */
function create_woo_cat_to_zoho($cat_name, $term_id = '0', $pid = '')
{

    $zoho_inventory_oid = get_option('zoho_inventory_oid');
    $zoho_inventory_url = get_option('zoho_inventory_url');

    if (!empty($pid) || $pid > 0) {
        $zidata = '"name" : "' . $cat_name . '","parent_category_id" : "' . $pid . '",';
    } else {
        $zidata = '"name" : "' . $cat_name . '",';
    }

    $data = array(
        'JSONString' => '{' . $zidata . '}',
    );

    $url = $zoho_inventory_url . 'api/v1/categories/?organization_id=' . $zoho_inventory_oid;

    $executeCurlCallHandle = new ExecutecallClass();
    $json = $executeCurlCallHandle->ExecuteCurlCallPost($url, $data);

    $code = $json->code;

    if ('0' == $code || 0 == $code) {
        foreach ($json->category as $key => $value) {
            if ($key == 'category_id') {

                update_option('zoho_id_for_term_id_' . $term_id, $value);
            }
        }
    }
    $response_msg = $json->message;

    //echo '<pre>'; print_r($json);

    return (object) array(
        'resp_id' => $code,
        'message' => $response_msg,
    );
}

/**
 * Function for getting zoho categories.
 */
function get_zoho_item_categories()
{

    $zoho_inventory_oid = get_option('zoho_inventory_oid');
    $zoho_inventory_url = get_option('zoho_inventory_url');

    $url = $zoho_inventory_url . 'api/v1/categories/?organization_id=' . $zoho_inventory_oid;

    $executeCurlCallHandle = new ExecutecallClass();
    $json = $executeCurlCallHandle->ExecuteCurlCallGet($url);

    $response = json_encode($json, true);
    return json_decode($response, true);
}

/**
 * Function to save custom field.
 *
 * @return void
 */
add_action('wp_ajax_map_custom_field', 'savecustomfields');
function savecustomfields()
{
    $response = (object) array();
    $response->message = 'Error';
    $response->code = 200;
    $field = $_POST['data'];
    update_option('wootozoho_custom_fields', $field);
    $response->message = 'Success';
    $response->data = $field;
    echo json_encode($response);
    exit;
}

/**
 * Get Term ID from Zoho category ID
 *
 * @return boolean
 */

function subcategories_term_id($option_value)
{

    global $wpdb;

    $table_prefix = $wpdb->prefix;
    $row = $wpdb->get_row("select * from " . $table_prefix . "options where option_value = '" . $option_value . "'");

    if (!empty($row->option_name)) {

        $ex = explode('zoho_id_for_term_id_', $row->option_name);
        $cat_id = $ex[1];
    } else {
        $cat_id = 0;
    }

    return $cat_id;
}

/**
 * Function for ajax call to enable or disable synch with zoho.
 *
 * @return void
 */
function disable_zoho_sync()
{
    $sync_status = $_POST['sync_status'];
    update_option('zoho_sync_status', $sync_status);
}

add_action('wp_ajax_disable_zoho_sync', 'disable_zoho_sync');

/**
 * Function to handle ajax call to enable or disable sync call.
 *
 * @return void
 */
function zi_sync_option_handler()
{
    // sync_status : true/false - To set option value.
    $sync_status = $_POST['sync_status'];
    // sync_option_key: This is key generated by sync option selected by admin at data sync page.
    // For example : 'zoho_stock_sync_status' is option key for Disable Stock Sync checkbox.
    $sync_option_key = $_POST['sync_option_key'];
    update_option($sync_option_key, $sync_status);
}

// Disable stock sync
add_action('wp_ajax_stock_sync', 'zi_sync_option_handler');

// Disable product sync
add_action('wp_ajax_product_sync', 'zi_sync_option_handler');

// Enable auto-generated zoho order id
add_action('wp_ajax_enable_auto_no', 'zi_sync_option_handler');

// Option to update order status while we create sales order to zoho.
add_action('wp_ajax_enable_order', 'zi_sync_option_handler');

// Ajax hook to enable decimal tax for order sync.
add_action('wp_ajax_enable_decimal_tax', 'zi_sync_option_handler');

// Enable package details sync.
add_action('wp_ajax_package_zoho_sync', 'zi_sync_option_handler');

// Enable attributes sync.
add_action('wp_ajax_enable_attributes_sync', 'zi_sync_option_handler');

// Disable Product Options Sync From Cron Configuration tab.
add_action('wp_ajax_disable_itemname_sync', 'zi_sync_option_handler');
add_action('wp_ajax_disable_itemprice_sync', 'zi_sync_option_handler');
add_action('wp_ajax_disable_itemimage_sync', 'zi_sync_option_handler');
add_action('wp_ajax_disable_itemdescription_sync', 'zi_sync_option_handler');
add_action('wp_ajax_disable_groupitem_sync', 'zi_sync_option_handler');

// Enable Multi-currency support
add_action('wp_ajax_enable_multicurrency', 'zi_sync_option_handler');

// Enable Accounting Stock Mode
add_action('wp_ajax_enable_accounting_stock', 'zi_sync_option_handler');

// Enable Warehouse Stock Sync
add_action('wp_ajax_enable_warehousestock', 'zi_sync_option_handler');