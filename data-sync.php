<?php

/**
 * File for ZOHO inventory plugin initialization.
 *
 * @category Zoho_Integration
 * @package  commercebird
 * @author   commercebird
 * @license  GNU General Public License v3.0
 * @link     https://commercebird.com
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Zoho Api Error Email
 *
 * @param [string] $subject - Email subject.
 * @param [string] $message - Message.
 *
 * @return void
 */
function cmbird_error_log_api_email( $subject, $message ) {
	// $domain = get_site_url();

	$to = get_bloginfo( 'admin_email' );

	$headers = 'From: ' . $to . "\r\n";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

	$messages = '<html><body>';
	$messages .= '<p>' . $message . '</p>';
	$messages .= '<p>' . $to . '</p>';
	$messages .= '</body></html>';

	wp_mail( $to, $subject, $messages, $headers );
}

/**
 * Function to be called at variable item sync from zoho to woo ajax call.
 */

add_action( 'wp_ajax_zoho_ajax_call_variable_item_from_zoho', 'cmbird_ajax_call_variable_item_from_zoho' );
function cmbird_ajax_call_variable_item_from_zoho() {
	// Clear Orphan data.
	$zi_common_class = new CMBIRD_Common_Functions();
	$zi_common_class->clear_orphan_data();

	// check if a category is selected
	$selected_category = isset( $_GET['category'] ) ? sanitize_text_field( $_GET['category'] ) : null;

	if ( $selected_category ) {
		$categories = [ $selected_category ]; // Only sync the selected category
	} else {
		// get category to filter by category
		$opt_category = get_option( 'cmbird_zoho_item_category' );
		if ( $opt_category ) {
			// convert serialized string to array
			$categories = maybe_unserialize( $opt_category );
			if ( ! is_array( $categories ) ) {
				$categories = array();
			}
		} else {
			$categories = array();
		}
	}

	// Slice the category array to start from the last synced category index
	if ( ! empty( $categories ) ) {
		foreach ( $categories as $category_index => $category_id ) {
			$data = array(
				'page' => 1,
				'category' => $category_id,
			);
			$existing_schedule = as_has_scheduled_action( 'import_group_items_cron', $data );
			// Schedule the action if it doesn't exist.
			if ( ! $existing_schedule ) {
				as_schedule_single_action( time(), 'import_group_items_cron', $data );
			}

		}
	}

	wp_send_json_success( array( 'message' => 'Items are being imported in background. You can visit other tabs :).' ) );
	wp_die();
}


// Attach the function to the cron event
add_action( 'zi_execute_import_sync', 'cmbird_ajax_call_item_from_zoho_func' );

/**
 * Function to be called at simple item sync from zoho to woo ajax call.
 */

add_action( 'wp_ajax_zoho_ajax_call_item_from_zoho', 'cmbird_ajax_call_item_from_zoho_func' );
function cmbird_ajax_call_item_from_zoho_func() {
	// Clear Orphan data.
	$zi_common_class = new CMBIRD_Common_Functions();
	$zi_common_class->clear_orphan_data();

	// Check if a category is selected
	$selected_category = isset( $_GET['category'] ) ? sanitize_text_field( $_GET['category'] ) : null;

	if ( $selected_category ) {
		$categories = [ $selected_category ]; // Only sync the selected category
	} else {
		$zoho_item_category = get_option( 'cmbird_zoho_item_category' );
		if ( $zoho_item_category ) {
			// convert serialized string to array
			$categories = maybe_unserialize( $zoho_item_category );
			if ( ! is_array( $categories ) ) {
				$categories = array();
			}
		} else {
			$categories = array();
		}
	}

	if ( empty( $categories ) ) {
		wp_send_json_error( array( 'message' => __( 'Please select at least one category from cron tab', 'commercebird' ) ) );
	} else {
		foreach ( $categories as $index => $category_id ) {
			$data = array(
				'page' => 1,
				'category' => $category_id,
			);
			$existing_schedule = as_has_scheduled_action( 'import_simple_items_cron', $data );
			if ( ! $existing_schedule ) {
				as_schedule_single_action( time(), 'import_simple_items_cron', $data );
			}
		}
	}
	wp_send_json_success( array( 'message' => __( 'Items are being imported in background. You can visit other tabs :).', 'commercebird' ) ) );
	wp_die();
}

/**
 * Zoho Inventory Function sync items from WooCommerce to Zoho in Background
 */
add_action( 'wp_ajax_zoho_ajax_call_item', 'cmbird_ajax_call_item' );
function cmbird_ajax_call_item() {
	// $fd = fopen(__DIR__ . '/cmbird_ajax_call_item.txt', 'a+');

	global $wpdb;

	$meta_key = 'zi_item_id';
	$post_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT p.ID
        FROM {$wpdb->prefix}posts AS p
        WHERE p.post_type = 'product'
        AND p.post_status = 'publish'
        AND NOT EXISTS (
            SELECT 1
            FROM {$wpdb->prefix}postmeta AS pm
            WHERE p.ID = pm.post_id
            AND pm.meta_key = %s
        )",
			$meta_key
		)
	);
	// Create an array to hold the product IDs
	$product_ids = array();
	// fwrite($fd, PHP_EOL . '------------- $post_ids : ' . print_r($post_ids, true));
	// Adding all items in the queue
	foreach ( $post_ids as $post_id ) {
		// Add post ID to the array
		$product_ids[] = $post_id;
		// Check if the array contains 10 product IDs (batch size)
		if ( count( $product_ids ) === 10 ) {
			// Schedule the action with a delay increasing exponentially for each batch
			as_schedule_single_action( time(), 'sync_zi_product_cron', array( $product_ids ) );
			// Clear the array for the next batch of product IDs
			$product_ids = array();
		}
	}

	// Handle any remaining product IDs if the count is less than 10 in the last batch
	if ( count( $product_ids ) > 0 ) {
		// Pass the remaining product IDs to the scheduler function
		as_schedule_single_action( time(), 'sync_zi_product_cron', array( $product_ids ) );
	}

	// fclose($fd);
	// Send Success Response and Terminate AJAX Call
	wp_send_json_success();
	wp_die();
}

/**
 * Sync contacts from zoho to woocommerce.
 * @return void
 */
add_action( 'zoho_contact_sync', 'cmbird_zoho_contacts_import' );
add_action( 'wp_ajax_import_zoho_contacts', 'cmbird_zoho_contacts_import' );
function cmbird_zoho_contacts_import( $page = '' ) {
	if ( empty( $page ) ) {
		$page = 1;
	}
	$data_arr = (object) array();
	$data_arr->page = $page;
	$existing_schedule = as_has_scheduled_action( 'sync_zi_import_contacts', array( $data_arr ) );

	// Wrap this via Action Scheduler per page
	if ( ! $existing_schedule ) {
		// Schedule the cron job
		as_schedule_single_action( time(), 'sync_zi_import_contacts', array( $data_arr ) );
	}

	// send success response to admin and terminate AJAX call.
	wp_send_json(
		array(
			'success' => true,
			'message' => 'Syncing in background. You can visit other tabs :).',
		)
	);
	wp_die();
}

/**
 * Sync composite item from zoho to woocommerce.
 *
 * @return void
 */
add_action( 'wp_ajax_zoho_ajax_call_composite_item_from_zoho', 'cmbird_sync_composite_item_from_zoho' );
function cmbird_sync_composite_item_from_zoho() {

	// Clear Orphan data.
	$zi_common_class = new CMBIRD_Common_Functions();
	$zi_common_class->clear_orphan_data();

	// check if a category is selected
	$selected_category = isset( $_GET['category'] ) ? sanitize_text_field( $_GET['category'] ) : null;
	if( $selected_category ) {
		$categories = [ $selected_category ]; // Only sync the selected category
	} else {
		// get category to filter by category
		$opt_category = get_option( 'cmbird_zoho_item_category' );
		if ( $opt_category ) {
			// convert serialized string to array
			$categories = maybe_unserialize( $opt_category );
			if ( ! is_array( $categories ) ) {
				$categories = array();
			}
		} else {
			$categories = array();
		}
	}

	$item_add_resp = array();
	foreach ( $categories as $category_id ) {
		$product_class = new CMBIRD_Products_ZI();
		$response = $product_class->recursively_sync_composite_item_from_zoho( 1, $category_id );
		$item_add_resp = array_merge( $item_add_resp, $response );
	}
	cmbird_send_log_message_to_admin( $item_add_resp, 'Log Message for manual sync', 'Composite item sync from zoho' );
	wp_send_json_success( $item_add_resp );
	wp_die();
}

/**
 * Send log message to admin
 *
 * @return void
 */
function cmbird_send_log_message_to_admin( $sync_logs, $subject, $message ) {
	$table_root = "<h3>$message</h3>";
	$table_root .= '<table><thead><tr><th>Action</th><th> Log message</th></tr></thead><tbody>';

	foreach ( $sync_logs as $logs ) {
		$table_root .= "<tr><td>$logs->resp_id</td><td>$logs->message</td></tr>";
	}
	$table_root .= '</tbody></table>';
	cmbird_error_log_api_email( $subject, $table_root );
}

// ZohoInventory api call hook to sync composite products from WooCommerce to Zoho.
add_action( 'wp_ajax_zoho_ajax_call_composite_item', 'cmbird_sync_composite_item_to_zoho' );
function cmbird_sync_composite_item_to_zoho() {

	$irgs = array(
		'post_type' => array( 'product' ),
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

	$my_query = new WP_Query( $irgs );
	$bundled_product = $my_query->posts;
	if ( count( $bundled_product ) > 0 ) {
		foreach ( $bundled_product as $prod ) {
			$bundle_childs = WC_PB_DB::query_bundled_items(
				array(
					'return' => 'id=>product_id', // 'objects'
					'bundle_id' => array( $prod->ID ),
				)
			);
			if ( count( $bundle_childs ) > 0 ) {
				foreach ( $bundle_childs as $child_item_id ) {
					$zoho_item_id = get_post_meta( $child_item_id, 'zi_item_id', true );
					if ( empty( $zoho_item_id ) ) {
						break;
					}
				}
			}
		}
	}

	$response = array();
	$response['message'] = 'Sync Started';
	$response['code'] = 200;
	wp_send_json_success( $response );
	wp_die();
}
