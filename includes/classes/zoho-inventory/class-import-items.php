<?php
/**
 * Class to import Products from Zoho to WooCommerce
 *
 * @package  zoho_inventory_api
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Internal\ProductAttributesLookup\LookupDataStore;

class CMBIRD_Products_ZI {

	private $config;
	private $is_tax_enabled;

	public function __construct() {
		$this->config = array(
			'ProductZI' => array(
				'OID' => get_option( 'cmbird_zoho_inventory_oid' ),
				'APIURL' => get_option( 'cmbird_zoho_inventory_url' ),
			),
			'Settings' => array(
				'disable_description' => get_option( 'cmbird_zoho_disable_description_sync_status' ),
				'disable_name' => get_option( 'cmbird_zoho_disable_name_sync_status' ),
				'disable_price' => get_option( 'cmbird_zoho_disable_price_sync_status' ),
				'disable_stock' => get_option( 'cmbird_zoho_disable_stock_sync_status' ),
				'enable_accounting_stock' => get_option( 'cmbird_zoho_enable_accounting_stock_status' ),
				'enable_warehouse_stock' => get_option( 'cmbird_zoho_enable_warehousestock_status' ),
				'zoho_warehouse_id' => get_option( 'cmbird_zoho_warehouse_id_status' ),
				'disable_image' => get_option( 'cmbird_zoho_disable_image_sync_status' ),
			),
		);
		// Check if WooCommerce taxes are enabled and store the result
		$this->is_tax_enabled = 'yes' === get_option( 'woocommerce_calc_taxes' );
	}

	// Method to use the tax check across the class
	public function is_tax_enabled(): bool {
		return $this->is_tax_enabled;
	}

	/**
	 * Function to retrieve item details and sync items.
	 *
	 * @param string $url - URL to get details.
	 * @return mixed return true if data false if error.
	 */
	public function zi_item_bulk_sync( $url ) {
		// $fd = fopen( __DIR__ . '/zi_item_bulk_sync.txt', 'a+' );

		global $wpdb;
		$execute_curl_call = new CMBIRD_API_Handler_Zoho();
		$json = $execute_curl_call->execute_curl_call_get( $url );
		$code = $json->code;

		// $message = $json->message;
		// fwrite($fd, PHP_EOL . '$json->item : ' . print_r($json, true));

		if ( 0 === $code || '0' === $code ) {

			foreach ( $json->items as $arr ) {
				// fwrite( $fd, PHP_EOL . '$arr : ' . print_r( $arr, true ) );
				if ( ( ! empty( $arr->item_id ) ) && ! ( $arr->is_combo_product ) ) {
					// fwrite($fd, PHP_EOL . 'Item Id found : ' . $arr->item_id);

					$product_res = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key='zi_item_id' AND meta_value=%s", $arr->item_id ) );

					if ( $product_res && ! empty( $product_res->post_id ) ) {
						$pdt_id = $product_res->post_id;
						// Load the Product Object
						$product = wc_get_product( $pdt_id );
						if ( empty( $product ) || ! $product ) {
							continue;
						}

						$zi_disable_itemdescription_sync = $this->config['Settings']['disable_description'];
						if ( ! empty( $arr->description ) && ! $zi_disable_itemdescription_sync ) {
							$product->set_short_description( $arr->description );
						}

						if ( ! empty( $arr->status ) ) {
							$status = 'active' === $arr->status ? 'publish' : 'draft';
							$product->set_status( $status );
						}

						$zi_disable_itemname_sync = $this->config['Settings']['disable_name'];
						if ( ( ! $zi_disable_itemname_sync ) && ! empty( $arr->name ) ) {
							$product->set_name( stripslashes( $arr->name ) );
						}

						if ( ! empty( $arr->sku ) ) {
							$product->set_sku( $arr->sku );
						}

						$zi_disable_itemprice_sync = $this->config['Settings']['disable_price'];
						if ( ! empty( $arr->rate ) && ! $zi_disable_itemprice_sync ) {
							$product->set_regular_price( $arr->rate );
							$sale_price = $product->get_sale_price();
							if ( empty( $sale_price ) ) {
								$product->set_price( $arr->rate );
							}
						}

						if ( isset( $arr->package_details ) ) {
							$details = $arr->package_details;
							if ( is_object( $details ) ) {
								$product->set_weight( floatval( $details->weight ) );
								$product->set_length( floatval( $details->length ) );
								$product->set_width( floatval( $details->width ) );
								$product->set_height( floatval( $details->height ) );
							}
						}

						// Update Purchase Rate as Cost Price
						$product->update_meta_data( '_cost_price', $arr->purchase_rate );

						// To check status of stock sync option.
						$zi_disable_stock_sync = $this->config['Settings']['disable_stock'];
						if ( ! $zi_disable_stock_sync && isset( $arr->available_for_sale_stock ) ) {
							$stock = '';
							// Update stock
							$accounting_stock = $this->config['Settings']['enable_accounting_stock'];
							// Sync from specific warehouse check
							$zi_enable_warehousestock = $this->config['Settings']['enable_warehouse_stock'];
							if ( $zi_enable_warehousestock && isset( $arr->warehouses ) ) {
								$warehouses = $arr->warehouses;
								$warehouse_id = $this->config['Settings']['zoho_warehouse_id'];
								foreach ( $warehouses as $warehouse ) {
									if ( $warehouse->warehouse_id === $warehouse_id ) {
										if ( $accounting_stock ) {
											$stock = $warehouse->warehouse_available_for_sale_stock;
										} else {
											$stock = $warehouse->warehouse_actual_available_for_sale_stock;
										}
									}
								}
							} elseif ( $accounting_stock ) {
								$stock = $arr->available_for_sale_stock;
							} else {
								$stock = $arr->actual_available_for_sale_stock;
							}

							if ( is_numeric( $stock ) ) {
								$product->set_manage_stock( true );
								$product->set_stock_quantity( number_format( $stock, 0, '.', '' ) );
								if ( $stock > 0 ) {
									$product->set_stock_status( 'instock' );
								} else {
									$backorder_status = $product->backorders_allowed();
									$status = ( $backorder_status === 'yes' ) ? 'onbackorder' : 'outofstock';
									$product->set_stock_status( $status );
								}
							}
						}

						if ( ! empty( $arr->tax_id ) && ! $this->is_tax_enabled() ) {
							$zi_common_class = new CMBIRD_Common_Functions();
							$woo_tax_class = $zi_common_class->get_tax_class_by_percentage( $arr->tax_percentage );
							$product->set_tax_status( 'taxable' );
							$product->set_tax_class( $woo_tax_class );
						}
						$product->save();
						// Sync ACF Fields
						$this->sync_item_custom_fields( $arr->custom_fields, $pdt_id );
						// Clear/refresh cache
						wc_delete_product_transients( $pdt_id );

					}
				}
			}
		} else {
			return false;
		}
		// fclose( $fd );
		// Return if synced.
		return true;
	}

	/**
	 * Function to add items recursively by cron job.
	 *
	 * @param [number] $page  - Page number for getting item with pagination.
	 * @param [number] $category - Category id to get item of specific category.
	 * @param [string] $source - Source from where function is calling : 'cron'/'sync'.
	 * @return mixed
	 */
	public function sync_item_recursively() {
		// $fd = fopen( __DIR__ . '/simple-items-sync.txt', 'a+' );

		$args = func_get_args();
		// fwrite( $fd, PHP_EOL . 'Args ' . print_r( $args, true ) );
		if ( is_array( $args ) ) {
			if ( isset( $args['page'] ) && isset( $args['category'] ) ) {
				$page = $args['page'];
				$category = $args['category'];
			} elseif ( isset( $args[0] ) && isset( $args[1] ) ) {
				$page = $args[0];
				$category = $args[1];
			} elseif ( isset( $args[0] ) && ! isset( $args[1] ) ) {
				$page = $args[0]['page'];
				$category = $args[0]['category'];
			} else {
				return;
			}
		} else {
			return;
		}

		// Keep backup of current syncing page of particular category.
		update_option( 'cmbird_simple_item_sync_page_cat_id_' . $category, $page );

		$zoho_inventory_oid = $this->config['ProductZI']['OID'];
		$zoho_inventory_url = $this->config['ProductZI']['APIURL'];
		$urlitem = $zoho_inventory_url . 'inventory/v1/items?organization_id=' . $zoho_inventory_oid . '&category_id=' . $category . '&page=' . $page . '&per_page=100&sort_column=last_modified_time';
		// fwrite( $fd, PHP_EOL . 'URL : ' . $urlitem );

		$execute_curl_call = new CMBIRD_API_Handler_Zoho();
		$json = $execute_curl_call->execute_curl_call_get( $urlitem );
		$code = (int) property_exists( $json, 'code' ) ? $json->code : '0';

		global $wpdb;

		/* Response for item sync with sync button. For cron sync blank array will return. */
		$response_msg = array();
		if ( empty( $code ) && property_exists( $json, 'items' ) ) {
			$item_ids = array();
			// log items.
			// fwrite( $fd, PHP_EOL . 'Items : ' . print_r( $json, true ) );

			foreach ( $json->items as $arr ) {
				$prod_id = wc_get_product_id_by_sku( $arr->sku );
				$is_bundle = $arr->is_combo_product;
				if ( isset( $arr->group_id ) ) {
					$is_grouped = $arr->group_id;
				}
				// Flag to enable or disable sync.
				$allow_to_import = false;
				// Check if product exists with same sku.
				if ( $prod_id ) {
					$zi_item_id = get_post_meta( $prod_id, 'zi_item_id', true );
					if ( empty( $zi_item_id ) ) {
						// Map existing item with zoho id.
						update_post_meta( $prod_id, 'zi_item_id', $arr->item_id );
						$allow_to_import = true;
					}
				}
				if ( '' == $is_bundle && empty( $is_grouped ) ) {
					// If product not exists normal behavior of item sync.
					$allow_to_import = true;
				}
				if ( ! empty( $is_grouped ) ) {
					$this->sync_variation_of_group( $arr );
					continue;
				}

				// Get the post id by doing a meta query on the postmeta table.
				if ( empty( $prod_id ) ) {
					$pdt = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key = 'zi_item_id' AND meta_value = %s LIMIT 1", $arr->item_id ) );
					$pdt_id = $pdt ? $pdt->post_id : '';
				} else {
					$pdt_id = $prod_id;
				}

				if ( empty( $pdt_id ) && $allow_to_import && 'active' === $arr->status ) {
					// Create the product if it does not exist
					try {
						$product = new WC_Product();
						$product->set_name( $arr->name );
						$product->set_status( 'publish' );
						if ( isset( $arr->sku ) && ! empty( $arr->sku ) ) {
							$product->set_sku( $arr->sku );
						}
						$product->set_regular_price( $arr->rate );
						$pdt_id = $product->save();
						if ( $pdt_id ) {
							update_post_meta( $pdt_id, 'zi_item_id', $arr->item_id );
						}
					} catch (Exception $e) {
						// fwrite( $fd, PHP_EOL . 'Error : ' . $e->getMessage() );
						// throw new Exception( esc_html( $e->getMessage() ) );
						continue;
					}
				}

				if ( $pdt_id ) {
					if ( ! empty( $arr->category_name ) ) {
						$term = get_term_by( 'name', $arr->category_name, 'product_cat' );
						$term_id = $term->term_id;
						if ( empty( $term_id ) ) {
							$term = wp_insert_term(
								$arr->category_name,
								'product_cat',
								array(
									'parent' => 0,
								)
							);
							$term_id = $term['term_id'];
						}
						if ( $term_id ) {
							$existing_terms = wp_get_object_terms( $pdt_id, 'product_cat' );
							if ( $existing_terms && count( $existing_terms ) > 0 ) {
								$is_terms_exist = $this->zi_check_terms_exists( $existing_terms, $term_id );
								if ( ! $is_terms_exist ) {
									update_post_meta( $pdt_id, 'zi_category_id', $category );
									wp_add_object_terms( $pdt_id, $term_id, 'product_cat' );
								}
							} else {
								update_post_meta( $pdt_id, 'zi_category_id', $category );
								wp_set_object_terms( $pdt_id, $term_id, 'product_cat' );
							}
						}
						// Remove "uncategorized" category if assigned
						$uncategorized_term = get_term_by( 'slug', 'uncategorized', 'product_cat' );
						if ( $uncategorized_term && has_term( $uncategorized_term->term_id, 'product_cat', $pdt_id ) ) {
							wp_remove_object_terms( $pdt_id, $uncategorized_term->term_id, 'product_cat' );
						}
					}

					if ( ! empty( $arr->brand ) ) {
						// check if the Brand or Brands taxonomy exists and then update the term
						if ( taxonomy_exists( 'product_brand' ) ) {
							wp_set_object_terms( $pdt_id, $arr->brand, 'product_brand' );
						} elseif ( taxonomy_exists( 'product_brand' ) ) {
							wp_set_object_terms( $pdt_id, $arr->brand, 'product_brand' );
						}
					}
					// Sync Featured Image if not disabled.
					$zi_disable_image_sync = $this->config['Settings']['disable_image'];
					if ( ! empty( $arr->image_document_id ) && ! $zi_disable_image_sync ) {
						$image_class = new CMBIRD_Image_ZI();
						$image_class->cmbird_zi_get_image( $arr->item_id, $arr->name, $pdt_id, $arr->image_name );
					}

					$item_ids[] = $arr->item_id;
				} // end of wpdb post_id check
			}
			if ( ! empty( $item_ids ) ) {
				$item_id_str = implode( ',', $item_ids );
				// fwrite($fd, PHP_EOL . 'Before Bulk sync');
				$item_details_url = "{$zoho_inventory_url}inventory/v1/itemdetails?item_ids={$item_id_str}&organization_id={$zoho_inventory_oid}";
				$this->zi_item_bulk_sync( $item_details_url );

				if ( isset( $json->page_context ) && $json->page_context->has_more_page ) {
					$data = array(
						'page' => $page + 1,
						'category' => $category,
					);
					// fwrite( $fd, PHP_EOL . 'Data: ' . print_r( $data, true ) );
					$existing_schedule = as_has_scheduled_action( 'import_simple_items_cron', array( $data ) );
					if ( ! $existing_schedule ) {
						as_schedule_single_action( time(), 'import_simple_items_cron', array( $data ) );
						// fwrite( $fd, PHP_EOL . 'Scheduled' );
					}
				} else {
					// If there is no more page to sync last backup page will be starting from 1.
					// This we have used because in shared hosting only 1000 records are syncing.
					update_option( 'cmbird_simple_item_sync_page_cat_id_' . $category, 1 );
				}
				array_push( $response_msg, $this->zi_response_message( $code, $json->message ) );
			}
		}
		// fclose( $fd );
		return $response_msg;
	}

	/**
	 * Update or Create Custom Fields of Product
	 *
	 * @param array|object $custom_fields - item object coming in from simple item recursive
	 * @param int $pdt_id - product id
	 * @return void
	 */
	public function sync_item_custom_fields( $custom_fields, $pdt_id ) {
		if ( empty( $custom_fields ) || empty( $pdt_id ) ) {
			return;
		}

		foreach ( $custom_fields as $custom_field ) {
			// Extract data from custom field
			$api_name = isset( $custom_field->api_name ) ? $custom_field->api_name : $custom_field['api_name'];
			$value = isset( $custom_field->value ) ? $custom_field->value : $custom_field['value'];

			// Check if both API name and value are present
			if ( ! empty( $api_name ) && ! empty( $value ) ) {
				// Check if ACF function exists
				if ( function_exists( 'update_field' ) ) {
					// Update ACF field
					update_field( $api_name, $value, $pdt_id );
				} else {
					// Fall back to update post meta
					update_post_meta( $pdt_id, $api_name, $value );
				}
			}
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
	public function sync_groupitem_recursively() {
		// $fd = fopen( __DIR__ . '/sync_groupitem_recursively.txt', 'a+' );

		$args = func_get_args();
		if ( ! empty( $args ) ) {
			if ( is_array( $args ) ) {
				if ( isset( $args['page'] ) && isset( $args['category'] ) ) {
					$page = $args['page'];
					$category = $args['category'];
				} elseif ( isset( $args[0] ) && isset( $args[1] ) ) {
					$page = $args[0];
					$category = $args[1];
				} elseif ( isset( $args[0] ) && ! isset( $args[1] ) ) {
					$page = $args[0]['page'];
					$category = $args[0]['category'];
				} else {
					return;
				}
			} else {
				return;
			}

			// Keep backup of current syncing page of particular category.
			update_option( 'cmbird_group_item_sync_page_cat_id_' . $category, $page );

			// fwrite($fd, PHP_EOL . 'Test name Update ' . print_r($data, true));
			global $wpdb;
			$zoho_inventory_oid = $this->config['ProductZI']['OID'];
			$zoho_inventory_url = $this->config['ProductZI']['APIURL'];

			$url = $zoho_inventory_url . 'inventory/v1/itemgroups/?organization_id=' . $zoho_inventory_oid . '&category_id=' . $category . '&page=' . $page . '&per_page=20&filter_by=Status.Active';
			// fwrite($fd, PHP_EOL . '$url : ' . $url);

			$execute_curl_call = new CMBIRD_API_Handler_Zoho();
			$json = $execute_curl_call->execute_curl_call_get( $url );

			$code = $json->code;
			// $message = $json->message;

			$response_msg = array();

			if ( $code === '0' || $code === 0 ) {
				$zi_disable_description_sync = $this->config['Settings']['disable_description'];
				$zi_disable_name_sync = $this->config['Settings']['disable_name'];
				// fwrite( $fd, PHP_EOL . '$json->itemgroups : ' . print_r( $json->itemgroups, true ) );
				foreach ( $json->itemgroups as $gp_arr ) {
					$zi_group_id = $gp_arr->group_id;
					$zi_group_name = $gp_arr->group_name;
					// fwrite($fd, PHP_EOL . '$itemGroup : ' . print_r($gp_arr, true));
					// skip if there is no first attribute
					$zi_group_attribute1 = $gp_arr->attribute_id1;
					if ( empty( $zi_group_attribute1 ) ) {
						continue;
					}

					// Get Group ID
					$group_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'zi_item_id' AND meta_value = %s LIMIT 1", $zi_group_id ) );
					// array_push( $response_msg, $this->zi_response_message( 'SUCCESS', 'Zoho Group Item Synced: ' . $zi_group_name, $group_id ) );
					/// end insert group product
					// variable items
					// fwrite($fd, PHP_EOL . '$group_id exists ' . $group_id);
					if ( ! empty( $group_id ) ) {
						$existing_parent_product = wc_get_product( $group_id );
						// fwrite($fd, PHP_EOL . 'Existing group Id');
						if ( ! empty( $gp_arr->description ) && ! $zi_disable_description_sync ) {
							$existing_parent_product->set_short_description( $gp_arr->description );
						}
						if ( ! empty( $gp_arr->name ) && ! $zi_disable_name_sync ) {
							$existing_parent_product->set_name( $gp_arr->name );
							// santize the name for slug and save the slug
							$slug = sanitize_title( $gp_arr->name );
							$existing_parent_product->set_slug( $slug );
						}
						// add zi_category_id as meta
						$existing_parent_product->update_meta_data( 'zi_category_id', $category );
						// create attributes if not exists.
						$attributes = $existing_parent_product->get_attributes();
						if ( empty( $attributes ) ) {
							// Create or Update the Attributes
							$attr_created = $this->sync_attributes_of_group( $gp_arr, $group_id );
							// fwrite($fd, PHP_EOL . '$attr_created ' . $attr_created);
						}
						$variations_check = $existing_parent_product->get_children();
						if ( empty( $variations_check ) ) {
							// fwrite( $fd, PHP_EOL . 'No Variations found' );
							$this->import_variable_product_variations( $gp_arr, $group_id );
						}
						$existing_parent_product->save();
						// ACF Fields
						if ( ! empty( $gp_arr->custom_fields ) ) {
							// fwrite($fd, PHP_EOL . 'Custom Fields : ' . print_r($gp_arr->custom_fields, true));
							$this->sync_item_custom_fields( $gp_arr->custom_fields, $group_id );
						}
						// update Brand.
						if ( ! empty( $gp_arr->brand ) ) {
							// check if the Brand or Brands taxonomy exists and then update the term
							if ( taxonomy_exists( 'product_brand' ) ) {
								wp_set_object_terms( $group_id, $gp_arr->brand, 'product_brand' );
							} elseif ( taxonomy_exists( 'product_brand' ) ) {
								wp_set_object_terms( $group_id, $gp_arr->brand, 'product_brand' );
							}
						}
					} else {
						// Create the parent variable product
						$parent_product = new WC_Product_Variable();
						$parent_product->set_name( $zi_group_name );
						$parent_product->set_status( 'publish' );
						$parent_product->set_short_description( $gp_arr->description );
						$parent_product->add_meta_data( 'zi_item_id', $zi_group_id );
						$parent_product->add_meta_data( 'zi_category_id', $category );
						$group_id = $parent_product->save();

						// Sync category by finding it first
						$category_handler = new CMBIRD_Categories_ZI();
						$term_id = $category_handler->cmbird_subcategories_term_id( $category );
						$term = get_term_by( 'id', $term_id, 'product_cat' );
						if ( $term && ! is_wp_error( $term ) ) {
							// Assign the category to the product
							wp_set_object_terms( $group_id, $term->term_id, 'product_cat' );

							// Remove the "uncategorized" category if it's assigned
							$uncategorized_term = get_term_by( 'slug', 'uncategorized', 'product_cat' );
							if ( $uncategorized_term && has_term( $uncategorized_term->term_id, 'product_cat', $group_id ) ) {
								wp_remove_object_terms( $group_id, $uncategorized_term->term_id, 'product_cat' );
							}
						}

						// update Brand.
						if ( ! empty( $gp_arr->brand ) ) {
							// check if the Brand or Brands taxonomy exists and then update the term
							if ( taxonomy_exists( 'product_brand' ) ) {
								wp_set_object_terms( $group_id, $gp_arr->brand, 'product_brand' );
							} elseif ( taxonomy_exists( 'product_brand' ) ) {
								wp_set_object_terms( $group_id, $gp_arr->brand, 'product_brand' );
							}
						}

						// fwrite($fd, PHP_EOL . 'New $group_id ' . $group_id);
						// ACF Fields
						if ( ! empty( $gp_arr->custom_fields ) ) {
							// fwrite($fd, PHP_EOL . 'Custom Fields : ' . print_r($gp_arr->custom_fields, true));
							$this->sync_item_custom_fields( $gp_arr->custom_fields, $group_id );
						}
						// Create or Update the Attributes
						$attr_created = $this->sync_attributes_of_group( $gp_arr, $group_id );

						if ( ! empty( $group_id ) && $attr_created ) {
							$this->import_variable_product_variations( $gp_arr, $group_id );
						}
					} // end of create variable product
				} // end foreach group items

				if ( isset( $json->page_context ) && $json->page_context->has_more_page ) {
					$data = array(
						'page' => $page + 1,
						'category' => $category,
					);
					$existing_schedule = as_has_scheduled_action( 'import_group_items_cron', $data );
					// Check if the scheduled action exists
					if ( ! $existing_schedule ) {
						as_schedule_single_action( time(), 'import_group_items_cron', $data );
					}
				} else {
					// If there is no more page to sync last backup page will be starting from 1.
					// This we have used because in shared hosting only 1000 records are syncing.
					update_option( 'cmbird_group_item_sync_page_cat_id_' . $category, 1 );
				}
				array_push( $response_msg, $this->zi_response_message( $code, $json->message ) );
			}
			// End of logging.
			// fclose( $fd );
			return $response_msg;
		} else {
			return;
		}
	}

	/**
	 * Callback function for importing a variable product and its variations.
	 *
	 * @param object $gp_arr - Group item object.
	 * @param int $group_id - Group item id.
	 */
	public function import_variable_product_variations( $gp_arr, $group_id ): void {
		// $fd = fopen( __DIR__ . '/import_variable_product_variations.txt', 'a+' );

		if ( empty( $gp_arr ) || empty( $group_id ) ) {
			return;
		}

		global $wpdb;
		$product = wc_get_product( $group_id );

		if ( ! is_wp_error( $product ) ) {

			$item_group = $gp_arr;
			// fwrite( $fd, PHP_EOL . 'Item Group : ' . print_r( $item_group, true ) );
			$items = $item_group->items;
			$attribute_name1 = $item_group->attribute_name1;
			$attribute_name2 = $item_group->attribute_name2;
			$attribute_name3 = $item_group->attribute_name3;

			// fwrite( $fd, PHP_EOL . 'Items : ' . print_r( $items, true ) );
			// get the options for stock sync
			$zi_enable_warehousestock = $this->config['Settings']['enable_warehouse_stock'];
			$warehouse_id = $this->config['Settings']['zoho_warehouse_id'];
			$accounting_stock = $this->config['Settings']['enable_accounting_stock'];
			$zi_disable_stock_sync = $this->config['Settings']['disable_stock'];

			foreach ( $items as $item ) {
				// reset this array
				$attribute_arr = array();
				$variation_id = '';
				$status = $item->status === 'active' ? 'publish' : 'draft';

				$zi_item_id = $item->item_id;
				$variation_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'zi_item_id' AND meta_value = %s LIMIT 1", $zi_item_id ) );

				if ( ! empty( $variation_id ) ) {
					$v_product = wc_get_product( $variation_id );
					// Check if the product object is valid
					if ( $v_product && is_a( $v_product, 'WC_Product' ) ) {
						if ( $v_product->is_type( 'simple' ) ) {
							wp_delete_post( $variation_id, true );
						}
					}
				}
				// SKU check of the variation, if exits then remove it
				if ( ! empty( $item->sku ) ) {
					$sku_prod_id = wc_get_product_id_by_sku( $item->sku );
					$v_product = wc_get_product( $sku_prod_id );
					// Check if the product object is valid
					if ( $v_product && is_a( $v_product, 'WC_Product' ) ) {
						if ( $v_product->is_type( 'simple' ) ) {
							wp_delete_post( $variation_id, true );
						}
					}
				}
				if ( ! empty( $variation_id ) ) {
					continue;
				}
				// Stock mode check
				$warehouses = $item->warehouses;

				if ( $zi_enable_warehousestock && $warehouse_id ) {
					foreach ( $warehouses as $warehouse ) {
						if ( $warehouse->warehouse_id === $warehouse_id ) {
							if ( $accounting_stock ) {
								$stock = isset( $warehouse->warehouse_available_stock );
							} else {
								$stock = isset( $warehouse->warehouse_actual_available_stock );
							}
						}
					}
				} elseif ( $accounting_stock ) {
					$stock = $item->available_stock;
				} else {
					$stock = $item->actual_available_stock;
				}

				$attribute_name11 = $item->attribute_option_name1;
				$attribute_name12 = $item->attribute_option_name2;
				$attribute_name13 = $item->attribute_option_name3;
				// Prepare the variation data
				if ( ! empty( $attribute_name1 ) ) {
					$sanitized_name1 = wc_sanitize_taxonomy_name( $attribute_name1 );
					$attribute_arr[ $sanitized_name1 ] = $attribute_name11;
				}
				if ( ! empty( $attribute_name2 ) ) {
					$sanitized_name2 = wc_sanitize_taxonomy_name( $attribute_name2 );
					$attribute_arr[ $sanitized_name2 ] = $attribute_name12;
				}
				if ( ! empty( $attribute_name3 ) ) {
					$sanitized_name3 = wc_sanitize_taxonomy_name( $attribute_name3 );
					$attribute_arr[ $sanitized_name3 ] = $attribute_name13;
				}
				// fwrite( $fd, PHP_EOL . '$attribute_arr : ' . print_r( $attribute_arr, true ) );

				// fwrite($fd, PHP_EOL . '$variation_attributes : ' . print_r($variation_attributes, true));
				// Loop through the variations and create them
				try {
					$variation_post = array(
						'post_title' => $product->get_name(),
						'post_name' => 'product-' . $group_id . '-variation',
						'post_status' => $status,
						'post_parent' => $group_id,
						'post_type' => 'product_variation',
						'guid' => $product->get_permalink(),
					);
					// Creating the product variation
					$variation_id = wp_insert_post( $variation_post );
					if ( is_wp_error( $variation_id ) ) {
						continue;
					}
					$variation = new WC_Product_Variation( $variation_id );
					$variation->set_regular_price( $item->rate );
					$variation->set_sku( $item->sku );
					if ( ! $zi_disable_stock_sync && $stock > 0 ) {
						$variation->set_stock_quantity( $stock );
						$variation->set_manage_stock( true );
						$variation->set_stock_status( '' );
					} else {
						$variation->set_manage_stock( false );
					}
					$variation->add_meta_data( 'zi_item_id', $item->item_id );
					$variation_id = $variation->save();
				} catch (Exception $e) {
					// fwrite( $fd, PHP_EOL . 'Error : ' . $e->getMessage() );
					continue;
				}

				// Get the variation attributes with correct attribute values
				foreach ( $attribute_arr as $attribute => $term_name ) {
					$taxonomy = 'pa_' . $attribute;
					// If taxonomy doesn't exists we create it
					if ( ! taxonomy_exists( $taxonomy ) ) {
						register_taxonomy(
							$taxonomy,
							'product_variation',
							array(
								'hierarchical' => false,
								'label' => ucfirst( $attribute ),
								'query_var' => true,
								'rewrite' => array( 'slug' => sanitize_title( $attribute ) ),
							),
						);
					}

					// Check if the Term name exist and if not we create it.
					if ( ! term_exists( $term_name, $taxonomy ) ) {
						wp_insert_term( $term_name, $taxonomy );
					}

					$term_slug = get_term_by( 'name', $term_name, $taxonomy )->slug;
					// Get the post Terms names from the parent variable product.
					$post_term_names = wp_get_post_terms( $group_id, $taxonomy, array( 'fields' => 'names' ) );
					// Check if the post term exist and if not we set it in the parent variable product.
					if ( ! in_array( $term_name, $post_term_names, true ) ) {
						wp_set_post_terms( $group_id, $term_name, $taxonomy, true );
					}
					// Set/save the attribute data in the product variation
					update_post_meta( $variation_id, 'attribute_' . $taxonomy, $term_slug );
				}

				// update purchase price as meta data
				if ( ! empty( $item->purchase_rate ) ) {
					update_post_meta( $variation_id, '_cost_price', $item->purchase_rate );
				}

				// Featured Image of variation
				if ( ! empty( $item->image_name ) ) {
					$image_class = new CMBIRD_Image_ZI();
					$variation_image_id = $image_class->cmbird_zi_get_image( $item->item_id, $item->name, $variation_id, $item->image_name );
					if ( ! has_post_thumbnail( $group_id ) ) {
						if ( $variation_image_id ) {
							set_post_thumbnail( $group_id, $variation_image_id );
						}
					}
				}
			}
			// End group item add process
			// array_push($response_msg, $this->zi_response_message('SUCCESS', 'Zoho variable item created for zoho item id ' . $zi_item_id, $variation_id));

			if ( $product && is_a( $product, 'WC_Product_Variable' ) ) {
				// Sort the variations
				$data_store = $product->get_data_store();
				$data_store->sort_all_product_variations( $group_id );
			}
			// End of Logging
			// fclose( $fd );
		}
	}

	/**
	 * Update or Create the Product Attributes for the Variable Item Sync
	 *
	 * @param: $group_id - the parent product id in WooCommerce
	 * @return: bool - true if attributes were created successfully, false otherwise
	 */
	public function sync_attributes_of_group( $gp_arr, $group_id ) {
		// $fd = fopen(__DIR__ . '/sync_attributes_of_group.txt', 'a+');
		// Check if the group item has attributes
		if ( empty( $gp_arr->attribute_name1 ) ) {
			return false;
		}
		// Create attributes
		$success = true; // Track the success of attribute creation
		$attributes_data = array();
		$attribute_count = 0;
		$attribute_options_map = array(); // Track unique attribute options

		// Loop through the attribute names
		for ( $i = 1; $i <= 3; $i++ ) {
			$attribute_name_key = 'attribute_name' . $i;
			$attribute_option_name_key = 'attribute_option_name' . $i;

			// Get the attribute name
			$attribute_name = $gp_arr->$attribute_name_key;

			if ( ! empty( $attribute_name ) ) {
				// Check if the attribute is already added to the attributes array
				if ( ! isset( $attributes_data[ $attribute_name ] ) ) {
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
					foreach ( $gp_arr->items as $item ) {
						$attribute_option = $item->$attribute_option_name_key;
						if ( ! empty( $attribute_option ) && ! in_array( $attribute_option, $attribute_options_map ) ) {
							$attribute_options[] = $attribute_option;
							$attribute_options_map[] = $attribute_option;
						}
					}

					// Set the attribute options
					$attribute['options'] = $attribute_options;

					$attributes_data[] = $attribute;
					++$attribute_count;
				}
			}
		}
		// fwrite($fd, PHP_EOL . '$attributes : ' . print_r($attributes_data, true));

		// Assign the attributes to the parent product
		if ( count( $attributes_data ) > 0 ) {
			$attributes = array(); // Initializing

			// Loop through defined attribute data
			foreach ( $attributes_data as $key => $attribute_array ) {
				if ( isset( $attribute_array['name'] ) && isset( $attribute_array['options'] ) ) {
					// Clean attribute name to get the taxonomy
					$taxonomy = 'pa_' . wc_sanitize_taxonomy_name( $attribute_array['name'] );

					$option_term_ids = array(); // Initializing

					// Create the attribute if it doesn't exist
					if ( ! taxonomy_exists( $taxonomy ) ) {
						// Clean attribute label for better display
						$attribute_label = ucfirst( $attribute_array['name'] );

						// Register the new attribute taxonomy
						$attribute_args = array(
							'slug' => $taxonomy,
							'name' => $attribute_label,
							'type' => 'select',
							'order_by' => 'menu_order',
							'has_archives' => false,
						);

						$result = wc_create_attribute( $attribute_args );
						register_taxonomy( $taxonomy, array( 'product' ), array() );

						if ( ! is_wp_error( $result ) ) {
							// fwrite($fd, PHP_EOL . 'result : ' . $result);
							// Loop through defined attribute data options (terms values)
							foreach ( $attribute_array['options'] as $option ) {
								// Check if the term exists for the attribute taxonomy
								$term = term_exists( $result, $taxonomy );
								if ( empty( $term ) ) {
									// Term doesn't exist, create a new one
									$term_id = wp_insert_term( $option, $taxonomy );

									if ( ! is_wp_error( $term_id ) ) {
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
							$attributes[ $taxonomy ] = array(
								'name' => $taxonomy,
								'value' => $option_term_ids, // Need to be term IDs
								'position' => $key + 1,
								'is_visible' => $attribute_array['visible'],
								'is_variation' => $attribute_array['variation'],
								'is_taxonomy' => '1',
							);

							// Get the existing terms for the taxonomy
							$existing_terms = get_terms(
								array(
									'taxonomy' => $taxonomy,
									'hide_empty' => false,
								)
							);

							// Loop through existing terms and assign them to the product
							foreach ( $existing_terms as $existing_term ) {
								$existing_term_ids[] = $existing_term->term_id;
							}

							// Set the selected terms for the product
							wp_set_object_terms( $group_id, $existing_term_ids, $taxonomy );
						} else {
							$success = false;
						}
					} else {
						// Add existing attribute with its selected terms to the product attributes array
						$existing_terms = get_terms(
							array(
								'taxonomy' => $taxonomy,
								'hide_empty' => false,
							)
						);

						if ( $existing_terms ) {
							$existing_term_ids = array();
							foreach ( $attribute_array['options'] as $option ) {
								$match_found = false;
								foreach ( $existing_terms as $existing_term ) {
									if ( $existing_term->name === $option ) {
										$existing_term_ids[] = $existing_term->term_id;
										$match_found = true;
										break;
									}
								}
								if ( ! $match_found ) {
									// Check if the term exists for the attribute taxonomy
									$term = term_exists( $option, $taxonomy );

									if ( empty( $term ) ) {
										// Term doesn't exist, create a new one
										$term = wp_insert_term( $option, $taxonomy );

										if ( ! is_wp_error( $term ) ) {
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

							if ( ! empty( $existing_term_ids ) ) {
								$attributes[ $taxonomy ] = array(
									'name' => $taxonomy,
									'value' => $existing_term_ids,
									'position' => $key + 1,
									'is_visible' => $attribute_array['visible'],
									'is_variation' => $attribute_array['variation'],
									'is_taxonomy' => '1',
								);

								// Set the selected terms for the product
								wp_set_object_terms( $group_id, $existing_term_ids, $taxonomy, false );
							}
						} else {
							$option_term_ids = array(); // Initializing

							// Loop through defined attribute data options (terms values)
							foreach ( $attribute_array['options'] as $option ) {
								// Check if the term exists for the attribute taxonomy
								$term = term_exists( $option, $taxonomy );

								if ( empty( $term ) ) {
									// Term doesn't exist, create a new one
									$term = wp_insert_term( $option, $taxonomy );

									if ( ! is_wp_error( $term ) ) {
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
							wp_set_object_terms( $group_id, $option_term_ids, $taxonomy, false );

							// Add the new attribute to the product attributes array
							$attributes[ $taxonomy ] = array(
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
			update_post_meta( $group_id, '_product_attributes', $attributes );
			$lookup_data_store = new LookupDataStore();
			$lookup_data_store->create_data_for_product( $group_id );
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
	public function sync_variation_of_group( $item ) {
		// $fd = fopen( __DIR__ . '/sync_variation_of_group.txt', 'a+' );
		global $wpdb;
		// Stock mode check
		$zi_disable_stock_sync = $this->config['Settings']['disable_stock'];
		$accounting_stock = $this->config['Settings']['enable_accounting_stock'];
		if ( $accounting_stock ) {
			$stock = $item->available_stock;
		} else {
			$stock = $item->actual_available_stock;
		}
		$item_id = $item->item_id;
		// $item_category = $item->category_name;
		$groupid = property_exists( $item, 'group_id' ) ? $item->group_id : 0;
		// find parent variable product
		$group_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'zi_item_id' AND meta_value = %s LIMIT 1", $groupid ) );
		// fwrite($fd, PHP_EOL . 'Row Data : ' . print_r($row, true));
		// fwrite($fd, PHP_EOL . 'Row $group_id : ' . $group_id);
		$stock_quantity = $stock < 0 ? 0 : $stock;
		// fwrite($fd, PHP_EOL . 'Before group item sync : ' . $group_id);
		if ( ! empty( $group_id ) ) {
			// fwrite($fd, PHP_EOL . 'Inside item sync : ' . $item->name);
			// Brand
			if ( isset( $item->brand ) && ! empty( $group_id ) ) {
				if ( taxonomy_exists( 'product_brand' ) ) {
					wp_set_object_terms( $group_id, $item->brand, 'product_brand' );
				}
			}

			$variation_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'zi_item_id' AND meta_value = %s LIMIT 1", $item_id ) );
			if ( ! empty( $item->sku ) && empty( $variation_id ) ) {
				$variation_id = wc_get_product_id_by_sku( $item->sku );
			}
			if ( $variation_id ) {
				$variation = new WC_Product_Variation( $variation_id );
				// SKU - Imported
				if ( ! empty( $item->sku ) ) {
					$variation->set_sku( $item->sku );
				}
				// update purchase price as meta data
				if ( ! empty( $item->purchase_rate ) ) {
					update_post_meta( $variation_id, '_cost_price', $item->purchase_rate );
				}
				// Price - Imported
				$zi_disable_price_sync = $this->config['Settings']['disable_price'];
				$variation_sale_price = $variation->get_sale_price();
				if ( empty( $variation_sale_price ) && ! $zi_disable_price_sync ) {
					$variation->set_sale_price( $item->rate );
				}
				$variation->set_regular_price( $item->rate );
				// Set Tax Class
				if ( $item->tax_id && ! $this->is_tax_enabled() ) {
					$zi_common_class = new CMBIRD_Common_Functions();
					$woo_tax_class = $zi_common_class->get_tax_class_by_percentage( $item->tax_percentage );
					$variation->set_tax_status( 'taxable' );
					$variation->set_tax_class( $woo_tax_class );
				}
				// Stock Imported code
				if ( ! $zi_disable_stock_sync && is_numeric( $stock_quantity ) ) {
					$variation->set_manage_stock( true );
					if ( $stock_quantity > 0 ) {
						$variation->set_manage_stock( true );
						$variation->set_stock_quantity( $stock_quantity );
						$variation->set_stock_status( 'instock' );
					} elseif ( $stock_quantity <= 0 ) {
						$variation->set_manage_stock( true );
						$variation->set_stock_quantity( $stock_quantity );
						$stock_status = $variation->backorders_allowed() ? 'onbackorder' : 'outofstock';
						$variation->set_stock_status( $stock_status );
					}
				}
				// Featured Image of variation
				if ( ! empty( $item->image_document_id ) ) {
					$image_class = new CMBIRD_Image_ZI();
					$variation_image_id = $image_class->cmbird_zi_get_image( $item->item_id, $item->name, $variation_id, $item->image_name );
					if ( ! has_post_thumbnail( $group_id ) ) {
						if ( $variation_image_id ) {
							set_post_thumbnail( $group_id, $variation_image_id );
						}
					}
				}
				// enable or disable based on status from Zoho
				$status = ( 'active' === $item->status ) ? 'publish' : 'draft';
				$variation->set_status( $status );
				$variation->save();
				// clear cache
				wc_delete_product_transients( $variation_id );
			} else {
				// create new variation
				// if status is not active then return
				if ( 'active' !== $item->status ) {
					return;
				}

				$attribute_name1 = $item->attribute_option_name1;
				$attribute_name2 = $item->attribute_option_name2;
				$attribute_name3 = $item->attribute_option_name3;
				// Prepare the variation data
				$attribute_arr = array();
				if ( ! empty( $attribute_name1 ) ) {
					$sanitized_name1 = wc_sanitize_taxonomy_name( $item->attribute_name1 );
					$attribute_arr[ $sanitized_name1 ] = $attribute_name1;
				}
				if ( ! empty( $attribute_name2 ) ) {
					$sanitized_name2 = wc_sanitize_taxonomy_name( $item->attribute_name2 );
					$attribute_arr[ $sanitized_name2 ] = $attribute_name2;
				}
				if ( ! empty( $attribute_name3 ) ) {
					$sanitized_name3 = wc_sanitize_taxonomy_name( $item->attribute_name3 );
					$attribute_arr[ $sanitized_name3 ] = $attribute_name3;
				}
				// fwrite( $fd, PHP_EOL . 'Attributes_arr: ' . print_r( $attribute_arr, true ) );

				// here actually create new variation because sku not found
				$variation = new WC_Product_Variation();
				$variation->set_parent_id( $group_id );
				$variation->set_status( 'publish' );
				$variation->set_regular_price( $item->rate );
				$variation->set_sku( $item->sku );
				if ( ! $zi_disable_stock_sync ) {
					$variation->set_stock_quantity( $stock );
					$variation->set_manage_stock( true );
					$variation->set_stock_status( '' );
				} else {
					$variation->set_manage_stock( false );
				}
				$variation->add_meta_data( 'zi_item_id', $item->item_id );
				$variation_id = $variation->save();

				// Get the variation attributes with correct attribute values
				foreach ( $attribute_arr as $attribute => $term_name ) {
					$taxonomy = $attribute;
					// If taxonomy doesn't exists we create it
					if ( ! taxonomy_exists( $taxonomy ) ) {
						register_taxonomy(
							$taxonomy,
							'product_variation',
							array(
								'hierarchical' => false,
								'label' => ucfirst( $attribute ),
								'query_var' => true,
								'rewrite' => array( 'slug' => sanitize_title( $attribute ) ),
							),
						);
					}

					// Check if the Term name exist and if not we create it.
					if ( ! term_exists( $term_name, $taxonomy ) ) {
						wp_insert_term( $term_name, $taxonomy );
					}

					$term_slug = get_term_by( 'name', $term_name, $taxonomy )->slug;
					// Get the post Terms names from the parent variable product.
					$post_term_names = wp_get_post_terms( $group_id, $taxonomy, array( 'fields' => 'names' ) );
					// Check if the post term exist and if not we set it in the parent variable product.
					if ( ! in_array( $term_name, $post_term_names, true ) ) {
						wp_set_post_terms( $group_id, $term_name, $taxonomy, true );
					}
					// Set/save the attribute data in the product variation
					update_post_meta( $variation_id, 'attribute_' . $taxonomy, $term_slug );
				}

				// update purchase price as meta data
				if ( ! empty( $item->purchase_rate ) ) {
					update_post_meta( $variation_id, '_cost_price', $item->purchase_rate );
				}
				// Stock
				if ( ! empty( $stock ) && ! $zi_disable_stock_sync ) {
					update_post_meta( $variation_id, 'manage_stock', true );
					if ( $stock > 0 ) {
						update_post_meta( $variation_id, '_stock', $stock );
						update_post_meta( $variation_id, '_stock_status', 'instock' );
					} else {
						$backorder_status = get_post_meta( $group_id, '_backorders', true );
						update_post_meta( $variation_id, '_stock', $stock );
						if ( $backorder_status === 'yes' ) {
							update_post_meta( $variation_id, '_stock_status', 'onbackorder' );
						} else {
							update_post_meta( $variation_id, '_stock_status', 'outofstock' );
						}
					}
				}
				// Featured Image of variation
				if ( ! empty( $item->image_document_id ) ) {
					$image_class = new CMBIRD_Image_ZI();
					$variation_image_id = $image_class->cmbird_zi_get_image( $item->item_id, $item->name, $variation_id, $item->image_name );
					if ( ! has_post_thumbnail( $group_id ) ) {
						if ( $variation_image_id ) {
							set_post_thumbnail( $group_id, $variation_image_id );
						}
					}
				}
				update_post_meta( $variation_id, 'zi_item_id', $item_id );
				WC_Product_Variable::sync( $group_id );
				// Regenerate lookup table for attributes
				$lookup_data_store = new LookupDataStore();
				$lookup_data_store->create_data_for_product( $group_id );
				// End group item add process
				unset( $attribute_arr );
			}
			// end of grouped item updating
		} else {
			// fwrite($fd, PHP_EOL . 'Group item empty');
			return;
		}
		// fwrite($fd, PHP_EOL . 'After group item sync');
		// fclose( $fd );
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
	public function zi_check_if_child_synced_already( $composite_zoho_id, $zi_url, $zi_org_id, $prod_id ) {
		if ( $prod_id ) {
			$bundle_childs = WC_PB_DB::query_bundled_items(
				array(
					'return' => 'id=>product_id',
					'bundle_id' => array( $prod_id ),
				)
			);
		}
		global $wpdb;

		$url = $zi_url . 'inventory/v1/compositeitems/' . $composite_zoho_id . '?organization_id=' . $zi_org_id;

		$execute_curl_call = new CMBIRD_API_Handler_Zoho();
		$json = $execute_curl_call->execute_curl_call_get( $url );
		$code = $json->code;
		// Flag to allow sync of parent composite item.
		$allow_sync = false;
		// Array of child object metadata.
		$product_array = array(); // [{prod_id:'',metadata:{key:'',value:''}},...].
		if ( '0' === $code || 0 === $code ) {
			foreach ( $json->composite_item->mapped_items as $child_item ) {
				$prod_meta = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->postmeta WHERE meta_key = 'zi_item_id' AND meta_value = %s", $child_item->item_id ) );
				// If any child will not have zoho id in meta field then process will return false and syncing will be skipped for given item.
				if ( ! empty( $prod_meta->post_id ) ) {

					$allow_sync = true;
					$prod_obj = (object) array(
						'prod_id' => $prod_meta->post_id,
						'metadata' => (object) array(
							'quantity_min' => max( 1, $child_item->quantity ),
							'quantity_max' => max( 1, $child_item->quantity ),
							'stock_status' => ( $child_item->stock_on_hand ) ? 'in_stock' : 'out_of_stock',
							'max_stock' => $child_item->stock_on_hand,
						),
					);
					if ( is_array( $bundle_childs ) && ! empty( $bundle_childs ) ) {
						$index = array_search( $prod_meta->post_id, $bundle_childs );
						unset( $bundle_childs[ $index ] );
					}
					array_push( $product_array, $prod_obj );
				} else {
					continue;
				}
			}
		}
		if ( is_array( $bundle_childs ) && ! empty( $bundle_childs ) ) {
			foreach ( $bundle_childs as $item_id => $val ) {
				WC_PB_DB::delete_bundled_item( $item_id );
			}
		}
		if ( $allow_sync ) {
			return $product_array;
		}
		return false;
	}
	/**
	 * Mapping of bundled product
	 *
	 * @param number $product_id - Product id of child item of bundle product.
	 * @param number $bundle_id  - Bundle id of product.
	 * @param number $menu_order - Listing order of child product ($menu_order will useful at composite product details page).
	 * @return void
	 */
	public function add_bundle_product( $product_id, $bundle_id, $menu_order = 0 ) {
		$bundle_items = WC_PB_DB::query_bundled_items(
			array(
				'return' => 'id=>product_id',
				'bundle_id' => array( $bundle_id ),
				'product_id' => array( $product_id ),
			)
		);
		$data = array(
			'menu_order' => $menu_order,
		);

		if ( count( $bundle_items ) > 0 ) {
			$result = WC_PB_DB::update_bundled_item( $bundle_id, $data );
			return $result;
		} else {
			// create data array of bundle item.
			$data = array(
				'product_id' => $product_id,
				'bundle_id' => $bundle_id,
				'menu_order' => $menu_order,
			);
			$bundle_id = WC_PB_DB::add_bundled_item( $data );
			return $bundle_id;
		}
	}

	/**
	 * Create or update bundle item metadata
	 *
	 * @param number $bundle_item_id bundle item id.
	 * @param string $meta_key - metadata key.
	 * @param string $meta_value - metadata value.
	 * @return void
	 */

	public function zi_update_bundle_meta( $bundle_item_id, $meta_key, $meta_value ) {
		// first get metadata from db.
		$metadata = WC_PB_DB::get_bundled_item_meta( $bundle_item_id, $meta_key );
		if ( $metadata ) {
			$result = WC_PB_DB::update_bundled_item_meta( $bundle_item_id, $meta_key, $meta_value );
		} else {
			$result = WC_PB_DB::add_bundled_item_meta( $bundle_item_id, $meta_key, $meta_value );
			return $result;
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
	public function recursively_sync_composite_item_from_zoho( $page, $category, $source ) {
		// Start logging
		// $fd = fopen( __DIR__ . '/recursively_sync_composite_item_from_zoho.txt', 'a+' );

		global $wpdb;
		$zi_org_id = $this->config['ProductZI']['OID'];
		$zi_url = $this->config['ProductZI']['APIURL'];

		$current_user = wp_get_current_user();
		$admin_author_id = $current_user->ID;
		if ( ! $admin_author_id ) {
			$admin_author_id = 1;
		}

		$url = $zi_url . 'inventory/v1/compositeitems/?organization_id=' . $zi_org_id . '&filter_by=Status.Active&category_id=' . $category . '&page=' . $page;

		$execute_curl_call = new CMBIRD_API_Handler_Zoho();
		$json = $execute_curl_call->execute_curl_call_get( $url );
		$code = $json->code;
		// $message = $json->message;
		// fwrite($fd, PHP_EOL . '$json  : ' . print_r($json, true));
		// Response for item sync with sync button. For cron sync blank array will return.
		$response_msg = array();
		if ( $code === '0' || $code === 0 ) {
			if ( empty( $json->composite_items ) ) {
				array_push( $response_msg, $this->zi_response_message( 'ERROR', 'No composite item to sync for category : ' . $category ) );
				return $response_msg;
			}
			// Accounting stock mode check
			$accounting_stock = $this->config['Settings']['enable_accounting_stock'];
			foreach ( $json->composite_items as $comp_item ) {
				// fwrite( $fd, PHP_EOL . 'Composite Item : ' . print_r( $comp_item, true ) );
				// Sync stock from specific warehouse check
				$zi_enable_warehousestock = $this->config['Settings']['enable_warehousestock'];
				$warehouse_id = $this->config['Settings']['warehouse_id'];
				$warehouses = $comp_item->warehouses;

				if ( $zi_enable_warehousestock === true ) {
					foreach ( $warehouses as $warehouse ) {
						if ( $warehouse->warehouse_id === $warehouse_id ) {
							if ( $accounting_stock ) {
								$stock = $warehouse->warehouse_available_stock;
							} else {
								$stock = $warehouse->warehouse_actual_available_stock;
							}
						}
					}
				} elseif ( $accounting_stock ) {
					$stock = $comp_item->available_stock;
				} else {
					$stock = $comp_item->actual_available_stock;
				}

				// ----------------- Create composite item in woocommerce--------------.
				// Code to skip sync with item already exists with same sku.
				$prod_id = wc_get_product_id_by_sku( $comp_item->sku );
				// Flag to enable or disable sync.
				$allow_to_import = false;
				// Check if product exists with same sku.
				if ( $prod_id ) {
					$zi_item_id = get_post_meta( $prod_id, 'zi_item_id', true );
					if ( $zi_item_id === $comp_item->composite_item_id ) {
						// If product is with same sku and zi_item_id mapped.
						// Do not import ...
						$allow_to_import = false;
					} else {
						// Map existing item with zoho id.
						update_post_meta( $prod_id, 'zi_item_id', $comp_item->composite_item_id );
						$allow_to_import = false;
					}
				} else {
					// If product not exists normal bahaviour of item sync.
					$allow_to_import = true;
				}
				$zoho_comp_item_id = $comp_item->composite_item_id;
				if ( $comp_item->composite_item_id ) {
					$child_items = $this->zi_check_if_child_synced_already( $zoho_comp_item_id, $zi_url, $zi_org_id, $prod_id );
					// Check if child items already synced with zoho.
					if ( ! $child_items ) {
						array_push( $response_msg, $this->zi_response_message( 'ERROR', 'Child not synced for composite item : ' . $zoho_comp_item_id ) );
						continue;
					}
					$product_res = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->postmeta} WHERE meta_key = 'zi_item_id' AND meta_value = %s", $zoho_comp_item_id ) );
					if ( ! empty( $product_res->post_id ) ) {
						$com_prod_id = $product_res->post_id;
					}
					// Check if item is allowed to import or not.
					if ( $allow_to_import ) {
						$product_class = new CMBIRD_Products_ZI_Export();
						$item_array = json_decode( wp_json_encode( $comp_item ), true );
						$com_prod_id = $product_class->cmbird_zi_product_to_woocommerce( $item_array, $stock, 'composite' );
						update_post_meta( $com_prod_id, 'zi_item_id', $zoho_comp_item_id );
					}
				}
				// Map composite items to database.
				if ( ! empty( $com_prod_id ) ) {
					wp_set_object_terms( $com_prod_id, 'bundle', 'product_type' );
					foreach ( $child_items as $child_prod ) {
						// Adding product to bundle.
						$child_bundle_id = $this->add_bundle_product( $child_prod->prod_id, $com_prod_id );
						if ( $child_bundle_id ) {
							foreach ( $child_prod->metadata as $bundle_meta_key => $bundle_meta_val ) {
								$this->zi_update_bundle_meta( $child_bundle_id, $bundle_meta_key, $bundle_meta_val );
							}
						}
					}
				}
				// --------------------------------------------------------------------.

				$is_synced_flag = false; // loggin purpose only .

				$product = wc_get_product( $com_prod_id );
				foreach ( $comp_item as $key => $value ) {
					if ( $key === 'status' ) {
						if ( ! empty( $com_prod_id ) ) {
							$status = $value === 'active' ? 'publish' : 'draft';
							$product->set_status( $status );
						}
					}
					if ( $key === 'description' ) {
						if ( ! empty( $com_prod_id ) && ! empty( $value ) ) {
							$product->set_short_description( $value );
						}
					}
					if ( $key === 'name' ) {
						if ( ! empty( $com_prod_id ) ) {
							$product->set_name( $value );
						}
					}
					if ( $key === 'sku' ) {
						if ( ! empty( $com_prod_id ) ) {
							$product->set_sku( $value );
						}
					}
					// Check if stock sync allowed by plugin.
					if ( $key === 'available_stock' || $key === 'actual_available_stock' ) {
						$zi_disable_stock_sync = $this->config['Settings']['disable_stock'];
						if ( ! $zi_disable_stock_sync ) {
							if ( $stock ) {
								if ( ! empty( $com_prod_id ) ) {
									// If value is less than 0 default 1.
									$stock_quantity = $stock < 0 ? 0 : $stock;
									$product->set_manage_stock( true );
									$product->set_stock_quantity( $stock_quantity );
									if ( $stock_quantity > 0 ) {
										$status = 'instock';
									} else {
										$backorder_status = get_post_meta( $com_prod_id, '_backorders', true );
										$status = ( $backorder_status === 'yes' ) ? 'onbackorder' : 'outofstock';
									}
									$product->set_stock_status( $status );
									update_post_meta( $com_prod_id, '_wc_pb_bundled_items_stock_status', $status );
								}
							}
						}
					}
					if ( $key === 'rate' ) {
						if ( ! empty( $com_prod_id ) ) {
							$sale_price = $product->get_sale_price();
							if ( empty( $sale_price ) ) {
								$product->set_regular_price( $value );
								$product->set_price( $value );
								update_post_meta( $com_prod_id, '_wc_pb_base_price', $value );
								update_post_meta( $com_prod_id, '_wc_pb_base_regular_price', $value );
								update_post_meta( $com_prod_id, '_wc_sw_max_regular_price', $value );
							} else {
								$product->set_regular_price( $value );
								update_post_meta( $com_prod_id, '_wc_pb_base_price', $value );
								update_post_meta( $com_prod_id, '_wc_pb_base_regular_price', $value );
								update_post_meta( $com_prod_id, '_wc_sw_max_regular_price', $value );
							}
						}
					}
					$product->save();

					if ( $key === 'image_document_id' ) {
						if ( ! empty( $com_prod_id ) && ! empty( $value ) ) {
							$image_class = new CMBIRD_Image_ZI();
							$image_class->cmbird_zi_get_image( $zoho_comp_item_id, $comp_item->name, $com_prod_id, $comp_item->image_name );
						}
					}
					if ( $key === 'category_name' ) {
						if ( ! empty( $com_prod_id ) && $comp_item->category_name != '' ) {
							$term = get_term_by( 'name', $comp_item->category_name, 'product_cat' );
							$term_id = $term->term_id;
							if ( empty( $term_id ) ) {
								$term = wp_insert_term(
									$comp_item->category_name,
									'product_cat',
									array(
										'parent' => 0,
									)
								);
								$term_id = $term['term_id'];
							}
							if ( $term_id ) {
								$existing_terms = wp_get_object_terms( $com_prod_id, 'product_cat' );
								if ( $existing_terms && count( $existing_terms ) > 0 ) {
									$is_terms_exist = $this->zi_check_terms_exists( $existing_terms, $term_id );
									if ( ! $is_terms_exist ) {
										update_post_meta( $com_prod_id, 'zi_category_id', $category );
										wp_add_object_terms( $com_prod_id, $term_id, 'product_cat' );
									}
								} else {
									update_post_meta( $com_prod_id, 'zi_category_id', $category );
									wp_set_object_terms( $com_prod_id, $term_id, 'product_cat' );
								}
							}
							// Remove "uncategorized" category if assigned
							$uncategorized_term = get_term_by( 'slug', 'uncategorized', 'product_cat' );
							if ( $uncategorized_term && has_term( $uncategorized_term->term_id, 'product_cat', $com_prod_id ) ) {
								wp_remove_object_terms( $com_prod_id, $uncategorized_term->term_id, 'product_cat' );
							}
						}
					}
				}

				// sync dimensions and weight
				$item_url = "{$zi_url}inventory/v1/compositeitems/{$zoho_comp_item_id}?organization_id={$zi_org_id}";
				$this->zi_item_dimension_weight( $item_url, $com_prod_id, true );

				// If item synced append to log : logging purpose only.
				if ( $is_synced_flag ) {
					array_push( $response_msg, $this->zi_response_message( 'SUCCESS', 'Composite item synced for id : ' . $comp_item->composite_item_id, $com_prod_id ) );
				}
			}

			if ( $json->page_context->has_more_page ) {
				++$page;
				$this->recursively_sync_composite_item_from_zoho( $page, $category, $source );
			}
		} else {
			array_push( $response_msg, $this->zi_response_message( $code, $json->message ) );
		}
		// fclose( $fd ); // End of logging.

		return $response_msg;
	}

	/**
	 * Function to retrieve item details, update weight and dimensions.
	 *
	 * @param string $url - URL to ge details.
	 * @return mixed return true if data false if error.
	 */
	public function zi_item_dimension_weight( $url, $product_id, $is_composite = false ) {
		// $fd = fopen(__DIR__ . '/zi_item_dimension_weight.txt', 'a+');
		// Check if item is for syncing purpose.
		$execute_curl_call = new CMBIRD_API_Handler_Zoho();
		$json = $execute_curl_call->execute_curl_call_get( $url );
		$code = $json->code;
		$message = $json->message;
		if ( 0 === $code || '0' === $code ) {
			if ( $is_composite ) {
				// fwrite($fd, PHP_EOL . '$json  : ' . print_r($json, true));
				$details = $json->composite_item->package_details;
			} else {
				$details = $json->item->package_details;
			}
			$product = wc_get_product( $product_id );
			$product->set_weight( floatval( $details->weight ) );
			$product->set_length( floatval( $details->length ) );
			$product->set_width( floatval( $details->width ) );
			$product->set_height( floatval( $details->height ) );
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
	public function zi_response_message( $index_col, $message, $woo_id = '' ) {
		return (object) array(
			'resp_id' => $index_col,
			'message' => $message,
			'woo_prod_id' => $woo_id,
		);
	}

	/**
	 * Helper Function to check if terms already exists.
	 */
	public function zi_check_terms_exists( $existing_terms, $term_id ) {
		foreach ( $existing_terms as $woo_existing_term ) {
			if ( $woo_existing_term->term_id === $term_id ) {
				return true;
			} else {
				return false;
			}
		}
	}
}
$cmbird_products_zi = new CMBIRD_Products_ZI();
