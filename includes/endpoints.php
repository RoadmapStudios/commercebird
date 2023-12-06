<?php

/**
 * All Custom Enpdpoints are Here.
 *
 * @package  WooZo Inventory
 */

if(!defined('ABSPATH')) {
	exit;
}

// If product class not exists then import that class.
if(!class_exists('ProductClass') || !class_exists('ZI_CommonClass')) {
	require_once RMS_DIR_PATH.'vendor/autoload.php';
}

/**
 * @description: endpoint to return the access token to Wooventory.com
 * @last_modified: 6 April 2023
 */

function zi_token_init_hook_function() {
	/* Add Enpoints for syncing */
	add_action('rest_api_init', 'zoho_accesstoken');
}
add_action('init', 'zi_token_init_hook_function');

function zoho_accesstoken() {
	register_rest_route(
		'v2',
		'/zoho-accesstoken/',
		array(
			'methods' => WP_REST_Server::READABLE, /* WP_REST_Server::READABLE */
			'callback' => 'wp_get_zoho_accesstoken',
			'permission_callback' => 'request_is_from_wooventory',
		)
	);
}

function request_is_from_wooventory(WP_REST_Request $request) {
	// Check if the request is from the domain example.com
	$allowed_domain = array('http://localhost:8100', 'https://app.wooventory.com', 'capacitor://localhost', 'http://localhost');
	$origin = $request->get_header('Origin');
	// check if the origin is in our array of allowed domains
	if(in_array($origin, $allowed_domain)) {
		// Allow the REST request
		return true;
	}

	// If not from the allowed domain, return a permission error
	return new WP_Error('rest_forbidden', 'Sorry, this API endpoint is not accessible from your domain.', array('status' => 403));
}
function wp_get_zoho_accesstoken() {
	$response = array();

	// cron sync options
	$opt_category = get_option('zoho_item_category');
	if($opt_category) {
		$opt_category = unserialize($opt_category);
	} else {
		$opt_category = array();
	}
	$cron_options = array();
	$cron_options['disable_name'] = get_option('zoho_disable_itemname_sync_status');
	$cron_options['disable_price'] = get_option('zoho_disable_itemprice_sync_status');
	$cron_options['disable_image'] = get_option('zoho_disable_itemimage_sync_status');
	$cron_options['disable_description'] = get_option('zoho_disable_itemdescription_sync_status');
	$cron_options['disable_stock'] = get_option('zoho_stock_sync_status');
	$cron_options['accounting_stock'] = get_option('zoho_enable_accounting_stock_status');

	// connection
	$zoho_inventory_oid = get_option('zoho_inventory_oid');
	$zoho_inventory_url = get_option('zoho_inventory_url');
	$get_url = $zoho_inventory_url.'api/v1/organizations/'.$zoho_inventory_oid.'?organization_id='.$zoho_inventory_oid;

	$executeCurlCallHandle = new ExecutecallClass();
	$json = $executeCurlCallHandle->ExecuteCurlCallGet($get_url);
	$code = $json->code;
	if($code == 0 || $code == '0') {
		$access_token = get_option('zoho_inventory_access_token');
		$response['message'] = $json->message;
		$response['access_token'] = 'Bearer '.$access_token;
		$response['zi_org_id'] = $zoho_inventory_oid;
		$response['zi_api_url'] = $zoho_inventory_url;
		$response['item_categories'] = $opt_category;
		$response['cron_options'] = $cron_options;
		return new WP_REST_Response($response, 123);
	} else {
		return new WP_REST_Response('connection is not yet setup', 123);
	}
}

/**
 * @description: endpoint to receive shipping status and process it in wpdb
 * @requires: wc custom order statuses plugin
 * @last_modified: 11-06-2020
 */

if(class_exists('Alg_WC_Custom_Order_Statuses')) {

	function zi_shipping_status_init_hook_function() {
		/* Add Enpoints for syncing */
		add_action('rest_api_init', 'receive_zoho_shipping_status');
	}
	add_action('init', 'zi_shipping_status_init_hook_function');

	function receive_zoho_shipping_status() {
		register_rest_route(
			'v2',
			'/zoho-shipping-status/',
			array(
				'methods' => WP_REST_Server::CREATABLE, /* WP_REST_Server::READABLE */
				'callback' => 'wp_get_zoho_order_data',
				'permission_callback' => '__return_true',
			)
		);
	}

	function wp_get_zoho_order_data($request) {
		$dir = dirname(plugin_dir_path(__FILE__), 1);
		$postdata = $request->get_json_params();
		if(array_key_exists('JSONString', $_POST)) {
			$postdata = str_replace('\\', '', $_POST['JSONString']);
			file_put_contents($dir.'/zi_shipping_status.txt', $postdata);
		} elseif($_POST) {
			$postdata = str_replace('\\', '', $_POST);
			file_put_contents($dir.'/zi_shipping_status.txt', $postdata);
		}
		/*     $postdata = json_encode(file_get_contents('test_Data.txt')); */

		if(!empty($postdata)) {
			process_shipping_status($postdata);
			return new WP_REST_Response('Shipping Status Updated', 200);
		} else {
			return array(
				'code' => 500,
				'response' => 'Error receiving webhook response',
			);
		}
	}

	function process_shipping_status($postdata) {
		// start logging
		// $fd = fopen(__DIR__ . '/shipping-status-webhook.txt', 'w+');

		$order_data = json_decode($postdata);
		if(!is_object($order_data) && !is_array($order_data)) {
			$order_data = json_decode($order_data);
		}

		if(!empty($order_data->salesorder)) {
			$salesorder = $order_data->salesorder;
			/* Getting Salesorder id */

			$salesorder_id = $salesorder->salesorder_id;
			$formatted_status = trim($salesorder->shipped_status_formatted);
			$ship_status = strtolower($formatted_status);
			$packages = $salesorder->packages;

			/* Customer Query to get Order Id */
			$order_statuses = wc_get_order_statuses();
			// Find orders with the specific meta key and value
			$orders = wc_get_orders(
				array(
					'meta_key' => 'zi_salesorder_id',
					'meta_value' => $salesorder_id,
					'numberposts' => -1,
				)
			);
			// Loop through the found orders
			foreach($orders as $order) {
				$post_id = $order->get_id();
			}
			// Get Order Object
			$order = wc_get_order($post_id);
			// process cancelled zoho orders
			if('void' == trim($salesorder->status)) {
				$order->update_status('cancelled');
				$order->save();
				exit;
			}

			/* Getting Packages if empty in response */
			if(empty($packages) && !empty($post_id)) {
				$zoho_inventory_oid = get_option('zoho_inventory_oid');
				$zoho_inventory_url = get_option('zoho_inventory_url');
				$package_url = $zoho_inventory_url.'api/v1/packages?organization_id='.$zoho_inventory_oid;
				$executeCurlCallHandle = new ExecutecallClass();
				$json = $executeCurlCallHandle->ExecuteCurlCallGet($package_url);
				if($json->code == 0 || $json->code == '0') {
					$all_packages = $json->packages;
					foreach($all_packages as $packs) {
						$order_id = $packs->salesorder_id;
						if(trim($order_id) == trim($salesorder_id)) {
							// $package_id = $packs->package_id;
							$tracking_number = $packs->tracking_number;
							if(empty($ship_status)) {
								$ship_status = trim($packs->status);
							}
							$order->update_meta_data('zi_tracking_number', $tracking_number);
						}
					}
				}
			} elseif(!empty($packages) && !empty($post_id)) {
				foreach($packages as $package) {
					/* getting all ship and trace data from package */
					$tracking_number = $package->tracking_number;
					// $package_number = $package->package_number;
					$carrier = $package->carrier;
					$status = $package->status;
				}
				if(empty($ship_status)) {
					$ship_status = trim($status);
				}
				$order->update_meta_data('zi_tracking_number', $tracking_number);
				$order->update_meta_data('zi_shipping_carrier', $carrier);
			} else {
				if(!empty($post_id)) {
					$error = 'Post id not available for this '.$salesorder_id.' sales order';
				}
				/* Sending_ordererror_email($salesorder_id,$error); */
				return array(
					'code' => 300,
					'response' => 'Failed',
					'error_message' => $error,
				);
			}

			// process shipped status
			if(!empty($ship_status) && $post_id) {
				$order_statuses = array_map('strtolower', $order_statuses);
				if(in_array($ship_status, $order_statuses)) {
					$ship_status = remove_accents($ship_status);
					$order->update_meta_data('zi_shipping_status', $ship_status);
					$order->update_status($ship_status);
				} else {
					$order->update_meta_data('zi_shipping_status', $ship_status);
				}
			}
			$order->save();
			// fclose($fd);
			return array(
				'code' => 200,
				'response' => 'Success add tracking id and update shipped status',
			);
		} else {
			return array(
				'code' => 300,
				'response' => 'No sales order response there',
			);
		}
	}

	// Display the tracking number on My Order Detail page
	add_action('woocommerce_order_details_after_order_table', 'action_order_details_after_order_table');
	function action_order_details_after_order_table($order) {
		// Only on "My Account" > "Order View"
		if(is_wc_endpoint_url('view-order')) {
			$tracking_number = $order->get_meta('zi_tracking_number', true);
			$carrier = $order->get_meta('zi_shipping_carrier', true);
			if($tracking_number) {
				printf(
					'<p class="shipping-info">'.
					__('Your Shipping Tracking Number is: %s', 'woocommerce'),
					'<strong>'.$tracking_number.'</strong><br>'.__('Carrier: ', 'woocommerce').'<strong>'.$carrier.'</strong></p>'
				);
			} else {
				return;
			}
		}
	}
} // end of shipping status endpoint

/**
 * @description: endpoint to receive product data and process it in wpdb
 * @last_modified: 11-06-2020
 */

function zi_productdata_init_hook_function() {
	/* Add Enpoints for syncing */
	add_action('rest_api_init', 'receive_zoho_product_data');
}
add_action('init', 'zi_productdata_init_hook_function');

function receive_zoho_product_data() {
	register_rest_route(
		'v2',
		'/zoho-product/',
		array(
			'methods' => WP_REST_Server::CREATABLE, /* WP_REST_Server::READABLE */
			'callback' => 'wp_get_zoho_product_data',
			'permission_callback' => '__return_true',
		)
	);
}

function wp_get_zoho_product_data($request) {
	// $fd = fopen(__DIR__.'/webhook.txt', 'w+');

	$dir = dirname(plugin_dir_path(__FILE__), 1);
	$data = $request->get_json_params();
	if(array_key_exists('JSONString', $request)) {
		$postdata = str_replace('\\', '', $request['JSONString']);
		// fwrite($fd, PHP_EOL . 'JSONString: '. $_POST["JSONString"]);
		file_put_contents($dir.'/zoho-product.txt', $postdata);
	} elseif($data) {
		// fwrite($fd, PHP_EOL.'Item Data: '.print_r($data, true));
		file_put_contents($dir.'/zoho-product.txt', json_encode($data, 128));
	}

	if(!empty($data)) {
		zi_process_product_response($data);
	} else {
		return new WP_Error('code', __('Error', 'rmsZI'));
	}
	// fclose($fd);
}

/**
 * @description: endpoint to receive contact data and process it in wpdb
 * @last_modified: 11-06-2020
 */
function zi_process_product_response($data) {

	$fd = fopen(__DIR__.'/webhook.txt', 'w+');
	// log the type
	fwrite($fd, PHP_EOL.'Type: '.gettype($data));
	fwrite($fd, PHP_EOL.'Item: '.json_encode($data, 128));

	fclose($fd);
	// Accounting stock mode check
	$accounting_stock = get_option('zoho_enable_accounting_stock_status');
	$zi_enable_warehousestock = get_option('zoho_enable_warehousestock_status');
	$warehouse_id = get_option('zoho_warehouse_id');

	global $wpdb;

	// variable item sync
	if(array_key_exists('item', $data)) {
		$item = $data['item'];
		$item_id = $item['item_id'];
		$item_name = $item['name'];
		$item_price = $item['rate'];
		$item_sku = $item['sku'];
		$item_description = $item['description'];
		$item_brand = $item['brand'];
		$item_tags_hash = $item['custom_field_hash'];
		$item_tags = $item_tags_hash['cf_tags'];

		// Stock mode check
		$warehouses = $item['warehouses'];
		if($zi_enable_warehousestock == true) {
			foreach($warehouses as $warehouse) {
				if($warehouse['warehouse_id'] === $warehouse_id) {
					if($accounting_stock == 'true') {
						$item_stock = $warehouse['warehouse_available_for_sale_stock'];
					} else {
						$item_stock = $warehouse['warehouse_actual_available_for_sale_stock'];
					}
				}
			}
		} elseif($accounting_stock) {
			$item_stock = $item['available_for_sale_stock'];
		} else {
			$item_stock = $item['actual_available_for_sale_stock'];
		}
		$item_image = $item['image_name'];
		$group_name = $item['group_name'];
		$item_category = $item['category_name'];
		$groupid = $item['group_id'];

		// Item package details
		$details = $item['package_details'];
		$weight = floatval($details['weight']);
		$length = floatval($details['length']);
		$width = floatval($details['width']);
		$height = floatval($details['height']);

		// getting the admin user ID
		$query = new WP_User_Query(
			array(
				'role' => 'Administrator',
				'count_total' => false,
			)
		);
		$users = $query->get_results();
		if($users) {
			$admin_author_id = $users[0]->ID;
		} else {
			$admin_author_id = '1';
		}

		// fwrite($fd, PHP_EOL . '$groupid : ' . $groupid);
		if(!empty($groupid)) {
			$zi_disable_itemdescription_sync = get_option('zoho_disable_itemdescription_sync_status');
			if(!empty($item_description) && $zi_disable_itemdescription_sync != 'true') {
				// fwrite($fd, PHP_EOL . 'Item description update : ' . $item_description);
				$tbl_prefix = $wpdb->prefix;
				$wpdb->update($tbl_prefix.'posts', array('post_excerpt' => $item_description), array('ID' => $groupid), array('%s'), array('%d'));
			}

			// Tags
			if(!empty($item_tags)) {
				$final_tags = explode(',', $item_tags);
				wp_set_object_terms($groupid, $final_tags, 'product_tag');
			}

			// Brand
			if(!empty($item_brand)) {
				wp_set_object_terms($groupid, $item_brand, 'product_brand');
			}

			// fwrite($fd, PHP_EOL . 'Query : ' . 'SELECT * FROM ' . $wpdb->prefix . "postmeta WHERE meta_key='zi_item_id' AND meta_value='" . $groupid . "'");
			// find parent variable product
			$row = $wpdb->get_row('SELECT * FROM '.$wpdb->prefix."postmeta WHERE meta_key='zi_item_id' AND meta_value='".$groupid."'");
			$group_id = $row->post_id;
			// fwrite($fd, PHP_EOL . 'Row Data : ' . print_r($row, true));
			// fwrite($fd, PHP_EOL . 'parent postID : ' . $group_id);

			$rowItem = $wpdb->get_row('SELECT * FROM '.$wpdb->prefix."postmeta WHERE meta_key='zi_item_id' AND meta_value='".$item_id."'");
			$variation_id = $rowItem->post_id;
			if($variation_id) { // updating existing variations
				$variation = new WC_Product_Variation($variation_id);
				## Set/save all other data

				// Prices
				if(!empty($item['rate'])) {
					$variation->set_price($item['rate']);
				}
				$variation->set_regular_price($item['rate']);
				// Stock
				if(!empty($item_stock)) {
					// fwrite($fd, PHP_EOL . 'Stock is here:'. $item_stock);
					$variation->set_stock_quantity($item_stock);
					$variation->set_manage_stock(true);
					// $variation->set_stock_status('');
				} else {
					// fwrite($fd, PHP_EOL . 'Available Stock : false');
					$variation->set_manage_stock(false);
				}
				// featured image
				$zi_disable_itemimage_sync = get_option('zoho_disable_itemimage_sync_status');
				if(!empty($item_image) && $zi_disable_itemimage_sync != 'true') {
					// fwrite($fd, PHP_EOL . 'Sync Image' );
					$imageClass = new ImageClass();
					$imageClass->args_attach_image($item_id, $item_name, $variation_id, $item_image, $admin_author_id);
				}

				$variation->save(); // Save the data
			} else {
				$attribute_name11 = $item['attribute_option_name1'];
				$attribute_name12 = $item['attribute_option_name2'];
				$attribute_name13 = $item['attribute_option_name3'];

				if(!empty($attribute_name11)) {

					$attribute_arr[$item['attribute_name1']] = $attribute_name11;
				}
				if(!empty($attribute_name12)) {

					$attribute_arr[$item['attribute_name2']] = $attribute_name12;
				}
				if(!empty($attribute_name13)) {

					$attribute_arr[$item['attribute_name3']] = $attribute_name13;
				}
				$variation_data = array(
					'attributes' => $attribute_arr,
					'sku' => $item['sku'],
					'regular_price' => $item['rate'],
					'stock_qty' => $item_stock,
				);

				$status = ($item['status'] == 'active') ? 'publish' : 'draft';
				$variation_post = array(
					'post_title' => $item['name'],
					'post_name' => $item['name'],
					'post_status' => $status,
					'post_parent' => $group_id,
					'post_type' => 'product_variation',
					'guid' => get_the_permalink($group_id),
				);
				// Creating the product variation
				$variation_id = wp_insert_post($variation_post);

				// Get an instance of the WC_Product_Variation object
				$variation = new WC_Product_Variation($variation_id);

				// Iterating through the variations attributes
				foreach($variation_data['attributes'] as $attribute => $term_name) {
					update_post_meta($variation_id, 'attribute_'.strtolower(str_replace(' ', '-', $attribute)), trim($term_name));
					update_post_meta($variation_id, 'group_id_store', $group_id);
				}

				// SKU
				// $variation->set_sku($variation_data['sku']);

				// Prices
				$variation->set_regular_price($variation_data['regular_price']);
				$variation_sale_price = get_post_meta($variation_id, '_sale_price', true);
				if(empty($variation_sale_price)) {
					$variation->set_price($variation_data['regular_price']);
				}

				// featured image
				$zi_disable_itemimage_sync = get_option('zoho_disable_itemimage_sync_status');
				if(!empty($item_image) && $zi_disable_itemimage_sync != 'true') {
					$imageClass = new ImageClass();
					$imageClass->args_attach_image($item_id, $item_name, $variation_id, $item_image, $admin_author_id);
				}

				// Stock
				if(!empty($variation_data['stock_qty'])) {
					$variation->set_stock_quantity($variation_data['stock_qty']);
					$variation->set_manage_stock(true);
					$variation->set_stock_status('');
				} else {
					$variation->set_manage_stock(false);
				}
				$variation->set_weight(''); // weight (reseting)
				$variation->save(); // Save the data
				update_post_meta($variation_id, 'zi_item_id', $item_id);

				// End group item add process
				unset($attribute_arr);
			}
			if($variation_id) {
				// weight & dimensions
				update_post_meta($variation_id, '_weight', $weight);
				update_post_meta($variation_id, '_length', $length);
				update_post_meta($variation_id, '_width', $width);
				update_post_meta($variation_id, '_height', $height);
			}
			wc_delete_product_transients($group_id); // Clear/refresh cache
			// end of grouped item creation
		} else {
			// fwrite($fd, PHP_EOL . 'Inside simple items');
			// fwrite($fd, PHP_EOL . 'Item description Simple : ' . $item_description);
			$rowItem = $wpdb->get_row('SELECT * FROM '.$wpdb->prefix."postmeta WHERE meta_key='zi_item_id' AND meta_value='".$item_id."'");
			$mapped_product_id = $rowItem->post_id;
			// simple product
			// fwrite($fd, PHP_EOL . 'Before Match check');
			$pdt_id = '';
			if(!empty($mapped_product_id)) {
				$pdt_id = $mapped_product_id;
				// Sync product name if that is allowed.
				$product_class = new ProductClass();
				$product_class->update_product_name($pdt_id, $item_name);
			} elseif(empty($item['is_combo_product'])) {
				// fwrite($fd, PHP_EOL . 'Inside create product');
				$current_user = wp_get_current_user();
				if(!empty($current_user) && $current_user->ID) {
					$admin_author_id = $current_user->ID;
					// get admin user id who started the cron job.
				} else {
					$admin_author_id = get_option('zi_cron_admin');
				}

				// Check if Category is selected before creating simple item
				$opt_category = get_option('zoho_item_category');
				$category_id = $item['category_id'];
				if($opt_category) {
					$opt_category = unserialize($opt_category);
					if(in_array($category_id, $opt_category)) {
						$product_class = new ProductClass();
						$pdt_id = $product_class->zi_product_to_woocommerce($item, $item_stock);
					}
				}
				// fwrite($fd, PHP_EOL . 'After adding it : ' . $pdt_id);
			}

			// If there is product id then update metadata.
			if(!empty($pdt_id)) {
				$simple_product = wc_get_product($pdt_id);
				// update the zi_item_id using the product instance 
				$simple_product->update_meta_data('zi_item_id', $item_id);

				// fwrite($fd, PHP_EOL . 'Insite mappping metadata');
				// Update the product SKU
				$simple_product->set_sku($item_sku);
				// fwrite($fd, PHP_EOL . '$item_price : ' . $item_price);
				// price
				$sale_price = get_post_meta( $pdt_id, '_sale_price', true );
				$simple_product->set_regular_price($item_price);
				if ( empty( $sale_price ) ) {
					$simple_product->update_meta_data('_price', $item_price);
				}

				// description
				$zi_disable_itemdescription_sync = get_option('zoho_disable_itemdescription_sync_status');
				if(!empty($item_description) && $zi_disable_itemdescription_sync != 'true') {
					$simple_product->set_description($item_description);
				}

				// Tags
				if(!empty($item_tags)) {
					$final_tags = explode(',', $item_tags);
					wp_set_object_terms($pdt_id, $final_tags, 'product_tag');
				}

				// Brand
				if(!empty($item_brand)) {
					wp_set_object_terms($pdt_id, $item_brand, 'product_brand');
				}

				// stock
				$zi_stock_sync = get_option('zoho_stock_sync_status');
				if($zi_stock_sync != 'true') {
					// fwrite($fd, PHP_EOL . 'Inside1');
					if('NULL' !== gettype($item_stock)) {
						// fwrite($fd, PHP_EOL . 'Inside1.1');
						// Set manage stock to yes
						$simple_product->set_manage_stock(true);
						// Update stock for simple product
						$simple_product->set_stock_quantity(number_format($item_stock, 0, '.', ''));
						if($item_stock > 0) {
							// fwrite($fd, PHP_EOL . 'Inside2');
							$status = 'instock';
							// Update stock status
							$simple_product->set_stock_status($status);
							wp_set_post_terms($pdt_id, $status, 'product_visibility', true);
						} else {
							// fwrite($fd, PHP_EOL . 'Inside3');
							$backorder_status = get_post_meta($pdt_id, '_backorders', true);
							$status = ($backorder_status === 'yes') ? 'onbackorder' : 'outofstock';
							$simple_product->set_stock_status($status);
							wp_set_post_terms($pdt_id, $status, 'product_visibility', true);
						}
					}
				}
				// fwrite($fd, PHP_EOL . 'After stock');
				// Update weight & dimensions of simple product
				$simple_product->set_weight($weight);
				$simple_product->set_length($length);
				$simple_product->set_width($width);
				$simple_product->set_height($height);
				
				// featured image
				$zi_disable_itemimage_sync = get_option('zoho_disable_itemimage_sync_status');
				if(!empty($item_image) && $zi_disable_itemimage_sync != 'true') {
					$imageClass = new ImageClass();
					$imageClass->args_attach_image($item_id, $item_name, $pdt_id, $item_image, $admin_author_id);
				}
				// category
				if(!empty($item_category) && empty($group_name)) {
					$term = get_term_by('name', $item_category, 'product_cat');
					$term_id = $term->term_id;
					if(empty($term_id)) {
						$term = wp_insert_term(
							$item_category,
							'product_cat',
							array(
								'parent' => 0,
							)
						);
						$term_id = $term->term_id;
					}
					// Remove "uncategorized" category if assigned
					$uncategorized_term = get_term_by('slug', 'uncategorized', 'product_cat');
					if($uncategorized_term && has_term($uncategorized_term->term_id, 'product_cat', $pdt_id)) {
						wp_remove_object_terms($pdt_id, $uncategorized_term->term_id, 'product_cat');
					}
					if(!is_wp_error($term_id) && isset($term->term_id)) {
						$existingTerms = wp_get_object_terms($pdt_id, 'product_cat');
						if($existingTerms && count($existingTerms) > 0) {
							$importClass = new ImportProductClass();
							$isTermsExist = $importClass->zi_check_terms_exists($existingTerms, $term_id);
							if(!$isTermsExist) {
								update_post_meta($pdt_id, 'zi_category_id', $item['category_id']);
								wp_add_object_terms($pdt_id, $term_id, 'product_cat');
							}
						} else {
							update_post_meta($pdt_id, 'zi_category_id', $item['category_id']);
							wp_set_object_terms($pdt_id, $term_id, 'product_cat');
						}
					}
				}

				// Map taxes while syncing product from zoho.
				if($item['tax_id']) {
					$zi_common_class = new ZI_CommonClass();
					$woo_tax_class = $zi_common_class->get_woo_tax_class_from_zoho_tax_id($item['tax_id']);
					$simple_product->set_tax_status('taxable');
					$simple_product->set_tax_class($woo_tax_class);
				}
				$simple_product->save();
				wc_delete_product_transients($pdt_id); // Clear/refresh cache
			}
		}
		return array(
			'code' => 200,
			'response' => 'Success',
		);
	} elseif(!empty($product_data['inventory_adjustment'])) {
		$item = $product_data['inventory_adjustment'];
		$line_items = $item['line_items'] ? $item['line_items'] : array();
		// get first item from line items array
		$item_id = $line_items[0]['item_id'];
		$adjusted_stock = $line_items[0]['quantity_adjusted'];

		$rowItem = $wpdb->get_row('SELECT * FROM '.$wpdb->prefix."postmeta WHERE meta_key='zi_item_id' AND meta_value='".$item_id."'");
		$mapped_product_id = $rowItem->post_id;

		if(!empty($mapped_product_id)) {
			// stock
			$zi_stock_sync = get_option('zoho_stock_sync_status');
			$product = wc_get_product($mapped_product_id);
			// Check if the product is in stock
			if($zi_stock_sync != 'true') {
				if($product->is_in_stock()) {
					// Get stock quantity
					$stock_quantity = $product->get_stock_quantity();
					$new_stock = $stock_quantity + $adjusted_stock;
					$product->set_stock_quantity($new_stock);
					$product->save();
				} else {
					$product->set_stock_quantity($adjusted_stock);
					$product->save();
				}
			}
		}
		return array(
			'code' => 200,
			'response' => 'Success',
		);
	} else {
		return array(
			'code' => 300,
			'response' => 'No product found',
		);
	}
	// fclose($fd);
}
