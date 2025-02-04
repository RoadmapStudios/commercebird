<?php

namespace CommerceBird\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CMBIRD_Image_ZI;
use CMBIRD_Products_ZI;
use CMBIRD_Products_ZI_Export;
use WC_Data_Exception;
use WC_Product_Variation;
use WC_Product_Variable;
use WP_REST_Response;
use wpdb;
use CMBIRD_Common_Functions;
use Automattic\WooCommerce\Internal\ProductAttributesLookup\LookupDataStore;

class ProductWebhook {

	use Api;

	private static string $endpoint = 'zoho-product';

	private $is_tax_enabled;

	public function __construct() {
		register_rest_route(
			self::$namespace,
			self::$endpoint,
			array(
				'methods' => 'POST',
				'callback' => array( $this, 'handle' ),
				'permission_callback' => array( $this, 'permission_check' ),
			)
		);
		// Check if WooCommerce taxes are enabled and store the result
		$this->is_tax_enabled = 'yes' === get_option( 'woocommerce_calc_taxes' );
	}

	// Method to use the tax check across the class
	public function is_tax_enabled(): bool {
		return $this->is_tax_enabled;
	}


	/**
	 * @throws WC_Data_Exception
	 */
	private function process( array $data ): WP_REST_Response {
		$response = new WP_REST_Response();
		$response->set_data( $this->empty_response );
		$response->set_status( 404 );
		if ( ! array_key_exists( 'item', $data ) && ! array_key_exists( 'inventory_adjustment', $data ) ) {
			return $response;
		}

		// Accounting stock mode check
		$accounting_stock = get_option( 'cmbird_zoho_enable_accounting_stock_status' );
		$zi_enable_warehousestock = get_option( 'cmbird_zoho_enable_warehousestock_status' );
		$warehouse_id = get_option( 'cmbird_zoho_warehouse_id_status' );

		// variable item sync
		if ( array_key_exists( 'item', $data ) ) {
			return $this->process_product_data( $data['item'], $zi_enable_warehousestock, $warehouse_id, $accounting_stock );
		}
		// inventory_adjustment
		if ( array_key_exists( 'inventory_adjustment', $data ) ) {
			return $this->inventory_adjustment( $data['inventory_adjustment'] );
		}

		return $response;
	}

	/**
	 * @param $item
	 * @param $zi_enable_warehousestock
	 * @param $warehouse_id
	 * @param $accounting_stock
	 *
	 * @return WP_REST_Response
	 * @throws WC_Data_Exception
	 */
	public function process_product_data( $item, $zi_enable_warehousestock, $warehouse_id, $accounting_stock ): WP_REST_Response {
		// $fd = fopen( __DIR__ . '/process_product_data.txt', 'a+' );

		// clean orphaned data from the database
		$common_class = new CMBIRD_Common_Functions();
		$common_class->clear_orphan_data();

		global $wpdb;
		$item_id = $item['item_id'];
		$item_name = $item['name'];
		$item_price = $item['rate'];
		$item_sku = $item['sku'];
		$item_description = $item['description'];
		$item_status = $item['status'] === 'active' ? 'publish' : 'draft';
		$item_brand = $item['brand'];
		$category_id = $item['category_id'];
		$custom_fields = $item['custom_fields'];
		$item_image = $item['image_name'];
		// Stock mode check
		$warehouses = $item['warehouses'];
		if ( true === $zi_enable_warehousestock ) {
			foreach ( $warehouses as $warehouse ) {
				if ( $warehouse['warehouse_id'] === $warehouse_id ) {
					if ( $accounting_stock ) {
						$item_stock = $warehouse['warehouse_available_stock'];
					} else {
						$item_stock = $warehouse['warehouse_actual_available_stock'];
					}
				}
			}
		} elseif ( $accounting_stock ) {
			$item_stock = $item['available_stock'];
		} else {
			$item_stock = $item['actual_available_stock'];
		}
		if ( isset( $item['group_name'] ) ) {
			$group_name = $item['group_name'];
		} else {
			$group_name = '';
		}
		$item_category = $item['category_name'];
		if ( isset( $item['group_id'] ) ) {
			$groupid = $item['group_id'];
		} else {
			$groupid = '';
		}

		// Item package details
		$details = $item['package_details'];
		$weight = floatval( $details['weight'] );
		$length = floatval( $details['length'] );
		$width = floatval( $details['width'] );
		$height = floatval( $details['height'] );

		// fwrite($fd, PHP_EOL . '$groupid : ' . $groupid);
		if ( ! empty( $groupid ) ) {
			// fwrite($fd, PHP_EOL . 'Inside grouped items');
			// find parent variable product
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key='zi_item_id' AND meta_value=%s", $groupid ) );
			$group_id = $row->post_id;

			if ( ! empty( $group_id ) ) {
				$existing_parent_product = wc_get_product( $group_id );
				$zi_disable_itemdescription_sync = get_option( 'cmbird_zoho_disable_description_sync_status' );
				if ( ! empty( $item_description ) && ! $zi_disable_itemdescription_sync ) {
					// fwrite($fd, PHP_EOL . 'Item description update : ' . $item_description);
					$existing_parent_product->set_short_description( $item_description );
				}
				// Update the name of the variable product if allowed
				$zi_disable_itemname_sync = get_option( 'cmbird_zoho_disable_name_sync_status' );
				if ( ! $zi_disable_itemname_sync ) {
					$existing_parent_product->set_name( $item['group_name'] );
					$slug = sanitize_title( $item['group_name'] );
					$existing_parent_product->set_slug( $slug );
				}
				// Brand update if taxonomy product_brand exists
				if ( ! empty( $item_brand ) && taxonomy_exists( 'product_brand' ) ) {
					wp_set_object_terms( $groupid, $item_brand, 'product_brand' );
				} elseif ( ! empty( $item_brand ) && taxonomy_exists( 'product_brand' ) ) {
					wp_set_object_terms( $groupid, $item_brand, 'product_brand' );
				}
				// Update the custom fields if the custom fields are not empty
				$cmbird_product_zi = new CMBIRD_Products_ZI();
				if ( ! empty( $custom_fields ) ) {
					$cmbird_product_zi->sync_item_custom_fields( $custom_fields, $groupid );
				}

				// Create or Update the Attributes
				// turn $item into array
				$gp_arr = (array) $item;
				$attr_created = $cmbird_product_zi->sync_attributes_of_group( $gp_arr, $group_id );
				if ( ! empty( $group_id ) && $attr_created ) {
					// Create the variations
					// create object with the $group_id and the $groupid
					$args = array(
						'zi_group_id' => $groupid,
						'group_id' => $group_id,
					);
					$cmbird_product_zi->import_variable_product_variations( $args );
				}

				// set the product status of the variable parent product
				// $existing_parent_product->set_status( $item_status );
				$existing_parent_product->save();
			} else {
				// Add in scheduler to create the Variable Product
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
			}

			$row_item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key='zi_item_id' AND meta_value=%s", $item_id ) );
			$variation_id = $row_item->post_id;
			if ( $variation_id ) {
				// updating existing variations
				$variation = new WC_Product_Variation( $variation_id );
				// Prices
				if ( ! empty( $item_price ) ) {
					$variation->set_price( $item_price );
				}
				$variation->set_regular_price( $item_price );
				// Stock
				$zi_disable_stock_sync = get_option( 'cmbird_zoho_disable_stock_sync_status' );
				if ( ! empty( $item_stock ) && ! $zi_disable_stock_sync ) {
					// fwrite($fd, PHP_EOL . 'Stock is here:'. $item_stock);
					$variation->set_stock_quantity( $item_stock );
					$variation->set_manage_stock( true );
					// $variation->set_stock_status('');
				} else {
					// fwrite($fd, PHP_EOL . 'Available Stock : false');
					$variation->set_manage_stock( false );
				}
				// featured image
				$zi_disable_itemimage_sync = get_option( 'cmbird_zoho_disable_image_sync_status' );
				if ( ! empty( $item_image ) && ! $zi_disable_itemimage_sync ) {
					// fwrite($fd, PHP_EOL . 'Sync Image' );
					$image_class = new CMBIRD_Image_ZI();
					$image_class->cmbird_zi_get_image( $item_id, $item_name, $variation_id, $item_image );
				}
				// Disable or enable the variation based on the item_status
				$variation->set_status( $item_status );
				// Update Purchase price
				$variation->update_meta_data( '_cost_price', $item['purchase_rate'] );

				// Map taxes while syncing product from zoho.
				if ( $item['tax_id'] && ! $this->is_tax_enabled() ) {
					$zi_common_class = new CMBIRD_Common_Functions();
					$woo_tax_class = $zi_common_class->get_tax_class_by_percentage( $item['tax_percentage'] );
					$variation->set_tax_status( 'taxable' );
					$variation->set_tax_class( $woo_tax_class );
				}
				// weight & dimensions
				$variation->set_weight( $weight );
				$variation->set_length( $length );
				$variation->set_width( $width );
				$variation->set_height( $height );

				$variation->save(); // Save the data
			} elseif ( 'publish' === $item_status ) {
				$attribute_name11 = $item['attribute_option_name1'];
				$attribute_name12 = $item['attribute_option_name2'];
				$attribute_name13 = $item['attribute_option_name3'];
				// Prepare the variation data
				$attribute_arr = array();
				if ( ! empty( $attribute_name11 ) ) {
					$sanitized_name1 = wc_sanitize_taxonomy_name( $item['attribute_name1'] );
					$attribute_arr[ $sanitized_name1 ] = $attribute_name11;
				}
				if ( ! empty( $attribute_name12 ) ) {
					$sanitized_name2 = wc_sanitize_taxonomy_name( $item['attribute_name2'] );
					$attribute_arr[ $sanitized_name2 ] = $attribute_name12;
				}
				if ( ! empty( $attribute_name13 ) ) {
					$sanitized_name3 = wc_sanitize_taxonomy_name( $item['attribute_name3'] );
					$attribute_arr[ $sanitized_name3 ] = $attribute_name13;
				}

				// here actually create new variation because sku not found
				$zi_disable_stock_sync = get_option( 'cmbird_zoho_disable_stock_sync_status' );
				$variation = new WC_Product_Variation();
				$variation->set_parent_id( $group_id );
				$variation->set_status( 'publish' );
				$variation->set_regular_price( $item_price );
				$variation->set_sku( $item_sku );
				$variation->set_weight( $weight );
				$variation->set_length( $length );
				$variation->set_width( $width );
				$variation->set_height( $height );
				if ( ! $zi_disable_stock_sync ) {
					$variation->set_stock_quantity( $item_stock );
					$variation->set_manage_stock( true );
					$variation->set_stock_status( '' );
				} else {
					$variation->set_manage_stock( false );
				}
				// Map taxes while syncing product from zoho.
				if ( $item['tax_id'] && ! $this->is_tax_enabled() ) {
					$zi_common_class = new CMBIRD_Common_Functions();
					$woo_tax_class = $zi_common_class->get_tax_class_by_percentage( $item['tax_percentage'] );
					$variation->set_tax_status( 'taxable' );
					$variation->set_tax_class( $woo_tax_class );
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

				// featured image
				$zi_disable_itemimage_sync = get_option( 'cmbird_zoho_disable_image_sync_status' );
				if ( ! empty( $item_image ) && ! $zi_disable_itemimage_sync ) {
					$image_class = new CMBIRD_Image_ZI();
					$image_class->cmbird_zi_get_image( $item_id, $item_name, $variation_id, $item_image );
				}

				update_post_meta( $variation_id, 'zi_item_id', $item_id );
				// Sync variation data with parent variable product
				WC_Product_Variable::sync( $group_id );
				// Regenerate lookup table for attributes
				$lookup_data_store = new LookupDataStore();
				$lookup_data_store->create_data_for_product( $group_id );
				// End group item add process
				unset( $attribute_arr );
			}
			wc_delete_product_transients( $group_id ); // Clear/refresh cache
			// end of grouped item creation
		} else {
			// fwrite($fd, PHP_EOL . 'Inside simple items');
			// fwrite($fd, PHP_EOL . 'Item description Simple : ' . $item_description);
			$row_item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key = 'zi_item_id' AND meta_value = %s", $item_id ) );
			$mapped_product_id = $row_item->post_id;
			// simple product
			// fwrite($fd, PHP_EOL . 'Before Match check');
			$pdt_id = '';
			if ( ! empty( $mapped_product_id ) && null !== $mapped_product_id ) {
				$product_found = wc_get_product( $mapped_product_id );
				if ( ! $product_found ) {
					// remove all postmeta of that product id.
					$wpdb->delete( $wpdb->postmeta, array( 'post_id' => $mapped_product_id ) );
				} else {
					$pdt_id = $mapped_product_id;
				}
			} elseif ( empty( $item['is_combo_product'] ) ) {
				// fwrite($fd, PHP_EOL . 'Inside create product');

				// Check if Category is selected before creating simple item
				if ( 'publish' === $item_status ) {
					$opt_category = get_option( 'cmbird_zoho_item_category' );
					$opt_category = maybe_unserialize( $opt_category );
					$category_id = $item['category_id'];
					if ( $opt_category ) {
						if ( in_array( $category_id, $opt_category, true ) ) {
							$product_class = new CMBIRD_Products_ZI_Export();
							$pdt_id = $product_class->cmbird_zi_product_to_woocommerce( $item, $item_stock );
						}
					}
				}
				// fwrite($fd, PHP_EOL . 'After adding it : ' . $pdt_id);
			}

			// If there is product id then update metadata.
			if ( ! empty( $pdt_id ) ) {
				$simple_product = wc_get_product( $pdt_id );
				// update the name if its allowed
				$zi_disable_itemname_sync = get_option( 'cmbird_zoho_disable_name_sync_status' );
				if ( ! $zi_disable_itemname_sync ) {
					$simple_product->set_name( $item_name );
					$slug = sanitize_title( $item_name );
					$simple_product->set_slug( $slug );
				}
				// update the zi_item_id using the product instance
				$simple_product->update_meta_data( 'zi_item_id', $item_id );
				// update the status using set_status()
				$simple_product->set_status( $item_status );
				// Update the product SKU
				$simple_product->set_sku( $item_sku );
				// price
				$sale_price = $simple_product->get_sale_price();
				$simple_product->set_regular_price( $item_price );
				if ( empty( $sale_price ) ) {
					$simple_product->set_price( $item_price );
				}
				// Update Purchase price
				$simple_product->update_meta_data( '_cost_price', $item['purchase_rate'] );
				// description
				$zi_disable_itemdescription_sync = get_option( 'cmbird_zoho_disable_description_sync_status' );
				if ( ! empty( $item_description ) && ! $zi_disable_itemdescription_sync ) {
					$simple_product->set_short_description( $item_description );
				}
				// Brand update if taxonomy product_brand(s) exists
				if ( ! empty( $item_brand ) && taxonomy_exists( 'product_brand' ) ) {
					wp_set_object_terms( $pdt_id, $item_brand, 'product_brand' );
				} elseif ( ! empty( $item_brand ) && taxonomy_exists( 'product_brand' ) ) {
					wp_set_object_terms( $pdt_id, $item_brand, 'product_brand' );
				}
				// stock
				$zi_disable_stock_sync = get_option( 'cmbird_zoho_disable_stock_sync_status' );
				if ( ! $zi_disable_stock_sync ) {
					// fwrite( $fd, PHP_EOL . 'Inside1' );
					if ( 'NULL' !== gettype( $item_stock ) ) {
						// fwrite( $fd, PHP_EOL . 'Inside1.1' );
						// Set manage stock to yes
						$simple_product->set_manage_stock( true );
						// Update stock for simple product
						$simple_product->set_stock_quantity( number_format( $item_stock, 0, '.', '' ) );
						if ( $item_stock > 0 ) {
							// fwrite( $fd, PHP_EOL . 'Inside2' );
							// Update stock status
							$simple_product->set_stock_status( 'instock' );
							wp_set_post_terms( $pdt_id, 'instock', 'product_visibility', true );
						} else {
							// fwrite($fd, PHP_EOL . 'Inside3');
							$stock_status = $simple_product->backorders_allowed() ? 'onbackorder' : 'outofstock';
							$simple_product->set_stock_status( $stock_status );
							wp_set_post_terms( $pdt_id, $stock_status, 'product_visibility', true );
						}
					}
				}
				// fwrite($fd, PHP_EOL . 'After stock');
				// Update weight & dimensions of simple product
				$simple_product->set_weight( $weight );
				$simple_product->set_length( $length );
				$simple_product->set_width( $width );
				$simple_product->set_height( $height );

				// featured image
				$zi_disable_itemimage_sync = get_option( 'cmbird_zoho_disable_image_sync_status' );
				if ( ! empty( $item_image ) && ! $zi_disable_itemimage_sync ) {
					$image_class = new CMBIRD_Image_ZI();
					$image_class->cmbird_zi_get_image( $item_id, $item_name, $pdt_id, $item_image );
				}

				// category
				if ( ! empty( $item_category ) && empty( $group_name ) ) {
					$term = get_term_by( 'name', $item_category, 'product_cat' );
					$term_id = $term->term_id;
					if ( empty( $term_id ) ) {
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
					$uncategorized_term = get_term_by( 'slug', 'uncategorized', 'product_cat' );
					if ( $uncategorized_term && has_term( $uncategorized_term->term_id, 'product_cat', $pdt_id ) ) {
						wp_remove_object_terms( $pdt_id, $uncategorized_term->term_id, 'product_cat' );
					}
					if ( ! is_wp_error( $term_id ) && isset( $term->term_id ) ) {
						$existing_terms = wp_get_object_terms( $pdt_id, 'product_cat' );
						if ( $existing_terms && count( $existing_terms ) > 0 ) {
							$import_class = new CMBIRD_Products_ZI();
							$is_term_exist = $import_class->zi_check_terms_exists( $existing_terms, $term_id );
							if ( ! $is_term_exist ) {
								$simple_product->update_meta_data( 'zi_category_id', $item['category_id'] );
								wp_add_object_terms( $pdt_id, $term_id, 'product_cat' );
							}
						} else {
							$simple_product->update_meta_data( 'zi_category_id', $item['category_id'] );
							wp_set_object_terms( $pdt_id, $term_id, 'product_cat' );
						}
					}
				}

				// Update the custom fields if the custom fields are not empty
				if ( ! empty( $custom_fields ) ) {
					$import_class = new CMBIRD_Products_ZI();
					$import_class->sync_item_custom_fields( $custom_fields, $pdt_id );
				}

				// Map taxes while syncing product from zoho.
				if ( $item['tax_id'] && ! $this->is_tax_enabled() ) {
					$zi_common_class = new CMBIRD_Common_Functions();
					$woo_tax_class = $zi_common_class->get_tax_class_by_percentage( $item['tax_percentage'] );
					$simple_product->set_tax_status( 'taxable' );
					$simple_product->set_tax_class( $woo_tax_class );
				}
				$simple_product->save();
				wc_delete_product_transients( $pdt_id );
			}
		}
		$response = new WP_REST_Response();
		$response->set_data( 'success on variable product' );
		$response->set_status( 200 );

		// fclose( $fd ); // close logfile.

		return $response;
	}

	/**
	 * @param $inventory_adjustment
	 * @param wpdb $wpdb
	 *
	 * @return WP_REST_Response
	 */
	public function inventory_adjustment( $inventory_adjustment ): WP_REST_Response {
		global $wpdb;
		$item = $inventory_adjustment;
		$line_items = $item['line_items'];
		// get first item from line items array
		$item_id = $line_items[0]['item_id'];
		$adjusted_stock = $line_items[0]['quantity_adjusted'];

		$row_item = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key = 'zi_item_id' AND meta_value = %s",
				$item_id
			)
		);
		$mapped_product_id = $row_item->post_id;

		if ( ! empty( $mapped_product_id ) ) {
			// stock
			$zi_disable_stock_sync = get_option( 'cmbird_zoho_disable_stock_sync_status' );
			$product = wc_get_product( $mapped_product_id );
			// Check if the product is in stock
			if ( ! $zi_disable_stock_sync ) {
				if ( $product->is_in_stock() ) {
					// Get stock quantity
					$stock_quantity = $product->get_stock_quantity();
					$new_stock = $stock_quantity + $adjusted_stock;
					$product->set_stock_quantity( $new_stock );
				} else {
					$product->set_stock_quantity( $adjusted_stock );
					$product->set_stock_status( 'instock' );
					$product->set_manage_stock( true );
				}
				$product->save();
			}
		}

		$response = new WP_REST_Response();
		$response->set_data( 'Inventory Adjustment successful' );
		$response->set_status( 200 );

		return $response;
	}
}
