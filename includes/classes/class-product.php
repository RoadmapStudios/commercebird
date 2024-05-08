<?php
/**
 * Class for All Product Data from Woo To Zoho
 *
 * @package  WooZo Inventory
 */

class ProductClass {


	public function __construct() {
		$this->config = array(
			'ProductZI' => array(
				'OID' => get_option( 'zoho_inventory_oid' ),
				'APIURL' => get_option( 'zoho_inventory_url' ),
			),
		);
	}

	public function zi_products_prepare_sync( $product_ids ) {

		if ( is_array( $product_ids ) ) {
			foreach ( $product_ids as $product_id ) {
				$this->zi_product_sync( $product_id );
			}
		}
	}

	/**
	 * Starting point to sync Product to Zoho Inventory. If product type is other than simple, pass the product_id to different function
	 *
	 * @param [type] $post_id
	 * @return void
	 */
	public function zi_product_sync( $post_id ) {

		// $fd = fopen(__DIR__.'/product_class.txt','a+');

		if ( is_array( $post_id ) ) {
			$product_id = intval( $post_id['0'] );
			$post_id = $product_id;
		}

		if ( 'publish' !== get_post_status( $post_id ) ) {
			return;
		}

		$product = wc_get_product( $post_id );
		if ( $product->is_type( 'bundle' ) ) {
			// fwrite($fd,PHP_EOL.'Inside bundle: ');
			$this->zi_bundle_product_to_zoho( $post_id );
		} elseif ( $product->is_type( 'variable' ) ) {
			// fwrite($fd,PHP_EOL.'Inside variable: ');
			$this->zi_variation_product_to_zoho( $post_id );
		} else {
			// fwrite($fd,PHP_EOL.'Inside Regular: ');
			// Simple product.
			$rate = $product->get_regular_price();
			// $rateS = $product->get_sale_price();
			/*
							  if ($rateS) {
							  $rate = $rateS;
							  } else {
							  $rate = $rateR;
							  } */
			// parse the name
			$pre_name = $product->get_name();
			$name = preg_replace( "/[>\"''<`]/", '', $pre_name );

			$sku = $product->get_sku();
			$stock_quantity = $product->get_stock_quantity();
			$in_stock = ( $stock_quantity > 0 ) ? $stock_quantity : 0;
			// fwrite($fd,PHP_EOL.'$product->get_stock_quantity() : '.$product->get_stock_quantity());
			$in_stock_rate = $in_stock * (int) $rate;

			$tax_rates = WC_Tax::get_base_tax_rates( $product->get_tax_class() );
			$tax_id_key = '';
			foreach ( $tax_rates as $tax_key => $tax_value ) {
				$tax_id_key = $tax_key;
				break;
			}
			$tax_option = get_option( 'zoho_inventory_tax_rate_' . $tax_id_key );
			$tax_id = explode( '##', $tax_option )[0];

			$zi_status = ( 'publish' === get_post_status( $post_id ) ) ? 'active' : 'inactive';
			// request data for adding/updating value to zoho.
			$zi_disable_itemname_sync = get_option( 'zoho_disable_name_sync_status' );
			$zoho_item_id = get_post_meta( $post_id, 'zi_item_id', true );

			$zidata = '';
			if ( empty( $zoho_item_id ) || 'true' != $zi_disable_itemname_sync ) {
				$zidata .= '"name" : "' . $name . '",';
			}

			if ( $product->is_virtual( 'yes' ) ) {
				$zidata .= '"product_type" : "service",';
				$zidata .= '"item_type" : "sales",';
			} else {
				$zidata .= '"product_type" : "goods",';
				$zidata .= '"item_type" : "inventory",';
			}

			$zidata .= '"sku" : "' . $sku . '",';
			// $zidata .= '"unit" : "pcs",';
			$zidata .= '"status" : "' . $zi_status . '",';
			// Initial stock update only if item sync for first time.
			if ( empty( $zoho_item_id ) ) {
				$zidata .= '"initial_stock" : ' . $in_stock . ',';
				$zidata .= '"initial_stock_rate" : "' . $in_stock_rate . '",';
			}
			$zidata .= '"rate" : "' . $rate . '",';
			$zidata .= '"tax_id" : "' . $tax_id . '",';
			//$zidata .= '"image_name" : "' . $image . '",';

			$dimensions = (object) array();
			$dimensions->length = $product->get_length();
			$dimensions->width = $product->get_width();
			$dimensions->height = $product->get_height();
			$dimensions->weight = $product->get_weight();
			$zidata .= '"package_details" : ' . wp_json_encode( $dimensions ) . ',';

			// Send category only if category ID available.
			$zi_category_id = $this->get_prod_updated_category( $post_id );
			if ( $zi_category_id ) {
				$zidata .= '"category_id" : "' . $zi_category_id . '"';
			}

			// $zidata .= '"image_type" : "' . $ext . '"';
			if ( ! empty( $zoho_item_id ) && ctype_digit( $zoho_item_id ) ) {
				// fwrite($fd,PHP_EOL.'Inside Update: ');
				$this->product_zoho_update_inventory_post( $post_id, $zoho_item_id, $zidata );
			} else {
				// fwrite($fd,PHP_EOL.'Inside Create ');
				$zoho_inventory_oid = $this->config['ProductZI']['OID'];
				$zoho_inventory_url = $this->config['ProductZI']['APIURL'];

				$data = array(
					'JSONString' => '{' . $zidata . '}',
					'organization_id' => $zoho_inventory_oid,
				);
				$url = $zoho_inventory_url . 'api/v1/items';

				$execute_curl_call_handle = new ExecutecallClass();
				$json = $execute_curl_call_handle->ExecuteCurlCallPost( $url, $data );

				$errmsg = $json->message;
				update_post_meta( $post_id, 'zi_product_errmsg', $errmsg );

				$code = $json->code;
				// fwrite($fd,PHP_EOL.'JSON Response : '.print_r($json,true));
				// Check if the the given sku has product at zoho inventory.
				if ( $code === '1001' || $code === 1001 ) {
					// fwrite($fd,PHP_EOL.'Inside SKU Check');
					$sku_check = str_replace( ' ', '+', $sku );
					$url = $zoho_inventory_url . 'api/v1/items?search_text=' . $sku_check . '&organization_id=' . $zoho_inventory_oid;
					$get_request = $execute_curl_call_handle->ExecuteCurlCallGet( $url );

					if ( $get_request->code === '0' || $get_request->code === 0 ) {
						$item_id = '';
						$matching_item = null;

						foreach ( $get_request->items as $zoho_item ) {
							if ( $zoho_item->sku === $sku ) {
								// SKU matched
								$matching_item = $zoho_item;
								break;
							}
						}

						// If SKU check didn't find a match, perform name check
						if ( ! $matching_item ) {
							$item_name_check = str_replace( ' ', '+', $name );
							$url = $zoho_inventory_url . 'api/v1/items?search_text=' . $item_name_check . '&organization_id=' . $zoho_inventory_oid;
							$get_request = $execute_curl_call_handle->ExecuteCurlCallGet( $url );

							if ( $get_request->code === '0' || $get_request->code === 0 ) {
								foreach ( $get_request->items as $zoho_item ) {
									if ( $zoho_item->name === $name ) {
										// Name matched
										$matching_item = $zoho_item;
										break;
									}
								}
							}
						}

						if ( $matching_item ) {
							$code = 0;
							$json->item = $matching_item;
							update_post_meta( $post_id, 'zi_product_errmsg', 'Product "' . $matching_item->name . '" is mapped successfully with Zoho' );
						}
					}
				}
				// fwrite($fd,PHP_EOL.'After SKU Check : code '.$code);
				if ( $code == '0' || $code == 0 ) {
					foreach ( $json->item as $key => $value ) {
						if ( $key == 'item_id' ) {
							$item_id = $value;
						}
						if ( $key == 'purchase_account_id' ) {
							$purchase_account_id = $value;
						}
						if ( $key == 'account_id' ) {
							$account_id = $value;
						}
						if ( $key == 'account_name' ) {
							$account_name = $value;
						}
						if ( $key == 'inventory_account_id' ) {
							$inventory_account_id = $value;
						}
						if ( $key == 'category_id' && ! empty( $value ) ) {
							update_post_meta( $post_id, 'zi_category_id', $value );
						}
					}
					update_post_meta( $post_id, 'zi_item_id', $item_id );
					update_post_meta( $post_id, 'zi_purchase_account_id', $purchase_account_id );
					update_post_meta( $post_id, 'zi_account_id', $account_id );
					update_post_meta( $post_id, 'zi_account_name', $account_name );
					update_post_meta( $post_id, 'zi_inventory_account_id', $inventory_account_id );
				}
			}
		} // loop end
		// return;
		// fclose($fd);
	}

	/**
	 * Function to update zoho item if already exists.
	 *
	 * @param number $proid - product number.
	 * @param number $item_id - zoho item id.
	 * @param mixed  $pdt3 - Zoho item object for post request.
	 * @return string
	 */
	protected function product_zoho_update_inventory_post( $proid, $item_id, $pdt3, $bundle = '' ) {
		// $fd = fopen(__DIR__.'/product_class.txt','a+');
		// fwrite($fd,PHP_EOL.'Inside update : ');
		$errmsg = '';
		$zoho_inventory_oid = $this->config['ProductZI']['OID'];
		$zoho_inventory_url = $this->config['ProductZI']['APIURL'];

		$url = $zoho_inventory_url . 'api/v1/items/' . $item_id;
		// fwrite($fd,PHP_EOL.'JSON Data : '.'{' . $pdt3 . '}');
		$data = array(
			'JSONString' => '{' . $pdt3 . '}',
			'organization_id' => $zoho_inventory_oid,
		);

		$execute_curl_call_handle = new ExecutecallClass();
		$json = $execute_curl_call_handle->ExecuteCurlCallPut( $url, $data );
		// fwrite($fd,PHP_EOL.'Update response : '.print_r($json,true));
		$code = $json->code;
		$errmsg = $json->message;
		update_post_meta( $proid, 'zi_product_errmsg', $errmsg );
		// fclose($fd);
		return $errmsg;
	}

	/**
	 * Create Add And Update Bundle producttype
	 */
	protected function zi_get_bundle_item_meta_data( $bundle_item_id, $bundle_id, $meta_key = '' ) {
		global $wpdb;
		$table_meta = $wpdb->prefix . 'woocommerce_bundled_itemmeta';
		$table_item = $wpdb->prefix . 'woocommerce_bundled_items';
		if ( '' !== $meta_key ) {
			$meta_key = "AND meta_key='$meta_key'";
		}
		$metadata = $wpdb->get_results( "SELECT meta_key, meta_value FROM $table_meta, $table_item WHERE $table_meta.bundled_item_id = $table_item.bundled_item_id AND product_id=$bundle_item_id AND bundle_id = $bundle_id $meta_key" );

		if ( count( $metadata ) > 0 ) {
			return $metadata;
		} else {
			false;
		}
	}

	protected function zi_bundle_product_data_zoho( $bundle_id ) {
		// $fd = fopen(__DIR__ . '/zi_bundle_product_data_zoho.txt', 'w+');

		$bundled_product = new WC_Product_Bundle( $bundle_id );
		$bundle_childs = $bundled_product->get_bundled_items();

		// Allow Bundle Product
		$child_array = array();
		foreach ( $bundle_childs as $child ) {
			$parent_product = $child->product;
			$child_id = $child->product_id;
			$meta_value = $this->zi_get_bundle_item_meta_data( $child_id, $bundle_id, 'quantity_max' );

			$zi_child_ids = array(); // Array to store zi_child_ids

			if ( $parent_product->is_type( 'variable' ) ) {
				$meta_data = $this->zi_get_bundle_item_meta_data( $child_id, $bundle_id, 'allowed_variations' );

				foreach ( $meta_data as $meta ) {
					if ( $meta->meta_key === 'allowed_variations' ) {
						$serialized_value = $meta->meta_value;
						$deserialized_value = maybe_unserialize( $serialized_value );

						if ( is_array( $deserialized_value ) ) {
							foreach ( $deserialized_value as $variation_id ) {
								$zi_variation_id = get_post_meta( $variation_id, 'zi_item_id', true );
								if ( $zi_variation_id ) {
									$zi_child_ids[] = $zi_variation_id;
								}
							}
						}
					}
				}
			} else {
				$zi_child_id = get_post_meta( $child_id, 'zi_item_id', true );
				if ( $zi_child_id ) {
					$zi_child_ids[] = $zi_child_id;
				}
			}

			foreach ( $zi_child_ids as $zi_child_id ) {
				$json_child = (object) array(
					'item_id' => $zi_child_id,
					'quantity' => $meta_value[0]->meta_value,
				);
				array_push( $child_array, $json_child );
			}
		}
		$child_items = $child_array;

		// fclose($fd);
		return $child_items;
	}

	protected function zi_bundle_product_to_zoho( $post_id ) {
		// $fd = fopen(__DIR__ . '/zi_bundle_product_to_zoho.txt', 'w+');

		$item = wc_get_product( $post_id );
		if ( $item->is_type( 'bundle' ) ) {

			$child_items = $this->zi_bundle_product_data_zoho( $post_id );
		}

		$price_r = $item->get_regular_price();
		$price_s = $item->get_sale_price();

		if ( $price_s ) {
			$rate = round( $price_s, 2 );
		} else {
			$rate = round( $price_r, 2 );
		}
		//$rate = 500;
		// $proid = $item->ID;
		$pre_name = $item->get_name();
		$name = preg_replace( "/[>\"''<`]/", '', $pre_name );
		$sku = $item->get_sku();
		$stock_quantity = $item->get_stock_quantity();
		$in_stock = ( $stock_quantity > 0 ) ? $stock_quantity : 0;
		$in_stock_rate = ( $in_stock * $rate );

		$product_type = 'goods';
		$item_type = 'inventory';
		$tax_rates = WC_Tax::get_base_tax_rates( $item->get_tax_class() );
		$tax_id_key = '';
		foreach ( $tax_rates as $tax_key => $tax_value ) {
			$tax_id_key = $tax_key;
			break;
		}
		$tax_option = get_option( 'zoho_inventory_tax_rate_' . $tax_id_key );
		$tax_id = explode( '##', $tax_option )[0];
		if ( ! empty( $tax_rates ) ) {
			$tax_rate = reset( $tax_rates );
		}

		$pdt1 = '"name" : "' . $name . '","mapped_items":' . wp_json_encode( $child_items ) . ', "product_type" : "' . $product_type . '","tax_id" : "' . $tax_id . '","rate" : "' . $rate . '","sku" : "' . $sku . '","item_type" : "' . $item_type . '"';
		// If zoho category id is not mapped to product, then assign mapped product category with zoho.

		// $zi_category_id = $this->get_prod_updated_category($post_id);
		// if ($zi_category_id) {
		//     $pdt1 .= ',"category_id" : "' . $zi_category_id . '"';
		// }

		$zoho_item_id = get_post_meta( $post_id, 'zi_item_id', true );
		if ( empty( $zoho_item_id ) ) {
			$pdt1 .= ',"initial_stock" : ' . $in_stock . ',';
			$pdt1 .= '"initial_stock_rate" : "' . $in_stock_rate . '"';
		}

		// Dimensions data append to update call.
		$dimensions = (object) array();
		$dimensions->length = $item->get_length();
		$dimensions->width = $item->get_width();
		$dimensions->height = $item->get_height();
		$dimensions->weight = $item->get_weight();
		$pdt1 .= ',"package_details" : ' . wp_json_encode( $dimensions ) . ',';

		$zoho_inventory_oid = $this->config['ProductZI']['OID'];
		$zoho_inventory_url = $this->config['ProductZI']['APIURL'];

		if ( $zoho_item_id && ctype_digit( $zoho_item_id ) ) {
			$url_p = $zoho_inventory_url . 'api/v1/compositeitems/' . $zoho_item_id;
		} else {
			$url_p = $zoho_inventory_url . 'api/v1/compositeitems';
		}

		$data_p = array(
			'JSONString' => '{' . $pdt1 . '}',
			'organization_id' => $zoho_inventory_oid,
		);

		// fwrite($fd, PHP_EOL . 'data_p : ' . print_r($data_p, true));

		$execute_curl_call_handle = new ExecutecallClass();

		if ( $zoho_item_id && ctype_digit( $zoho_item_id ) ) {

			$json = $execute_curl_call_handle->ExecuteCurlCallPut( $url_p, $data_p );
			$errmsg = $json->message;
			update_post_meta( $post_id, 'zi_product_errmsg', $errmsg );
		} else {

			$json = $execute_curl_call_handle->ExecuteCurlCallPost( $url_p, $data_p );

			$code = $json->code;
			$errmsg = $json->message;
			update_post_meta( $post_id, 'zi_product_errmsg', $errmsg );
			if ( $code == '1001' || $code == 1001 ) {
				$sku_check = str_replace( ' ', '+', $sku );
				$url = $zoho_inventory_url . 'api/v1/compositeitems/?search_text=' . $sku_check . '&organization_id=' . $zoho_inventory_oid;
				$get_request = $execute_curl_call_handle->ExecuteCurlCallGet( $url );
				if ( $get_request->code === '0' || $get_request->code === 0 ) {
					$item_id = '';
					foreach ( $get_request->composite_items as $zoho_composite ) {
						// fwrite($fd,PHP_EOL.'ZOHO Item : '.print_r($zoho_item, true));
						if ( $zoho_composite->sku === $sku ) {
							$code = 0;
							$json->composite_item = $zoho_composite;
							update_post_meta( $post_id, 'zi_product_errmsg', 'Product "' . $zoho_composite->name . '" is mapped successfully with Zoho' );
							break;
						}
					}
				}
			}
			if ( $code === '0' || $code === 0 ) {
				foreach ( $json->composite_item as $key => $value ) {

					if ( $key === 'composite_item_id' ) {
						$item_id = $value;
					}
					if ( $key === 'purchase_account_id' ) {
						$purchase_account_id = $value;
					}
					if ( $key === 'account_id' ) {
						$account_id = $value;
					}
					if ( $key === 'account_name' ) {
						$account_name = $value;
					}
					if ( $key === 'inventory_account_id' ) {
						$inventory_account_id = $value;
					}
					if ( $key === 'category_id' && ! empty( $value ) ) {
						update_post_meta( $post_id, 'zi_category_id', $value );
					}
				}
				update_post_meta( $post_id, 'zi_item_id', $item_id );
				update_post_meta( $post_id, 'zi_purchase_account_id', $purchase_account_id );
				update_post_meta( $post_id, 'zi_account_id', $account_id );
				update_post_meta( $post_id, 'zi_account_name', $account_name );
				update_post_meta( $post_id, 'zi_inventory_account_id', $inventory_account_id );
			}
		}
		// fclose($fd);
	}

	//variation product post zoho start

	protected function zi_variation_product_to_zoho( $post_id ) {
		// $fd = fopen(__DIR__ . '/zi_variation_product_to_zoho.txt', 'w+');

		$product = wc_get_product( $post_id );

		$pre_name = $product->get_title();
		$name = preg_replace( "/[>\"''<`]/", '', $pre_name );

		$tax_rates = WC_Tax::get_base_tax_rates( $product->get_tax_class() );
		$tax_id_key = '';
		foreach ( $tax_rates as $tax_key => $tax_value ) {
			$tax_id_key = $tax_key;
			break;
		}
		$tax_option = get_option( 'zoho_inventory_tax_rate_' . $tax_id_key );
		$tax_id = explode( '##', $tax_option )[0];
		$zi_category_id = $this->get_prod_updated_category( $post_id );

		$zidata = '"group_name" : "' . $name . '", "tax_id" : "' . $tax_id . '","category_id" : "' . $zi_category_id . '",';

		// attributes
		$attributes = $product->get_attributes();
		// fwrite($fd, PHP_EOL . 'ATTRIBUTES : ' . print_r($attributes, true));

		$attribute_name1 = '';
		$attribute_name2 = '';
		$attribute_name3 = '';
		foreach ( $attributes as $attribute ) {
			if ( ! empty( $attribute ) ) {
				$attrname1 = $attribute->get_name();
				$attrname = str_replace( '"', '', $attrname1 );
				if ( ! empty( $attrname ) && $attribute['variation'] ) {
					if ( empty( $attribute_name1 ) ) {
						$attribute_name1 = $attrname;
					} elseif ( empty( $attribute_name2 ) ) {
						$attribute_name2 = $attrname;
					} elseif ( empty( $attribute_name3 ) ) {
						$attribute_name3 = $attrname;
					}
				}
			}
		}
		if ( ! empty( $attribute_name1 ) ) {
			$zidata .= '"attribute_name1": "' . $attribute_name1 . '",';
		}
		if ( ! empty( $attribute_name2 ) ) {
			$zidata .= '"attribute_name2": "' . $attribute_name2 . '",';
		}
		if ( ! empty( $attribute_name3 ) ) {
			$zidata .= '"attribute_name3": "' . $attribute_name3 . '",';
		}

		$available_variations = $product->get_available_variations();
		// If there is attributes variations then append that data to server.
		$items = array();
		if ( count( $available_variations ) > 0 ) {
			foreach ( $available_variations as $child_data ) {

				$product_variable = wc_get_product( $child_data['variation_id'] );
				$items[] = $this->variants_products( $product_variable, $child_data['variation_id'], $attribute_name1, $attribute_name2, $attribute_name3 );
			}
		}

		// get category id
		// $zi_category_id = $this->get_prod_updated_category($post_id);
		// if ($zi_category_id) {
		//     $zidata .= '"category_id" : "' . $zi_category_id . '",';
		// }

		$zidata .= '"items" :[' . implode( ',', $items ) . ']';
		$data = array(
			'JSONString' => '{' . $zidata . '}',
		);

		// fwrite($fd, PHP_EOL . 'ZI Data JSON : ' . '{' . print_r($data, true) . '}');
		// fclose($fd);

		$zoho_inventory_oid = $this->config['ProductZI']['OID'];
		$zoho_inventory_url = $this->config['ProductZI']['APIURL'];
		$zoho_group_id = get_post_meta( $post_id, 'zi_item_id', true );

		if ( ! empty( $zoho_group_id ) ) {
			$url = $zoho_inventory_url . 'api/v1/itemgroups/' . $zoho_group_id . '?organization_id=' . $zoho_inventory_oid;
			$execute_curl_call_handle = new ExecutecallClass();
			$json_p = $execute_curl_call_handle->ExecuteCurlCallPut( $url, $data );
			$code = $json_p->code;
			$errmsg = $json_p->message;
			update_post_meta( $post_id, 'zi_product_errmsg', $errmsg );
		} else {
			$url = $zoho_inventory_url . 'api/v1/itemgroups?organization_id=' . $zoho_inventory_oid;

			$execute_curl_call_handle = new ExecutecallClass();
			$json = $execute_curl_call_handle->ExecuteCurlCallPost( $url, $data );

			$errmsg = $json->message;
			update_post_meta( $post_id, 'zi_product_errmsg', $errmsg );
			$code = $json->code;
			if ( $code == '0' || $code == 0 ) {

				// This item will keep the copy of zoho item_id with respect to product.
				//  name as key synced to zoho.
				$child_items = array();
				foreach ( $json->item_group as $key => $value ) {
					if ( $key == 'group_id' ) {
						$group_id = $value;
					}

					if ( $key === 'items' ) {
						foreach ( $value as $key2 => $val2 ) {
							$zi_name = str_replace( ' ', '-', $val2->name );
							//    echo '<br>';
							$zi_name = $val2->name;
							$child_items[ $zi_name ] = $val2->item_id;
						}
					}
				}
				if ( ! empty( $group_id ) ) {
					update_post_meta( $post_id, 'zi_item_id', $group_id );
				}

				foreach ( $available_variations as $child_data ) {
					$product_variable = wc_get_product( $child_data['variation_id'] );

					$pname = '';
					foreach ( $product_variable->get_variation_attributes() as $taxonomy => $terms_slug ) {

						$pname .= $terms_slug;
					}

					$vname = $product_variable->get_name();
					$product_key = $vname . '-' . $pname;
					update_post_meta( $child_data['variation_id'], 'zi_item_id', $child_items[ $product_key ] );
				}
			}
		}
		// End New Variable Product
	}

	/**
	 * Function to sync variations of a product.
	 *
	 * @param  $post_id        - variation product_id.
	 * @param  $zi_category_id - Zoho category id of parent of a variation.
	 * @return void
	 */
	protected function variants_products( $product_variable, $post_id, $attr1 = '', $attr2 = '', $attr3 = '' ) {
		// $post_id = $product_variable->ID;
		// $fd = fopen(__DIR__.'/variations_products.txt','a+');
		// fwrite($fd,PHP_EOL.'-------------------------------');
		// fwrite($fd,PHP_EOL.'$attr1 : '.$attr1.' | $attr2 : '.$attr2.' | $attr3 : '.$attr3.' $post_id : '.$post_id);
		// Sync Attributes of Variable Products

		$attributes = $product_variable->get_variation_attributes();
		// fwrite($fd,PHP_EOL.'$variation_attributes : '.print_r($attributes,true));
		$arrtibute_string = '';
		if ( ! empty( $attr1 ) ) {
			$attr_key = strtolower( $attr1 );
			$attr_key = 'attribute_' . str_replace( ' ', '-', $attr_key );
			$arrtibute_string .= '"attribute_option_name1": "' . str_replace( '"', '', $attributes[ $attr_key ] ) . '",';
		}
		if ( ! empty( $attr2 ) ) {
			$attr_key = strtolower( $attr2 );
			$attr_key = 'attribute_' . str_replace( ' ', '-', $attr_key );
			$arrtibute_string .= '"attribute_option_name2": "' . str_replace( '"', '', $attributes[ $attr_key ] ) . '",';
		}
		if ( ! empty( $attr3 ) ) {
			$attr_key = strtolower( $attr3 );
			$attr_key = 'attribute_' . str_replace( ' ', '-', $attr_key );
			$arrtibute_string .= '"attribute_option_name3": "' . str_replace( '"', '', $attributes[ $attr_key ] ) . '",';
		}
		// fwrite($fd,PHP_EOL.'$arrtibute_string : '.$arrtibute_string);
		// fclose($fd);
		$zoho_item_id = get_post_meta( $post_id, 'zi_item_id', true );

		// $product_variable      = wc_get_product($post_id);
		$pname = '';
		foreach ( $product_variable->get_variation_attributes() as $taxonomy => $terms_slug ) {

			$pname .= $terms_slug;
		}

		$vname = $product_variable->get_name();
		$name = str_replace( '"', '', $vname );
		$rate = $product_variable->get_regular_price();
		// $rateS = $product_variable->get_sale_price();
		if ( $product_variable->is_virtual( 'yes' ) ) {
			$product_type = 'service';
			$item_type = 'sales';
		} else {
			$product_type = 'goods';
			$item_type = 'inventory';
		}

		$sku = $product_variable->get_sku();
		$stock_quantity = $product_variable->get_stock_quantity();
		$in_stock = ( $stock_quantity > 0 ) ? $stock_quantity : 0;

		/*
					if ($rateS) {
					$rate = $rateS;
					} else {
					$rate = $rateR;
					} */

		// TODO: get tax rates from Zoho API.
		$tax_rates = WC_Tax::get_base_tax_rates( $product_variable->get_tax_class() );
		$tax_id_key = '';
		foreach ( $tax_rates as $tax_key => $tax_value ) {
			$tax_id_key = $tax_key;
			break;
		}
		$tax_option = get_option( 'zoho_inventory_tax_rate_' . $tax_id_key );
		$tax_id = explode( '##', $tax_option )[0];

		$zi_status = ( 'publish' === get_post_status( $post_id ) ) ? 'active' : 'inactive';
		// request data for adding/updating value to zoho.
		$zidata = '';
		if ( ! empty( $arrtibute_string ) ) {
			$zidata .= $arrtibute_string;
		}
		$zidata .= '"name" : "' . $name . '",';
		$zidata .= '"product_type" : "' . $product_type . '",';
		$zidata .= '"sku" : "' . $sku . '",';
		$zidata .= '"item_type" : "' . $item_type . '",';
		// $zidata .= '"unit" : "pcs",';
		$zidata .= '"status" : "' . $zi_status . '",';
		if ( empty( $zoho_item_id ) && $in_stock > 0 ) {
			// $zidata .= '"purchase_rate" : "1",';
			$zidata .= '"initial_stock" : ' . $in_stock . ',';
			$zidata .= '"initial_stock_rate" : ' . $in_stock . ',';
		}
		$zidata .= '"rate" : "' . $rate . '",';
		$zidata .= '"tax_id" : "' . $tax_id . '",';

		$dimensions = (object) array();
		$dimensions->length = $product_variable->get_length();
		$dimensions->width = $product_variable->get_width();
		$dimensions->height = $product_variable->get_height();
		$dimensions->weight = $product_variable->get_weight();
		if ( ! empty( $dimensions ) ) {
			$zidata .= '"package_details" : ' . wp_json_encode( $dimensions );
		}

		// $fd = fopen(__DIR__ . '/variations.txt', 'a+');
		// fwrite($fd,PHP_EOL.'Get data for $post_id '.$post_id);
		if ( ctype_digit( $zoho_item_id ) ) {
			// fwrite($fd, PHP_EOL . 'Update Item');
			$update_error_msg = $this->product_zoho_update_inventory_post( $post_id, $zoho_item_id, $zidata );
			$zidataa = '';
			// fwrite($fd, PHP_EOL . '{' . $zidata . '}');
			return $zidataa .= '{' . $zidata . '}';
		} else {
			// fwrite($fd, PHP_EOL . 'Create Item');
			// Check if the the given sku has product in zoho inventory.
			$zoho_inventory_oid = $this->config['ProductZI']['OID'];
			$zoho_inventory_url = $this->config['ProductZI']['APIURL'];
			$sku_check = str_replace( ' ', '+', $sku );
			$url = $zoho_inventory_url . 'api/v1/items?search_text=' . $sku_check . '&organization_id=' . $zoho_inventory_oid;
			$execute_curl_call_handle = new ExecutecallClass();
			$get_request = $execute_curl_call_handle->ExecuteCurlCallGet( $url );
			$var_item_id = '';
			$groupitem_id = '';
			// fwrite($fd, PHP_EOL . '$get_request->code : ' . $get_request->code);
			if ( $get_request->code === '0' || $get_request->code === 0 ) {
				foreach ( $get_request->items as $zoho_item ) {
					// fwrite($fd, PHP_EOL . '$zoho_item->sku : ' . $zoho_item->sku);
					if ( $zoho_item->sku === $sku ) {
						// fwrite($fd, PHP_EOL . 'Product found with same sku $zoho_item : ' . print_r($zoho_item, true));
						$var_item_id = $zoho_item->item_id;
						$groupitem_id = $zoho_item->group_id;
						// Item sku is mached
						// Assign mached zoho item to json so fields can be mapped.
						break;
					}
				}
			}
			$zidataa = '';
			if ( $var_item_id ) {
				update_post_meta( $post_id, 'zi_item_id', $var_item_id );
			}
			if ( $groupitem_id ) {
				$parent_product_id = wp_get_post_parent_id( $post_id );
				update_post_meta( $parent_product_id, 'zi_item_id', $groupitem_id );
			}
			// fwrite($fd, PHP_EOL . '$var_item_id : ' . $var_item_id);
			// fclose($fd);
			return $zidataa .= '{' . $zidata . '}';
		}
	}

	/**
	 * Check if category already exists and return updated one
	 */
	protected function get_prod_updated_category( $product_id ) {
		// Check if product category already synced.
		$terms = get_the_terms( $product_id, 'product_cat' );
		if ( $terms ) {
			foreach ( $terms as $term ) {
				$product_cat_id = $term->term_id;
				$zoho_cat_id = get_option( "zoho_id_for_term_id_{$product_cat_id}" );
				if ( $zoho_cat_id ) {
					break;
				}
			}
		}
		// Check if product has already mapped category.
		if ( empty( $zoho_cat_id ) ) {
			$zoho_cat_id = get_post_meta( $product_id, 'zi_category_id', true );
		}

		if ( $zoho_cat_id ) {
			return $zoho_cat_id;
		} else {
			return false;
		}
	}

	/**
	 * Update product name in zoho
	 * TODO: perhaps remove this
	 */
	public function update_product_name( $product_id, $product_name ) {
		$zi_disable_itemname_sync = get_option( 'zoho_disable_name_sync_status' );
		if ( ! $zi_disable_itemname_sync ) {
			$name_update = array(
				'ID' => $product_id,
				'post_title' => $product_name,
				'post_name' => $this->zi_convert_itemname( $product_name ),
			);
			$update_resp = wp_update_post( $name_update, false );
			if ( is_wp_error( $update_resp ) ) {
				$error_string = $update_resp->get_error_message();
				return $error_string;
			}
		}
	}

	/**
	 * Create seo-friendly post_name
	 */
	private function zi_convert_itemname( $item_name ) {
		//Lower case everything
		$item_name = strtolower( $item_name );
		//Make alphanumeric (removes all other characters)
		$item_name = preg_replace( '/[^a-z0-9_\s-]/', '', $item_name );
		//Clean up multiple dashes or whitespaces
		$item_name = preg_replace( '/[\s-]+/', ' ', $item_name );
		//Convert whitespaces and underscore to dash
		$item_name = preg_replace( '/[\s_]/', '-', $item_name );
		return $item_name;
	}

	/**
	 * Function for adding Simple product from Zoho to woocommerce.
	 *
	 * @param $prod - Product object for adding new product in woocommerce.
	 * @param $user_id - Current Active user Id
	 * @param string $type - product is composite item or not (composite)
	 */
	public function zi_product_to_woocommerce( $item, $item_stock = '', $type = '' ) {
		// $fd = fopen( __DIR__ . '/zi_product_to_woocommerce.txt', 'a+' );
		try {
			if ( $item['status'] == 'active' ) {
				$status = 'publish';
			} else {
				return;
			}
			$product = new WC_Product();

			$allow_backorders = get_option( 'woocommerce_allow_backorders' );
			$zi_stock_sync = get_option( 'zoho_stock_sync_status' );

			// Set the product data
			$product->set_status( $status );
			$product->set_name( $item['name'] );
			$product->set_regular_price( $item['rate'] );
			$product->set_short_description( $item['description'] );
			$product->set_sku( $item['sku'] );

			// Set the stock management properties
			if ( ! empty( $item_stock ) && ! $zi_stock_sync ) {
				$product->set_manage_stock( true );
				$product->set_stock_quantity( $item_stock );

				if ( $item_stock > 0 ) {
					$product->set_stock_status( 'instock' );
				} elseif ( $item_stock < 0 && $allow_backorders === 'yes' ) {
					$product->set_stock_status( 'onbackorder' );
				} else {
					$product->set_stock_status( 'outofstock' );
				}
			}

			// Save the product
			$product_id = $product->save();

			// Map composite items metadata to convert product as a bundle product.
			if ( 'composite' === $type ) {
				update_post_meta( $product_id, '_wc_pb_layout_style', 'default' );
				update_post_meta( $product_id, '_wc_pb_add_to_cart_form_location', 'default' );
				wp_set_object_terms( $product_id, 'bundle', 'product_type' );
			}

			return $product_id;
		} catch (Exception $e) {
			// Handle the exception, log it, or perform any necessary actions.
			error_log( 'Error creating WooCommerce product: ' . $e->getMessage() );
			return false;
		}
	}
}
$productClass = new ProductClass();
