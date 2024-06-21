<?php

/**
 * All WooCommerce related functions.
 *
 * @category WooCommerce
 * @package  CommerceBird
 * @author   Fawad Tiemoerie <info@roadmapstudios.com>
 * @license  GNU General Public License v3.0
 * @link     https://commercebird.com
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper functions to ensure correct handling of Data being transferred via rest api
 */

function cmbird_clear_product_cache( $object, $request, $is_creating ) {
	if ( ! $is_creating ) {
		$product_id                  = $object->get_id();
		$zoho_inventory_access_token = get_option( 'zoho_inventory_access_token' );
		$zi_product_sync = get_option( 'zoho_disable_product_sync_status' );
		if ( ! empty( $zoho_inventory_access_token ) && ! $zi_product_sync ) {
			$product_handler = new ProductClass();
			$product_handler->zi_product_sync( $product_id );
		}
		wc_delete_product_transients( $product_id );
	}
}
add_action( 'woocommerce_rest_insert_product_object', 'cmbird_clear_product_cache', 10, 3 );



/**
 * Function to update the Contact in Zoho when customer updates address on frontend
 * @param $user_id
 */
function cmbird_update_contact_via_accountpage( $user_id ) {
	$zoho_inventory_access_token = get_option( 'zoho_inventory_access_token' );
	if ( ! empty( $zoho_inventory_access_token ) ) {
		$contact_class_handle = new ContactClass();
		$contact_class_handle->contact_update_function( $user_id );
	} else {
		return;
	}
}
add_action( 'profile_update', 'cmbird_update_contact_via_accountpage' );

/**
 * Function to be called by hook when new product is added in WooCommerce.
 *
 * @param $product_id
 * @return void
 */
add_action( 'woocommerce_update_product', 'zi_product_sync_class', 10, 1 );
add_action( 'wp_ajax_zoho_admin_product_sync', 'zi_product_sync_class' );
function zi_product_sync_class( $product_id ) {
	if ( ! is_admin() ) {
		return;
	}
	if ( ! $product_id ) {
		// Check nonce for security
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'zoho_admin_product_sync' ) ) {
			wp_send_json_error( 'Nonce verification failed' );
		} else {
			$product_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
			if ( ! $product_id ) {
				wp_send_json_error( 'Invalid Product ID' );
			}
		}
	}
	$zi_product_sync             = get_option( 'zoho_disable_product_sync_status' );
	$zoho_inventory_access_token = get_option( 'zoho_inventory_access_token' );
	if ( ! $zi_product_sync && ! empty( $zoho_inventory_access_token ) ) {
		$product_handler = new ProductClass();
		$product_handler->zi_product_sync( $product_id );
	}
	// if its variable product but without variations, then sync it.
	$product = wc_get_product( $product_id );
	if ( $product->is_type( 'variable' ) ) {
		$variations = $product->get_available_variations();
		if ( isset( $variations ) && count( $variations ) === 0 ) {
			$zi_product_id = get_post_meta( $product_id, 'zi_item_id', true );
			if ( ! empty( $zi_product_id ) ) {
				$product_handler = new import_product_class();
				$product_handler->import_variable_product_variations( $zi_product_id, $product_id );
			}
		}
	}
}

/**
 * Bulk-action to sync products from WooCommerce to Zoho
 *
 * @param: $bulk_array
 * @return: $bulk_array
 */
add_filter( 'bulk_actions-edit-product', 'zi_sync_all_items_to_zoho' );
function zi_sync_all_items_to_zoho( $bulk_array ) {
	$bulk_array['sync_item_to_zoho'] = 'Sync to Zoho';
	return $bulk_array;
}

add_filter( 'handle_bulk_actions-edit-product', 'zi_sync_all_items_to_zoho_handler', 10, 3 );
function zi_sync_all_items_to_zoho_handler( $redirect, $action, $object_ids ) {
	// let's remove query args first
	$redirect = remove_query_arg( 'sync_item_to_zoho_done', $redirect );

	// do something for "Make Draft" bulk action
	if ( 'sync_item_to_zoho' === $action ) {

		foreach ( $object_ids as $post_id ) {
			$product_handler = new ProductClass();
			$product_handler->zi_product_sync( $post_id );
		}

		// do not forget to add query args to URL because we will show notices later
		$redirect = add_query_arg( 'sync_item_to_zoho_done', count( $object_ids ), $redirect );

	}

	return $redirect;
}

// output the message of bulk action
add_action( 'admin_notices', 'sync_item_to_zoho_notices' );
function sync_item_to_zoho_notices() {
	if ( ! empty( $_REQUEST['sync_item_to_zoho_done'] ) ) {
		echo '<div id="message" class="updated notice is-dismissible">
			<p>Products Synced. If product is not synced, please click on Edit Product to see the API response.</p>
		</div>';
	}
}

/**
 * Function to be called by ajax hook when unmap button called.
 * This function remove zoho mapped id.
 */
add_action( 'wp_ajax_zi_product_unmap_hook', 'zi_product_unmap_hook' );
function zi_product_unmap_hook( $product_id ) {
	if ( ! $product_id ) {
		$product_id = $_POST['product_id'];
	}

	if ( $product_id ) {
		$product = wc_get_product( $product_id );
		// If this is variable items then unmap all of it's variations.
		if ( $product->is_type( 'variable' ) ) {
			$variations = $product->get_available_variations();
			if ( isset( $variations ) && count( $variations ) > 0 ) {
				foreach ( $variations as $child ) {
					delete_post_meta( $child['variation_id'], 'zi_item_id' );
					delete_post_meta( $child['variation_id'], 'zi_account_id' );
					delete_post_meta( $child['variation_id'], 'zi_account_name' );
					delete_post_meta( $child['variation_id'], 'zi_category_id' );
					delete_post_meta( $child['variation_id'], 'zi_inventory_account_id' );
					delete_post_meta( $child['variation_id'], 'zi_purchase_account_id' );
				}
			}
		}
		delete_post_meta( $product_id, 'zi_item_id' );
		delete_post_meta( $product_id, 'zi_account_id' );
		delete_post_meta( $product_id, 'zi_account_name' );
		delete_post_meta( $product_id, 'zi_category_id' );
		delete_post_meta( $product_id, 'zi_inventory_account_id' );
		delete_post_meta( $product_id, 'zi_purchase_account_id' );
		// update message
		update_post_meta( $product_id, 'zi_product_errmsg', 'Product is Unmapped' );
	}
}

/**
 * Function to be called by ajax hook when unmap button called.
 * This function remove zoho mapped id.
 */
add_action( 'wp_ajax_zi_customer_unmap_hook', 'zi_customer_unmap_hook' );
function zi_customer_unmap_hook( $order_id ) {
	if ( ! $order_id ) {
		$order_id = $_POST['order_id'];
	}

	$order       = wc_get_order( $order_id );
	$customer_id = $order->get_user_id();

	if ( $customer_id ) {
		delete_user_meta( $customer_id, 'zi_contact_id' );
		delete_user_meta( $customer_id, 'zi_contact_persons_id' );
		delete_user_meta( $customer_id, 'zi_contactperson_id_0' );
		delete_user_meta( $customer_id, 'zi_contactperson_id_1' );
		delete_user_meta( $customer_id, 'zi_currency_code' );
		delete_user_meta( $customer_id, 'zi_currency_id' );
		delete_user_meta( $customer_id, 'zi_created_time' );
		delete_user_meta( $customer_id, 'zi_last_modified_time' );
		delete_user_meta( $customer_id, 'zi_primary_contact_id' );
		delete_user_meta( $customer_id, 'zi_billing_address_id' );
		delete_user_meta( $customer_id, 'zi_shipping_address_id' );

		$order->add_order_note( 'Zoho Sync: Customer is now unmapped. Please try syncing the order again' );
		$order->save();
	}
}

/**
 * Add WordPress Meta box to show sync response
 */
function zoho_product_metabox() {
	$zoho_inventory_access_token = get_option( 'zoho_inventory_access_token' );
	if ( ! $zoho_inventory_access_token ) {
		return;
	}
	add_meta_box(
		'zoho-product-sync',
		__( 'Zoho Inventory' ),
		'zoho_product_metabox_callback',
		'product',
		'side',
		'high'
	);
}
function zoho_product_metabox_callback( $post ) {
	$response = get_post_meta( $post->ID, 'zi_product_errmsg' );
	echo 'API Response: ' . esc_html( implode( $response ) ) . '<br>';
	// Generate nonce
	$nonce = wp_create_nonce( 'zoho_admin_product_sync' );
	$post_id = $post->ID;
	echo '<br><a href="javascript:void(0)" style="width:100%; text-align: center;" class="button button-primary" onclick="zoho_admin_product_ajax(' . esc_attr( $post_id ) . ', \'' . esc_attr( $nonce ) . '\')">Sync Product</a>';
	echo '<br><a href="javascript:void(0)" style="margin-top:10px; background:#b32d2e; border-color: #b32d2e; width:100%; text-align: center;" class="button button-primary" onclick="zoho_admin_unmap_product_ajax(' . esc_attr( $post_id ) . ')">Unmap this Product</a>';
	$product = wc_get_product( $post->ID );
	$product_type = $product->get_type();
	if ( 'variable' === $product_type || 'variable-subscription' === $product_type ) {
		echo '<p class="howto" style="color:#b32d2e;"><strong>Important : </strong> Please ensure all variations have price and SKU</p>';
	}
	// echo the zi_category_id
	$zi_category_id = get_post_meta( $post->ID, 'zi_category_id', true );
	if ( $zi_category_id ) {
		echo '<p class="howto"><strong>Zoho Category: </strong>' . esc_html( $zi_category_id ) . '</p>';
	}
}
add_action( 'add_meta_boxes', 'zoho_product_metabox' );


/**
 * Add Zoho and Exact Item IDs to Product and Variations
 * @return void
 */
add_action( 'woocommerce_product_options_pricing', 'cmbird_item_id_field' );
add_action( 'woocommerce_variation_options_pricing', 'cmbird_item_id_variation_field', 10, 3 );
function cmbird_item_id_field() {
	woocommerce_wp_text_input(
		array(
			'id' => 'cost_price',
			'wrapper_class' => 'form-row',
			'label' => 'Cost Price',
			'data_type' => 'price',
		)
	);
	woocommerce_wp_text_input(
		array(
			'id'          => 'eo_item_id',
			'label'       => __( 'Exact Item ID' ),
			'class'       => 'readonly',
			'desc_tip'    => true,
			'description' => __( 'This is the Exact Item ID of this product. You cannot change this' ),
		)
	);
	woocommerce_wp_text_input(
		array(
			'id' => 'zi_item_id',
			'label' => __( 'Zoho Item ID' ),
			'class' => 'readonly',
			'desc_tip' => true,
			'description' => __( 'This is the Zoho Item ID of this product. You cannot change this' ),
		)
	);
}
function cmbird_item_id_variation_field( $loop, $variation_data, $variation ) {
	woocommerce_wp_text_input(
		array(
			'id' => 'cost_price[' . $loop . ']',
			'wrapper_class' => 'form-row',
			'data_type' => 'price',
			'label' => __( 'Cost Price' ),
			'value' => get_post_meta( $variation->ID, 'cost_price', true ),
		)
	);
	woocommerce_wp_text_input(
		array(
			'id'          => 'eo_item_id[' . $loop . ']',
			'class'       => 'readonly',
			'label'       => __( 'Exact Item ID' ),
			'value'       => get_post_meta( $variation->ID, 'eo_item_id', true ),
			'desc_tip'    => true,
			'description' => __( 'This is the Exact Item ID of this product. You cannot change this' ),
		)
	);
	woocommerce_wp_text_input(
		array(
			'id' => 'zi_item_id[' . $loop . ']',
			'class' => 'readonly',
			'label' => __( 'Zoho Item ID' ),
			'value' => get_post_meta( $variation->ID, 'zi_item_id', true ),
			'desc_tip' => true,
			'description' => __( 'This is the Zoho Item ID of this product. You cannot change this' ),
		)
	);
}

add_action( 'save_post_product', 'cmbird_save_cost_price' );
function cmbird_save_cost_price( $product_id ) {
	global $typenow;
	if ( 'product' === $typenow ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;
		if ( isset( $_POST['cost_price'] ) ) {
			update_post_meta( $product_id, 'cost_price', $_POST['cost_price'] );
		}
	}
}
add_action( 'woocommerce_save_product_variation', 'cmbird_save_cost_price_variation', 10, 2 );
function cmbird_save_cost_price_variation( $variation_id, $loop ) {
	$text_field = ! empty( $_POST['cost_price'][ $loop ] ) ? $_POST['cost_price'][ $loop ] : '';
	update_post_meta( $variation_id, 'cost_price', sanitize_text_field( $text_field ) );
}

/**
 * Adds 'Zoho Sync' column header to 'Orders' page immediately after 'Total' column.
 *
 * @param string[] $columns
 * @return string[] $new_columns
 */
function zi_sync_column_orders_overview( $columns ) {

	$new_columns = array();

	foreach ( $columns as $column_name => $column_info ) {

		$new_columns[ $column_name ] = $column_info;

		if ( 'order_total' === $column_name ) {
			$new_columns['zoho_sync'] = __( 'Zoho Sync', 'my-textdomain' );
		}
	}

	return $new_columns;
}
add_filter( 'manage_woocommerce_page_wc-orders_columns', 'zi_sync_column_orders_overview', 20 );

/**
 * Adding Sync Status for Orders Column
 *
 * @param string $column Column name.
 * @param int    $order_id $order id.
 * @return void
 */
function zi_add_zoho_orders_content( $column, $order_id ) {
	$zi_url = get_option( 'zoho_inventory_url' );
	$zi_visit_url = str_replace( 'www.zohoapis', 'inventory.zoho', $zi_url );
	switch ( $column ) {
		case 'zoho_sync':
			// Get custom order meta data.
			$order       = wc_get_order( $order_id );
			$zi_order_id = $order->get_meta( 'zi_salesorder_id', true, 'edit' );
			$url         = $zi_visit_url . 'app#/salesorders/' . $zi_order_id;
			if ( $zi_order_id ) {
				echo '<span class="dashicons dashicons-yes-alt" style="color:green;"></span><a href="' . esc_url( $url ) . '" target="_blank"> <span class="dashicons dashicons-external" style="color:green;"></span> </a>';
			} else {
				echo '<span class="dashicons dashicons-dismiss" style="color:red;"></span>';
			}
			unset( $order );
			break;
	}
}
add_action( 'manage_woocommerce_page_wc-orders_custom_column', 'zi_add_zoho_orders_content', 20, 2 );

/**
 * Adds 'Zoho Sync' column content.
 *
 * @param string[] $column name of column being displayed
 */
function zi_add_zoho_column_content( $column ) {
	global $post;
	$post_type = get_post_type( $post );

	if ( 'zoho_sync' === $column && 'product' === $post_type ) {
		$product_id    = $post->ID;
		$zi_product_id = get_post_meta( $product_id, 'zi_item_id' );
		if ( $zi_product_id ) {
			echo '<span class="dashicons dashicons-yes-alt" style="color:green;"></span>';
		} else {
			echo '<span class="dashicons dashicons-dismiss" style="color:red;"></span>';
		}
	}
}
add_action( 'manage_product_posts_custom_column', 'zi_add_zoho_column_content' );

/**
 * Adds 'Zoho Sync' column header to 'Products' page.
 *
 * @param string[] $columns
 * @return string[] $new_columns
 */
function zi_sync_column_products_overview( $columns ) {

	$new_columns = array();

	foreach ( $columns as $column_name => $column_info ) {

		$new_columns[ $column_name ] = $column_info;

		if ( 'product_cat' === $column_name ) {
			$new_columns['zoho_sync'] = __( 'Zoho Sync', 'my-textdomain' );
		}
	}

	return $new_columns;
}
add_filter( 'manage_edit-product_columns', 'zi_sync_column_products_overview', 20 );

/**
 * Make 'Zoho Sync' column filterable.
 */
function zi_sync_column_filterable() {
	global $typenow;

	if ( 'product' === $typenow ) {
		// code here
		$value = isset( $_GET['zoho_sync_filter'] ) ? $_GET['zoho_sync_filter'] : '';

		echo '<select name="zoho_sync_filter">';
		echo '<option value="">Zoho Sync Filter</option>';

		// Count synced products
		$synced_count = new WP_Query(
			array(
				'post_type'  => 'product',
				'meta_query' => array(
					array(
						'key'     => 'zi_item_id',
						'compare' => 'EXISTS',
					),
				),
				'fields'     => 'ids',
			)
		);
		$synced_count = $synced_count->found_posts;
		$synced_label = 'Synced';
		if ( $synced_count > 0 ) {
			$synced_label .= ' (' . $synced_count . ')';
		}
		echo '<option value="synced" ' . selected( $value, 'synced', false ) . '>' . $synced_label . '</option>';

		// Count not synced products
		$not_synced_count = new WP_Query(
			array(
				'post_type'  => 'product',
				'meta_query' => array(
					array(
						'key'     => 'zi_item_id',
						'compare' => 'NOT EXISTS',
					),
				),
				'fields'     => 'ids',
			)
		);
		$not_synced_count = $not_synced_count->found_posts;
		$not_synced_label = 'Not Synced';
		if ( $not_synced_count > 0 ) {
			$not_synced_label .= ' (' . $not_synced_count . ')';
		}
		echo '<option value="not_synced" ' . selected( $value, 'not_synced', false ) . '>' . $not_synced_label . '</option>';

		echo '</select>';
	}
}
add_action( 'restrict_manage_posts', 'zi_sync_column_filterable' );

/**
 * Modify the product query based on the filter.
 *
 * @param WP_Query $query The query object.
 */
function zi_sync_column_filter_query( $query ) {
	global $typenow, $pagenow;

	if ( $typenow === 'product' && $pagenow === 'edit.php' && isset( $_GET['zoho_sync_filter'] ) && $_GET['zoho_sync_filter'] !== '' ) {
		$value = $_GET['zoho_sync_filter'];

		$meta_query = array();

		if ( $value === 'synced' ) {
			$meta_query[] = array(
				'key'     => 'zi_item_id',
				'compare' => 'EXISTS',
			);
		} elseif ( $value === 'not_synced' ) {
			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key'     => 'zi_item_id',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => 'zi_item_id',
					'value'   => '',
					'compare' => '=',
				),
			);
		}

		$query->set( 'meta_query', $meta_query );
	}
}
add_action( 'pre_get_posts', 'zi_sync_column_filter_query' );

/**
 * Change Action Scheduler default purge to 1 week
 * @return int
 */
function commercebird_action_scheduler_purge() {
	return WEEK_IN_SECONDS;
}
add_filter( 'action_scheduler_retention_period', 'commercebird_action_scheduler_purge' );

add_filter(
	'action_scheduler_default_cleaner_statuses',
	function ( $statuses ) {
		$statuses[] = ActionScheduler_Store::STATUS_FAILED;
		return $statuses;
	}
);
