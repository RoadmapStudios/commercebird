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

	// get category to filter by category
	$opt_category = get_option( 'cmbird_zoho_item_category' );

	if ( $opt_category ) {
		$opt_category = maybe_unserialize( $opt_category );
	} else {
		$opt_category = array();
	}

	// Retrieve the last synced category index from the previous run
	$last_synced_category_index = get_option( 'cmbird_last_synced_category_index_groupitems', 0 );

	// Slice the category array to start from the last synced category index
	$opt_category = array_slice( $opt_category, $last_synced_category_index );
	$category_index = 0;
	if ( ! empty( $opt_category ) ) {
		foreach ( $opt_category as $category_index => $category_id ) {
			// get last backed up page number for particular category Id.
			// And start syncing from the last synced page.
			// If no page number available, it will start from zero.
			$last_synced_page = get_option( 'cmbird_group_item_sync_page_cat_id_' . $category_id );
			if ( ! intval( $last_synced_page ) ) {
				$last_synced_page = 1;
			}
			$data = array(
				'page' => $last_synced_page,
				'category' => $category_id,
			);
			$existing_schedule = as_has_scheduled_action( 'import_group_items_cron', $data );
			// Schedule the action if it doesn't exist.
			if ( ! $existing_schedule ) {
				as_schedule_single_action( time(), 'import_group_items_cron', $data );
			}

			// Update the last synced category index in the options
			update_option( 'cmbird_last_synced_category_index_groupitems', $last_synced_category_index + $category_index + 1 );
		}
		// Check if all categories have been imported or passed to the loop
		$total_categories = count( $opt_category );
		$processed_categories = $last_synced_category_index + $category_index + 1;

		if ( $processed_categories >= $total_categories ) {
			// Reset the last synced category index
			update_option( 'cmbird_last_synced_category_index_groupitems', 0 );
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

	$zoho_item_category = get_option( 'cmbird_zoho_item_category' );
	$last_synced_category_index = get_option( 'cmbird_last_synced_category_index', 0 );

	if ( $zoho_item_category ) {
		// convert serialized string to array
		$categories = maybe_unserialize( $zoho_item_category );
		$categories = array_slice( $categories, $last_synced_category_index );
	} else {
		$categories = array();
	}

	if ( empty( $categories ) ) {
		wp_send_json_error( array( 'message' => __( 'Please select at least one category from cron tab', 'commercebird' ) ) );
	} else {
		foreach ( $categories as $index => $category_id ) {
			$last_synced_page = get_option( 'cmbird_simple_item_sync_page_cat_id_' . $category_id );
			if ( ! intval( $last_synced_page ) ) {
				$last_synced_page = 1;
			}
			$data = array(
				'page' => $last_synced_page,
				'category' => $category_id,
			);
			$existing_schedule = as_has_scheduled_action( 'import_simple_items_cron', $data );
			if ( ! $existing_schedule ) {
				as_schedule_single_action( time(), 'import_simple_items_cron', $data );
			}
			update_option( 'cmbird_last_synced_category_index', $last_synced_category_index + $index + 1 );
		}

		$total_categories = count( $categories );
		$processed_categories = $last_synced_category_index + $index + 1;

		if ( $processed_categories >= $total_categories ) {
			update_option( 'cmbird_last_synced_category_index', 0 );
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

	$opt_category = get_option( 'cmbird_zoho_item_category' );
	if ( $opt_category ) {
		$opt_category = maybe_unserialize( $opt_category );
	} else {
		$opt_category = array();
	}

	$item_add_resp = array();
	foreach ( $opt_category as $category_id ) {
		$product_class = new CMBIRD_Products_ZI();
		$response = $product_class->recursively_sync_composite_item_from_zoho( 1, $category_id, 'sync' );
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
	echo wp_json_encode( $response );

	exit();
}

// Check if all child item synced or not.

/**
 * Create response object based on data.
 *
 * @param mixed $index_col - Index value error message.
 * @param string $message - Response message.
 *
 * @return object
 */
function cmbird_zi_response_message( $index_col, $message, $woo_id = '' ) {
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

add_action( 'wp_ajax_zoho_ajax_call_subcategory', 'cmbird_zi_subcategory_sync_call' );
function cmbird_zi_subcategory_sync_call() {
	// $fd = fopen( __DIR__ . '/ajax_subcategory_sync_call.txt', 'a+' );
	$response = array(); // Response array.
	$zoho_subcategories = cmbird_get_zoho_item_categories();
	// Import category from zoho to woocommerce.
	$response[] = cmbird_zi_response_message( '-', '-', '--- Importing Sub Category from zoho ---' );
	//echo '<pre>'; print_r($zoho_categories);
	foreach ( $zoho_subcategories as $subcategory ) {
		if ( $subcategory['parent_category_id'] > 0 ) {
			if ( '-1' !== $subcategory['category_id'] && $subcategory['category_id'] > 0 ) {
				$term = get_term_by( 'name', $subcategory['name'], 'product_cat' );

				if ( $subcategory['parent_category_id'] > 0 ) {
					$zoho_pid = intval( cmbird_subcategories_term_id( $subcategory['parent_category_id'] ) );
				}

				if ( empty( $term ) && $zoho_pid ) {
					$child_term = wp_insert_term(
						$subcategory['name'],
						'product_cat',
						array(
							'parent' => $zoho_pid,
						)
					);
					// Check if there is error in creating child category add message.
					if ( is_wp_error( $child_term ) ) {
						$response[] = cmbird_zi_response_message( $subcategory['category_id'], $child_term->get_error_message(), '-' );
					} else {
						$term_id = $child_term['term_id'];
					}
				} elseif ( $term instanceof WP_Term ) {
					$term_id = $term->term_id;
					// update the term as sub category of parent category.
					wp_update_term(
						$term_id,
						'product_cat',
						array(
							'parent' => $zoho_pid,
						)
					);
				}

				if ( $term_id && $zoho_pid > 0 ) {
					// Update zoho sub category id for term(sub category) of woocommerce.
					update_option( 'cmbird_zoho_id_for_term_id_' . $term_id, $subcategory['category_id'] );

				}
				$response[] = cmbird_zi_response_message( $subcategory['category_id'], $subcategory['name'], $term_id );
			}
		}
	}
	// Closing of import of category from woo to zoho .

	// Get product categories from woocommerce.
	$categories_terms = get_terms(
		array(
			'taxonomy' => 'product_cat',
			'child_of' => false,
		)
	);
	$log_head = '---Exporting Sub Category to zoho---';
	$response[] = cmbird_zi_response_message( '-', '-', $log_head );
	$c = 0;
	if ( $categories_terms && count( $categories_terms ) > 0 ) {

		foreach ( $categories_terms as $parent_term ) {
			$parent_id = $parent_term->term_id;
			$args = array(
				'taxonomy' => 'product_cat',
				'hide_empty' => false,
				'parent' => $parent_id,
			);
			$subcategories_terms = get_terms( $args );
			if ( $subcategories_terms && count( $subcategories_terms ) > 0 ) {
				foreach ( $subcategories_terms as $term ) {
					$zoho_cat_id = get_option( 'cmbird_zoho_id_for_term_id_' . $term->term_id );
					if ( empty( $zoho_cat_id ) ) {
						$zoho_cat_id = get_option( 'cmbird_zoho_id_for_term_id_' . $parent_id );
						$pid = $zoho_cat_id;
						$add_response = cmbird_zi_category_export( $term->name, $term->term_id, $pid );
						$response[] = $add_response;
					} else {
						$response[] = cmbird_zi_response_message( $zoho_cat_id, 'Sub Category name : "' . $term->name . '" already synced with zoho', $term->term_id );
					}
					++$c;
				}
			}
		}
	}
	// fwrite( $fd, PHP_EOL . 'Sub Categories : ' . print_r( $response, true ) );
	// fclose( $fd );

	if ( 0 === $c ) {
		$response[] = cmbird_zi_response_message( '-', 'Sub Categories not available to export', '-' );
	}
	return wp_json_encode( $response );
	// exit();
}

/**
 * Cron job to update category per day.
 */
if ( ! wp_next_scheduled( 'zoho_sync_category_cron' ) ) {
	wp_schedule_event( time(), '1day', 'zoho_sync_category_cron' );
}
add_action( 'zoho_sync_category_cron', 'cmbird_zi_category_sync_call' );
/**
 * Sync zoho category to woocommerce.
 *
 * @return void
 */

add_action( 'wp_ajax_zoho_ajax_call_category', 'cmbird_zi_category_sync_call' );
function cmbird_zi_category_sync_call() {
	// $fd = fopen( __DIR__ . '/cmbird_zi_category_sync_call.txt', 'a+' );

	$response = array(); // Response array.
	$zoho_categories = cmbird_get_zoho_item_categories();
	// fwrite( $fd, PHP_EOL . 'categories: ' . print_r( $zoho_categories, true ) );
	// Import category from zoho to woocommerce.
	$response[] = cmbird_zi_response_message( '-', '-', '--- Importing Category from zoho ---' );

	foreach ( $zoho_categories as $category ) {

		if ( '-1' === $category['category_id'] ) {
			continue;
		}

		if ( '-1' === $category['parent_category_id'] ) {

			if ( $category['category_id'] ) {
				// sanitize category name.
				$category_name = wc_sanitize_taxonomy_name( $category['name'] );
				// fwrite( $fd, PHP_EOL . 'Category Name : ' . $category_name );
				$term = get_term_by( 'name', $category_name, 'product_cat' );
				if ( ! empty( $term ) ) {
					$term_id = $term->term_id;
				} else {
					$term = wp_insert_term(
						$category['name'],
						'product_cat',
						array(
							'parent' => 0,
						)
					);
					if ( is_wp_error( $term ) ) {
						$response[] = cmbird_zi_response_message( $category['category_id'], $term->get_error_message(), '-' );
					} else {
						$term_id = $term['term_id'];
					}
				}
				if ( $term_id ) {
					// Update zoho category id for term(category) of woocommerce.
					update_option( 'cmbird_zoho_id_for_term_id_' . $term_id, $category['category_id'] );
				}
				$response[] = cmbird_zi_response_message( $category['category_id'], $category['name'], $term_id );
			}
		}
	}
	// fclose( $fd );
	// Closing of import of category from woo to zoho.
	$categories_terms = get_terms(
		array(
			'taxonomy' => 'product_cat',
			'child_of' => false,
		)
	);
	$log_head = '---Exporting Category to zoho---';
	$response[] = cmbird_zi_response_message( '-', '-', $log_head );
	if ( $categories_terms && count( $categories_terms ) > 0 ) {

		foreach ( $categories_terms as $term ) {
			//remove uncategorized from loop
			if ( $term->slug == 'uncategorized' ) {
				continue;
			}

			$zoho_cat_id = get_option( 'cmbird_zoho_id_for_term_id_' . $term->term_id );
			if ( empty( $zoho_cat_id ) ) {
				// fwrite( $fd, PHP_EOL . 'Category Name : ' . $term->name );
				$add_response = cmbird_zi_category_export( $term->name, $term->term_id );
				// fwrite( $fd, PHP_EOL . 'Response : ' . print_r( $add_response, true ) );
				$response[] = $add_response;
			} else {
				$response[] = cmbird_zi_response_message( $zoho_cat_id, 'Category name : "' . $term->name . '" already synced with zoho', $term->term_id );
			}
		}
	} else {
		$response[] = cmbird_zi_response_message( '-', 'Categories not available to export', '-' );
	}
	$encoded_response = wp_json_encode( $response );
	// fwrite( $fd, PHP_EOL . 'Final Response : ' . print_r( $encoded_response, true ) );
	// fclose( $fd );

	return $encoded_response;
	// exit();
}

/**
 * Create woocommerce category in zoho inventory.
 *
 * @return boolean - true if category created successfully.
 */
function cmbird_zi_category_export( $cat_name, $term_id = '0', $pid = '' ) {

	$zoho_inventory_oid = get_option( 'cmbird_zoho_inventory_oid' );
	$zoho_inventory_url = get_option( 'cmbird_zoho_inventory_url' );

	if ( ! empty( $pid ) || $pid > 0 ) {
		$zidata = '"name" : "' . $cat_name . '","parent_category_id" : "' . $pid . '",';
	} else {
		$zidata = '"name" : "' . $cat_name . '",';
	}

	$data = array(
		'JSONString' => '{' . $zidata . '}',
	);

	$url = $zoho_inventory_url . 'inventory/v1/categories/?organization_id=' . $zoho_inventory_oid;

	$execute_curl_call_handle = new CMBIRD_API_Handler_Zoho();
	$json = $execute_curl_call_handle->execute_curl_call_post( $url, $data );

	$code = $json->code;

	if ( '0' == $code || 0 == $code ) {
		foreach ( $json->category as $key => $value ) {
			if ( 'category_id' === $key ) {
				update_option( 'cmbird_zoho_id_for_term_id_' . $term_id, $value );
			}
		}
	}
	$response_msg = $json->message;

	//echo '<pre>'; print_r($json);
	$return = cmbird_zi_response_message( $code, $response_msg, $term_id );
	return wp_json_encode( $return );
}

/**
 * Function for getting zoho categories.
 */
function cmbird_get_zoho_item_categories() {
	// $fd = fopen( __DIR__ . '/cmbird_get_zoho_item_categories.txt', 'a+' );

	$zoho_inventory_oid = get_option( 'cmbird_zoho_inventory_oid' );
	$zoho_inventory_url = get_option( 'cmbird_zoho_inventory_url' );

	$url = $zoho_inventory_url . 'inventory/v1/categories/?organization_id=' . $zoho_inventory_oid;

	$execute_curl_call_handle = new CMBIRD_API_Handler_Zoho();
	$json = $execute_curl_call_handle->execute_curl_call_get( $url );
	$code = $json->code;
	$zoho_inventory_oid = get_option( 'cmbird_zoho_inventory_oid' );
	$zoho_inventory_url = get_option( 'cmbird_zoho_inventory_url' );

	$url = $zoho_inventory_url . 'inventory/v1/categories/?organization_id=' . $zoho_inventory_oid;

	$execute_curl_call_handle = new CMBIRD_API_Handler_Zoho();
	$json = $execute_curl_call_handle->execute_curl_call_get( $url );
	$code = $json->code;
	if ( '0' == $code || 0 == $code ) {
		$response = $json->categories;
		// Initialize an array to store unique categories
		$unique_categories = array();

		// Initialize an associative array to track category occurrences
		$category_count = array();

		// First pass: count occurrences and track the category with active items
		foreach ( $response as $category ) {
			$category_name = $category->name;

			// skip -1 category_id
			if ( '-1' === $category->category_id ) {
				continue;
			}
			// Count occurrences
			if ( isset( $category_count[ $category_name ] ) ) {
				++$category_count[ $category_name ];
			} else {
				$category_count[ $category_name ] = 1;
			}
		}

		// Second pass: add categories to unique array based on the counts and active items
		foreach ( $response as $category ) {
			// skip -1 category_id
			if ( '-1' === $category->category_id ) {
				continue;
			}

			// remove the duplicated categories from Zoho by doing a check on the active item
			if ( ! $category->has_active_items ) {
				// if category is not in category_count array, then do DELETE call to Zoho API to delete the category
				if ( 1 !== $category_count[ $category->name ] ) {
					$delete_url = $zoho_inventory_url . 'inventory/v1/categories/' . $category->category_id . '/?organization_id=' . $zoho_inventory_oid;
					$delete_response = $execute_curl_call_handle->execute_curl_call_delete( $delete_url );
					// fwrite( $fd, PHP_EOL . 'Category Deleted : ' . print_r( $delete_response, true ) );
				}
			}

			$category_name = $category->name;
			if ( 1 === $category_count[ $category_name ] || $category->has_active_items ) {
				// Add if mentioned only once
				$unique_categories[] = $category;
			}
		}

		// Reset keys to have a sequential array
		$unique_categories = array_values( $unique_categories );

	} else {
		$response = array();
		return $response;
	}

	$response = wp_json_encode( $unique_categories );
	// fwrite( $fd, print_r( $response, true ) );
	// fclose( $fd );

	return json_decode( $response, true );
}

/**
 * Get Term ID from Zoho category ID
 *
 * @return string - Term ID
 */

function cmbird_subcategories_term_id( $option_value ) {
	global $wpdb;
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}options WHERE option_value = %s", $option_value ) );
	if ( ! empty( $row->option_name ) ) {
		$ex = explode( 'zoho_id_for_term_id_', $row->option_name );
		$cat_id = $ex[1];
	}
	$term = get_term_by( 'term_id', $cat_id, 'product_cat' );
	if ( empty( $term ) ) {
		// remove the option from the database.
		delete_option( $row->option_name );
		return '';
	} else {
		return $cat_id;
	}
}
