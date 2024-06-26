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

/**
 * Zoho Api Error Email
 *
 * @param [string] $subject - Email subject.
 * @param [string] $message - Message.
 *
 * @return void
 */
function error_log_api_email( $subject, $message ) {
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

add_action( 'wp_ajax_zoho_ajax_call_variable_item_from_zoho', 'zoho_ajax_call_variable_item_from_zoho' );
add_action( 'wp_ajax_nopriv_zoho_ajax_call_variable_item_from_zoho', 'zoho_ajax_call_variable_item_from_zoho' );
function zoho_ajax_call_variable_item_from_zoho() {
	// Clear Orphan data.
	$zi_common_class = new ZI_CommonClass();
	$zi_common_class->clear_orphan_data();

	// get category to filter by category
	$opt_category = get_option( 'zoho_item_category' );

	if ( $opt_category ) {
		$opt_category = unserialize( $opt_category );
	} else {
		$opt_category = array();
	}

	// Retrieve the last synced category index from the previous run
	$last_synced_category_index = get_option( 'last_synced_category_index_groupitems', 0 );

	// Slice the category array to start from the last synced category index
	$opt_category = array_slice( $opt_category, $last_synced_category_index );
	$category_index = 0;
	if ( ! empty( $opt_category ) ) {
		foreach ( $opt_category as $category_index => $category_id ) {
			$data_arr = (object) array();
			// get last backed up page number for particular category Id.
			// And start syncing from the last synced page.
			// If no page number available, it will start from zero.
			$last_synced_page = get_option( 'group_item_sync_page_cat_id_' . $category_id );
			$data_arr->page = $last_synced_page;
			if ( ! intval( $last_synced_page ) ) {
				$data_arr->page = 1;
			}
			$data_arr->category = $category_id;
			$existing_schedule = as_has_scheduled_action( 'import_group_items_cron', array( $data_arr ) );

			// Check if the scheduled action exists
			if ( ! $existing_schedule ) {
				as_schedule_single_action( time(), 'import_group_items_cron', array( $data_arr ) );
			}

			// Update the last synced category index in the options
			update_option( 'last_synced_category_index_groupitems', $last_synced_category_index + $category_index + 1 );
		}
		// Check if all categories have been imported or passed to the loop
		$total_categories = count( $opt_category );
		$processed_categories = $last_synced_category_index + $category_index + 1;

		if ( $processed_categories >= $total_categories ) {
			// Reset the last synced category index
			update_option( 'last_synced_category_index_groupitems', 0 );
		}
	}

	wp_send_json_success( array( 'message' => 'Items are being imported in background. You can visit other tabs :).' ) );
	wp_die();
}


// Attach the function to the cron event
add_action( 'zi_execute_import_sync', 'zoho_ajax_call_item_from_zoho_func' );

/**
 * Function to be called at simple item sync from zoho to woo ajax call.
 */

add_action( 'wp_ajax_zoho_ajax_call_item_from_zoho', 'zoho_ajax_call_item_from_zoho_func' );
add_action( 'wp_ajax_nopriv_zoho_ajax_call_item_from_zoho', 'zoho_ajax_call_item_from_zoho_func' );
function zoho_ajax_call_item_from_zoho_func() {
	$zoho_item_category = get_option( 'zoho_item_category' );
	$last_synced_category_index = get_option( 'last_synced_category_index', 0 );

	if ( $zoho_item_category ) {
		$categories = unserialize( $zoho_item_category );
		$categories = array_slice( $categories, $last_synced_category_index );
	} else {
		$categories = array();
	}

	if ( empty( $categories ) ) {
		wp_send_json_error( array( 'message' => __( 'Please select at least one category from cron tab', 'commercebird' ) ) );
	} else {
		foreach ( $categories as $index => $category_id ) {
			$data = (object) array();
			$last_synced_page = get_option( 'simple_item_sync_page_cat_id_' . $category_id );
			if ( ! intval( $last_synced_page ) ) {
				$last_synced_page = 1;
			}

			$data->page = $last_synced_page;
			$data->category = $category_id;
			$existing_schedule = as_has_scheduled_action( 'import_simple_items_cron', array( $data ) );

			if ( ! $existing_schedule ) {
				as_schedule_single_action( time(), 'import_simple_items_cron', array( $data ) );
			}

			update_option( 'last_synced_category_index', $last_synced_category_index + $index + 1 );
		}

		$total_categories = count( $categories );
		$processed_categories = $last_synced_category_index + $index + 1;

		if ( $processed_categories >= $total_categories ) {
			update_option( 'last_synced_category_index', 0 );
		}
	}
	wp_send_json_success( array( 'message' => __( 'Items are being imported in background. You can visit other tabs :).', 'commercebird' ) ) );
	wp_die();
}

/**
 * Zoho Inventory Function sync items from WooCommerce to Zoho in Background
 */
add_action( 'wp_ajax_zoho_ajax_call_item', 'zoho_ajax_call_item' );
add_action( 'wp_ajax_nopriv_zoho_ajax_call_item', 'zoho_ajax_call_item' );
function zoho_ajax_call_item() {
	// $fd = fopen(__DIR__ . '/zoho_ajax_call_item.txt', 'a+');

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
	// adding all items in the queue
	foreach ( $post_ids as $post_id ) {
		// Add post ID to the array
		$product_ids[] = $post_id;

		// Check if the array contains 10 product IDs
		if ( count( $product_ids ) === 10 ) {
			// Pass the array of product IDs to the scheduler function
			as_schedule_single_action( time(), 'sync_zi_product_cron', array( $product_ids ) );

			// Clear the array for the next batch of product IDs
			$product_ids = array();
		}
	}

	// Check if there are any remaining product IDs in the array
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
add_action( 'zoho_contact_sync', 'zoho_contacts_import' );
add_action( 'wp_ajax_import_zoho_contacts', 'zoho_contacts_import' );
add_action( 'wp_ajax_nopriv_import_zoho_contacts', 'zoho_contacts_import' );
function zoho_contacts_import( $page = '' ) {
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
add_action( 'wp_ajax_zoho_ajax_call_composite_item_from_zoho', 'zi_sync_composite_item_from_zoho' );
add_action( 'wp_ajax_nopriv_zoho_ajax_call_composite_item_from_zoho', 'zi_sync_composite_item_from_zoho' );
function zi_sync_composite_item_from_zoho() {

	$opt_category = get_option( 'zoho_item_category' );
	if ( $opt_category ) {
		$opt_category = unserialize( $opt_category );
	} else {
		$opt_category = array();
	}

	$item_add_resp = array();
	foreach ( $opt_category as $category_id ) {
		$product_class = new import_product_class();
		$response = $product_class->recursively_sync_composite_item_from_zoho( 1, $category_id, 'sync' );
		$item_add_resp = array_merge( $item_add_resp, $response );
	}
	send_log_message_to_admin( $item_add_resp, 'Log Message for manual sync', 'Composite item sync from zoho' );
	wp_send_json_success( $item_add_resp );
	wp_die();
}

/**
 * Send log message to admin
 *
 * @return void
 */
function send_log_message_to_admin( $sync_logs, $subject, $message ) {
	$table_root = "<h3>$message</h3>";
	$table_root .= '<table><thead><tr><th>Action</th><th> Log message</th></tr></thead><tbody>';

	foreach ( $sync_logs as $logs ) {
		$table_root .= "<tr><td>$logs->resp_id</td><td>$logs->message</td></tr>";
	}
	$table_root .= '</tbody></table>';
	error_log_api_email( $subject, $table_root );
}

// ZohoInventory api call hook to sync composite products from WooCommerce to Zoho.
add_action( 'wp_ajax_zoho_ajax_call_composite_item', 'zi_sync_composite_item_to_zoho' );
add_action( 'wp_ajax_nopriv_zoho_ajax_call_composite_item', 'zi_sync_composite_item_to_zoho' );
function zi_sync_composite_item_to_zoho() {

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
function zi_response_message( $index_col, $message, $woo_id = '' ) {
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

add_action( 'wp_ajax_zoho_ajax_call_subcategory', 'ajax_subcategory_sync_call' );
function ajax_subcategory_sync_call() {
	$response = array(); // Response array.
	$zoho_subcategories = get_zoho_item_categories();
	// Import category from zoho to woocommerce.
	$response[] = zi_response_message( '-', '-', '--- Importing Sub Category from zoho ---' );
	$zoho_subcategories = $zoho_subcategories['categories'];
	//echo '<pre>'; print_r($zoho_categories);
	foreach ( $zoho_subcategories as $subcategory ) {
		if ( $subcategory['parent_category_id'] > 0 ) {
			if ( $subcategory['category_id'] !== '-1' && $subcategory['category_id'] > 0 ) {
				$term = get_term_by( 'name', $subcategory['name'], 'product_cat' );

				if ( $subcategory['parent_category_id'] > 0 ) {

					$zoho_pid = intval( subcategories_term_id( $subcategory['parent_category_id'] ) );
				}

				if ( empty( $term ) && $zoho_pid > 0 ) {
					$child_term = wp_insert_term(
						$subcategory['name'],
						'product_cat',
						array(
							'parent' => $zoho_pid,
						)
					);
					// Check if there is error in creating child category add message.
					if ( is_wp_error( $child_term ) ) {
						$response[] = zi_response_message( $subcategory['category_id'], $child_term->get_error_message(), '-' );
					} else {
						$term_id = $child_term['term_id'];
					}
				} elseif ( $term instanceof WP_Term ) {
					$term_id = $term->term_id;
				}

				if ( $term_id && $zoho_pid > 0 ) {
					// Update zoho sub category id for term(sub category) of woocommerce.
					update_option( 'zoho_id_for_term_id_' . $term_id, $subcategory['category_id'] );
				}
				$response[] = zi_response_message( $subcategory['category_id'], $subcategory['name'], $term_id );
			}
		}
	}
	// Closing of import of category from woo to zoho .

	// Get product categories from woocommerce.
	$categories_terms = get_terms(
		'product_cat',
	);
	$log_head = '---Exporting Sub Category to zoho---';
	$response[] = zi_response_message( '-', '-', $log_head );
	$c = 0;
	if ( $categories_terms && count( $categories_terms ) > 0 ) {

		foreach ( $categories_terms as $parent_term ) {

			$subcategories_terms = get_terms(
				array(
					'taxonomy' => 'product_cat',
					'hide_empty' => false,
					'child_of' => true,
				)
			);

			if ( $subcategories_terms && count( $subcategories_terms ) > 0 ) {

				foreach ( $subcategories_terms as $term ) {
					//remove uncategorized from loop
					if ( $term->slug == 'uncategorized' ) {
						continue;
					}

					$zoho_cat_id = get_option( 'zoho_id_for_term_id_' . $term->term_id );
					if ( empty( $zoho_cat_id ) ) {

						$zoho_cat_id = get_option( 'zoho_id_for_term_id_' . $parent_term->term_id );
						$pid = $zoho_cat_id;

						$addresponse = create_woo_cat_to_zoho( $term->name, $term->term_id, $pid );
						$response[] = $addresponse;
					} else {
						$response[] = zi_response_message( $zoho_cat_id, 'Sub Category name : "' . $term->name . '" already synced with zoho', $term->term_id );
					}
					++$c;
				}
			}
		}
	}

	if ( 0 === $c ) {
		$response[] = zi_response_message( '-', 'Sub Categories not available to export', '-' );
	}
	echo wp_json_encode( $response );
	exit();
}

/**
 * Cron job to update category per day.
 */
if ( ! wp_next_scheduled( 'zoho_sync_category_cron' ) ) {
	wp_schedule_event( time(), '1day', 'zoho_sync_category_cron' );
}
add_action( 'zoho_sync_category_cron', 'ajax_category_sync_call' );
/**
 * Sync zoho category to woocommerce.
 *
 * @return void
 */

add_action( 'wp_ajax_zoho_ajax_call_category', 'ajax_category_sync_call' );
function ajax_category_sync_call() {
	$response = array(); // Response array.
	$zoho_categories = get_zoho_item_categories();
	// Import category from zoho to woocommerce.
	$response[] = zi_response_message( '-', '-', '--- Importing Category from zoho ---' );

	$zoho_categories = $zoho_categories['categories'];

	foreach ( $zoho_categories as $category ) {

		if ( $category['parent_category_id'] == '-1' ) {

			if ( $category['category_id'] !== '-1' && $category['category_id'] > 0 ) {
				$term = get_term_by( 'name', $category['name'], 'product_cat' );
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
						$response[] = zi_response_message( $category['category_id'], $term->get_error_message(), '-' );
					} else {
						$term_id = $term['term_id'];
					}
				}
				if ( $term_id ) {
					// Update zoho category id for term(category) of woocommerce.
					update_option( 'zoho_id_for_term_id_' . $term_id, $category['category_id'] );
				}
				$response[] = zi_response_message( $category['category_id'], $category['name'], $term_id );
			}
		}
	}
	// Closing of import of category from woo to zoho.
	$categories_terms = get_terms(
		'product_cat',
	);
	$log_head = '---Exporting Category to zoho---';
	$response[] = zi_response_message( '-', '-', $log_head );
	if ( $categories_terms && count( $categories_terms ) > 0 ) {

		foreach ( $categories_terms as $term ) {
			//remove uncategorized from loop
			if ( $term->slug == 'uncategorized' ) {
				continue;
			}

			$zoho_cat_id = get_option( 'zoho_id_for_term_id_' . $term->term_id );
			if ( empty( $zoho_cat_id ) ) {
				$addresponse = create_woo_cat_to_zoho( $term->name, $term->term_id );
				$response[] = $addresponse;
			} else {
				$response[] = zi_response_message( $zoho_cat_id, 'Category name : "' . $term->name . '" already synced with zoho', $term->term_id );
			}
		}
	} else {
		$response[] = zi_response_message( '-', 'Categories not available to export', '-' );
	}
	echo wp_json_encode( $response );
	exit();
}

/**
 * Create woocommerce category in zoho store.
 *
 * @return void
 */
function create_woo_cat_to_zoho( $cat_name, $term_id = '0', $pid = '' ) {

	$zoho_inventory_oid = get_option( 'zoho_inventory_oid' );
	$zoho_inventory_url = get_option( 'zoho_inventory_url' );

	if ( ! empty( $pid ) || $pid > 0 ) {
		$zidata = '"name" : "' . $cat_name . '","parent_category_id" : "' . $pid . '",';
	} else {
		$zidata = '"name" : "' . $cat_name . '",';
	}

	$data = array(
		'JSONString' => '{' . $zidata . '}',
	);

	$url = $zoho_inventory_url . 'inventory/v1/categories/?organization_id=' . $zoho_inventory_oid;

	$execute_curl_call_handle = new ExecutecallClass();
	$json = $execute_curl_call_handle->execute_curl_call_post( $url, $data );

	$code = $json->code;

	if ( '0' == $code || 0 == $code ) {
		foreach ( $json->category as $key => $value ) {
			if ( $key === 'category_id' ) {

				update_option( 'zoho_id_for_term_id_' . $term_id, $value );
			}
		}
	}
	$response_msg = $json->message;

	//echo '<pre>'; print_r($json);

	return zi_response_message( $code, $response_msg, $term_id );
}

/**
 * Function for getting zoho categories.
 */
function get_zoho_item_categories() {
	// $fd = fopen( __DIR__ . '/get_zoho_item_categories.txt', 'a+' );

	$zoho_inventory_oid = get_option( 'zoho_inventory_oid' );
	$zoho_inventory_url = get_option( 'zoho_inventory_url' );

	$url = $zoho_inventory_url . 'inventory/v1/categories/?organization_id=' . $zoho_inventory_oid;

	$execute_curl_call_handle = new ExecutecallClass();
	$json = $execute_curl_call_handle->execute_curl_call_get( $url );

	$response = wp_json_encode( $json );
	// fwrite( $fd, PHP_EOL . '$response : ' . print_r( $response, true ) );
	// fclose( $fd );
	return json_decode( $response, true );
}

/**
 * Get Term ID from Zoho category ID
 *
 * @return boolean
 */

function subcategories_term_id( $option_value ) {

	global $wpdb;

	$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}options WHERE option_value = %s", $option_value ) );

	if ( ! empty( $row->option_name ) ) {
		$ex = explode( 'zoho_id_for_term_id_', $row->option_name );
		$cat_id = $ex[1];
	} else {
		$cat_id = 0;
	}

	return $cat_id;
}
