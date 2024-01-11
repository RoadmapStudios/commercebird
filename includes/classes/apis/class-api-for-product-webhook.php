<?php

namespace RMS\API;

use ImageClass;
use ImportProductClass;
use ProductClass;
use WC_Data_Exception;
use WC_Product_Variation;
use WP_REST_Response;
use WP_REST_Server;
use WP_User_Query;
use wpdb;
use ZI_CommonClass;

defined( 'RMS_PLUGIN_NAME' ) || exit();

class ProductWebhook {
	use Api;

	private string $endpoint = 'zoho-product';


	public function __construct() {
		register_rest_route(
			$this->namespace,
			$this->endpoint,
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => '__return_true',
			)
		);
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
		$accounting_stock         = get_option( 'zoho_enable_accounting_stock_status' );
		$zi_enable_warehousestock = get_option( 'zoho_enable_warehousestock_status' );
		$warehouse_id             = get_option( 'zoho_warehouse_id' );

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
		// $fd = fopen(__DIR__ . '/process_product_data.txt', 'a+');

		global $wpdb;
		$item_id          = $item['item_id'];
		$item_name        = $item['name'];
		$item_price       = $item['rate'];
		$item_sku         = $item['sku'];
		$item_description = $item['description'];
		$item_brand       = $item['brand'];
		$item_tags_hash   = $item['custom_field_hash'];
		$item_tags        = $item_tags_hash['cf_tags'];

		// Stock mode check
		$warehouses = $item['warehouses'];
		if ( true === $zi_enable_warehousestock ) {
			foreach ( $warehouses as $warehouse ) {
				if ( $warehouse['warehouse_id'] === $warehouse_id ) {
					if ( 'true' === $accounting_stock ) {
						$item_stock = $warehouse['warehouse_available_for_sale_stock'];
					} else {
						$item_stock = $warehouse['warehouse_actual_available_for_sale_stock'];
					}
				}
			}
		} elseif ( $accounting_stock ) {
			$item_stock = $item['available_for_sale_stock'];
		} else {
			$item_stock = $item['actual_available_for_sale_stock'];
		}
		$item_image    = $item['image_name'];
		$group_name    = $item['group_name'];
		$item_category = $item['category_name'];
		$groupid       = $item['group_id'];

		// Item package details
		$details = $item['package_details'];
		$weight  = floatval( $details['weight'] );
		$length  = floatval( $details['length'] );
		$width   = floatval( $details['width'] );
		$height  = floatval( $details['height'] );

		// getting the admin user ID
		$query = new WP_User_Query(
			array(
				'role'        => 'Administrator',
				'count_total' => false,
			)
		);
		$users = $query->get_results();
		if ( $users ) {
			$admin_author_id = $users[0]->ID;
		} else {
			$admin_author_id = '1';
		}

		// fwrite($fd, PHP_EOL . '$groupid : ' . $groupid);
		if ( ! empty( $groupid ) ) {
			$zi_disable_itemdescription_sync = get_option( 'zoho_disable_itemdescription_sync_status' );
			if ( ! empty( $item_description ) && 'true' !== $zi_disable_itemdescription_sync ) {
				// fwrite($fd, PHP_EOL . 'Item description update : ' . $item_description);
				$tbl_prefix = $wpdb->prefix;
				$wpdb->update( $tbl_prefix . 'posts', array( 'post_excerpt' => $item_description ), array( 'ID' => $groupid ), array( '%s' ), array( '%d' ) );
			}

			// Tags
			if ( ! empty( $item_tags ) ) {
				$final_tags = explode( ',', $item_tags );
				wp_set_object_terms( $groupid, $final_tags, 'product_tag' );
			}

			// Brand update if taxonomy product_brand exists
			if ( ! empty( $item_brand ) && taxonomy_exists( 'product_brand' ) ) {
			    wp_set_object_terms( $groupid, $item_brand, 'product_brand' );
			} elseif(!empty($item_brand) && taxonomy_exists('product_brands')) {
				wp_set_object_terms($groupid, $item_brand, 'product_brands');
			}

			// fwrite($fd, PHP_EOL . 'Query : ' . 'SELECT * FROM ' . $wpdb->prefix . "postmeta WHERE meta_key='zi_item_id' AND meta_value='" . $groupid . "'");
			// find parent variable product
			$row      = $wpdb->get_row( 'SELECT * FROM ' . $wpdb->prefix . "postmeta WHERE meta_key='zi_item_id' AND meta_value='" . $groupid . "'" );
			$group_id = $row->post_id;
			// fwrite($fd, PHP_EOL . 'Row Data : ' . print_r($row, true));
			// fwrite($fd, PHP_EOL . 'parent postID : ' . $group_id);

			$rowItem      = $wpdb->get_row( 'SELECT * FROM ' . $wpdb->prefix . "postmeta WHERE meta_key='zi_item_id' AND meta_value='" . $item_id . "'" );
			$variation_id = $rowItem->post_id;
			if ( $variation_id ) { // updating existing variations
				$variation = new WC_Product_Variation( $variation_id );
				## Set/save all other data

				// Prices
				if ( ! empty( $item['rate'] ) ) {
					$variation->set_price( $item['rate'] );
				}
				$variation->set_regular_price( $item['rate'] );
				// Stock
				if ( ! empty( $item_stock ) ) {
					// fwrite($fd, PHP_EOL . 'Stock is here:'. $item_stock);
					$variation->set_stock_quantity( $item_stock );
					$variation->set_manage_stock( true );
					// $variation->set_stock_status('');
				} else {
					// fwrite($fd, PHP_EOL . 'Available Stock : false');
					$variation->set_manage_stock( false );
				}
				// featured image
				$zi_disable_itemimage_sync = get_option( 'zoho_disable_itemimage_sync_status' );
				if ( ! empty( $item_image ) && 'true' !== $zi_disable_itemimage_sync ) {
					// fwrite($fd, PHP_EOL . 'Sync Image' );
					$imageClass = new ImageClass();
					$imageClass->args_attach_image( $item_id, $item_name, $variation_id, $item_image, $admin_author_id );
				}

				$variation->save(); // Save the data
			} else {
				$attribute_name11 = $item['attribute_option_name1'];
				$attribute_name12 = $item['attribute_option_name2'];
				$attribute_name13 = $item['attribute_option_name3'];

				if ( ! empty( $attribute_name11 ) ) {

					$attribute_arr[ $item['attribute_name1'] ] = $attribute_name11;
				}
				if ( ! empty( $attribute_name12 ) ) {

					$attribute_arr[ $item['attribute_name2'] ] = $attribute_name12;
				}
				if ( ! empty( $attribute_name13 ) ) {

					$attribute_arr[ $item['attribute_name3'] ] = $attribute_name13;
				}
				$variation_data = array(
					'attributes'    => $attribute_arr,
					'sku'           => $item['sku'],
					'regular_price' => $item['rate'],
					'stock_qty'     => $item_stock,
				);

				$status         = 'active' !== $item['status'] ? 'draft' : 'publish';
				$variation_post = array(
					'post_title'  => $item['name'],
					'post_name'   => $item['name'],
					'post_status' => $status,
					'post_parent' => $group_id,
					'post_type'   => 'product_variation',
					'guid'        => get_the_permalink( $group_id ),
				);
				// Creating the product variation
				$variation_id = wp_insert_post( $variation_post );

				// Get an instance of the WC_Product_Variation object
				$variation = new WC_Product_Variation( $variation_id );

				// Iterating through the variations attributes
				foreach ( $variation_data['attributes'] as $attribute => $term_name ) {
					update_post_meta( $variation_id, 'attribute_' . strtolower( str_replace( ' ', '-', $attribute ) ), trim( $term_name ) );
					update_post_meta( $variation_id, 'group_id_store', $group_id );
				}

				// SKU
				// $variation->set_sku($variation_data['sku']);

				// Prices
				$variation->set_regular_price( $variation_data['regular_price'] );
				$variation_sale_price = get_post_meta( $variation_id, '_sale_price', true );
				if ( empty( $variation_sale_price ) ) {
					$variation->set_price( $variation_data['regular_price'] );
				}

				// featured image
				$zi_disable_itemimage_sync = get_option( 'zoho_disable_itemimage_sync_status' );
				if ( ! empty( $item_image ) && $zi_disable_itemimage_sync != 'true' ) {
					$imageClass = new ImageClass();
					$imageClass->args_attach_image( $item_id, $item_name, $variation_id, $item_image, $admin_author_id );
				}

				// Stock
				if ( ! empty( $variation_data['stock_qty'] ) ) {
					$variation->set_stock_quantity( $variation_data['stock_qty'] );
					$variation->set_manage_stock( true );
					$variation->set_stock_status( '' );
				} else {
					$variation->set_manage_stock( false );
				}
				$variation->set_weight( '' ); // weight (reseting)
				$variation->save(); // Save the data
				update_post_meta( $variation_id, 'zi_item_id', $item_id );

				// End group item add process
				unset( $attribute_arr );
			}
			if ( $variation_id ) {
				// weight & dimensions
				update_post_meta( $variation_id, '_weight', $weight );
				update_post_meta( $variation_id, '_length', $length );
				update_post_meta( $variation_id, '_width', $width );
				update_post_meta( $variation_id, '_height', $height );
			}
			wc_delete_product_transients( $group_id ); // Clear/refresh cache
			// end of grouped item creation
		} else {
			// fwrite($fd, PHP_EOL . 'Inside simple items');
			// fwrite($fd, PHP_EOL . 'Item description Simple : ' . $item_description);
			$rowItem           = $wpdb->get_row( 'SELECT * FROM ' . $wpdb->prefix . "postmeta WHERE meta_key='zi_item_id' AND meta_value='" . $item_id . "'" );
			$mapped_product_id = $rowItem->post_id;
			// simple product
			// fwrite($fd, PHP_EOL . 'Before Match check');
			$pdt_id = '';
			if ( ! empty( $mapped_product_id ) ) {
				$pdt_id = $mapped_product_id;
				// Sync product name if that is allowed.
				$product_class = new ProductClass();
				$product_class->update_product_name( $pdt_id, $item_name );
			} elseif ( empty( $item['is_combo_product'] ) ) {
				// fwrite($fd, PHP_EOL . 'Inside create product');
				$current_user = wp_get_current_user();
				if ( ! empty( $current_user ) && $current_user->ID ) {
					$admin_author_id = $current_user->ID;
					// get admin user id who started the cron job.
				} else {
					$admin_author_id = get_option( 'zi_cron_admin' );
				}

				// Check if Category is selected before creating simple item
				$opt_category = get_option( 'zoho_item_category' );
				$category_id  = $item['category_id'];
				if ( $opt_category ) {
					$opt_category = unserialize( $opt_category );
					if ( in_array( $category_id, $opt_category, true ) ) {
						$product_class = new ProductClass();
						$pdt_id = $product_class->zi_product_to_woocommerce( $item, $item_stock );
					}
				}
				// fwrite($fd, PHP_EOL . 'After adding it : ' . $pdt_id);
			}

			// If there is product id then update metadata.
			if ( ! empty( $pdt_id ) ) {
				$simple_product = wc_get_product( $pdt_id );
				// update the zi_item_id using the product instance
				$simple_product->update_meta_data( 'zi_item_id', $item_id );

				// fwrite($fd, PHP_EOL . 'Insite mappping metadata');
				// Update the product SKU
				$simple_product->set_sku( $item_sku );
				// fwrite($fd, PHP_EOL . '$item_price : ' . $item_price);
				// price
				$sale_price = get_post_meta( $pdt_id, '_sale_price', true );
				$simple_product->set_regular_price( $item_price );
				if ( empty( $sale_price ) ) {
					$simple_product->update_meta_data( '_price', $item_price );
				}

				// description
				$zi_disable_itemdescription_sync = get_option( 'zoho_disable_itemdescription_sync_status' );
				if ( ! empty( $item_description ) && 'true' !== $zi_disable_itemdescription_sync ) {
					$simple_product->set_description( $item_description );
				}

				// Tags
				if ( ! empty( $item_tags ) ) {
					$final_tags = explode( ',', $item_tags );
					wp_set_object_terms( $pdt_id, $final_tags, 'product_tag' );
				}

				// Brand update if taxonomy product_brand(s) exists
				if(!empty($item_brand) && taxonomy_exists('product_brand')) {
					wp_set_object_terms($pdt_id, $item_brand, 'product_brand');
				} elseif(!empty($item_brand) && taxonomy_exists('product_brands')) {
					wp_set_object_terms($pdt_id, $item_brand, 'product_brands');
				}

				// stock
				$zi_stock_sync = get_option( 'zoho_stock_sync_status' );
				if ( $zi_stock_sync != 'true' ) {
					// fwrite($fd, PHP_EOL . 'Inside1');
					if ( 'NULL' !== gettype( $item_stock ) ) {
						// fwrite($fd, PHP_EOL . 'Inside1.1');
						// Set manage stock to yes
						$simple_product->set_manage_stock( true );
						// Update stock for simple product
						$simple_product->set_stock_quantity( number_format( $item_stock, 0, '.', '' ) );
						if ( $item_stock > 0 ) {
							// fwrite($fd, PHP_EOL . 'Inside2');
							$status = 'instock';
							// Update stock status
							$simple_product->set_stock_status( $status );
							wp_set_post_terms( $pdt_id, $status, 'product_visibility', true );
						} else {
							// fwrite($fd, PHP_EOL . 'Inside3');
							$backorder_status = get_post_meta( $pdt_id, '_backorders', true );
							$status           = ( $backorder_status === 'yes' ) ? 'onbackorder' : 'outofstock';
							$simple_product->set_stock_status( $status );
							wp_set_post_terms( $pdt_id, $status, 'product_visibility', true );
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
				$zi_disable_itemimage_sync = get_option( 'zoho_disable_itemimage_sync_status' );
				if ( ! empty( $item_image ) && $zi_disable_itemimage_sync != 'true' ) {
					$imageClass = new ImageClass();
					$imageClass->args_attach_image( $item_id, $item_name, $pdt_id, $item_image, $admin_author_id );
				}

				// category
				if ( ! empty( $item_category ) && empty( $group_name ) ) {
					$term    = get_term_by( 'name', $item_category, 'product_cat' );
					$term_id = $term->term_id;
					if ( empty( $term_id ) ) {
						$term    = wp_insert_term(
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
						$existingTerms = wp_get_object_terms( $pdt_id, 'product_cat' );
						if ( $existingTerms && count( $existingTerms ) > 0 ) {
							$importClass  = new ImportProductClass();
							$isTermsExist = $importClass->zi_check_terms_exists( $existingTerms, $term_id );
							if ( ! $isTermsExist ) {
								update_post_meta( $pdt_id, 'zi_category_id', $item['category_id'] );
								wp_add_object_terms( $pdt_id, $term_id, 'product_cat' );
							}
						} else {
							update_post_meta( $pdt_id, 'zi_category_id', $item['category_id'] );
							wp_set_object_terms( $pdt_id, $term_id, 'product_cat' );
						}
					}
				}

				// Map taxes while syncing product from zoho.
				if ( $item['tax_id'] ) {
					$zi_common_class = new ZI_CommonClass();
					$woo_tax_class   = $zi_common_class->get_woo_tax_class_from_zoho_tax_id( $item['tax_id'] );
					$simple_product->set_tax_status( 'taxable' );
					$simple_product->set_tax_class( $woo_tax_class );
				}
				$simple_product->save();
				wc_delete_product_transients( $pdt_id ); // Clear/refresh cache
			}
		}
		$response = new WP_REST_Response();
		$response->set_data( 'success on variable product' );
		$response->set_status( 200 );

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
		$item       = $inventory_adjustment;
		$line_items = $item['line_items'];
		// get first item from line items array
		$item_id        = $line_items[0]['item_id'];
		$adjusted_stock = $line_items[0]['quantity_adjusted'];

		$rowItem           = $wpdb->get_row( 'SELECT * FROM ' . $wpdb->prefix . "postmeta WHERE meta_key='zi_item_id' AND meta_value='" . $item_id . "'" );
		$mapped_product_id = $rowItem->post_id;

		if ( ! empty( $mapped_product_id ) ) {
			// stock
			$zi_stock_sync = get_option( 'zoho_stock_sync_status' );
			$product       = wc_get_product( $mapped_product_id );
			// Check if the product is in stock
			if ( 'true' !== $zi_stock_sync ) {
				if ( $product->is_in_stock() ) {
					// Get stock quantity
					$stock_quantity = $product->get_stock_quantity();
					$new_stock      = $stock_quantity + $adjusted_stock;
					$product->set_stock_quantity( $new_stock );
				} else {
					$product->set_stock_quantity( $adjusted_stock );
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
