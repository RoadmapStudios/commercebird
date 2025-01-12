<?php
/**
 * Class to import categories from Zoho Inventory to WooCommerce and vice versa.
 *
 * @package  zoho_inventory_api
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class CMBIRD_Categories_ZI {

	private $config;
	public function __construct() {
		$this->config = array(
			'ConnectZI' => array(
				'OID' => get_option( 'cmbird_zoho_inventory_oid' ),
				'APIURL' => get_option( 'cmbird_zoho_inventory_url' ),
			),
		);
		add_action( 'wp_ajax_zoho_ajax_call_category', array( $this, 'cmbird_zi_category_sync_call' ) );
		add_action( 'wp_ajax_zoho_ajax_call_subcategory', array( $this, 'cmbird_zi_subcategory_sync_call' ) );
	}

	/**
	 * Create response object based on data.
	 *
	 * @param mixed $index_col - Index value error message.
	 * @param string $message - Response message.
	 *
	 * @return object
	 */
	private function cmbird_zi_response_message( $index_col, $message, $woo_id = '' ) {
		return (object) array(
			'resp_id' => $index_col,
			'message' => $message,
			'woo_prod_id' => $woo_id,
		);
	}

	/**
	 * Get Term ID from Zoho category ID
	 *
	 * @return string - Term ID
	 */

	private function cmbird_subcategories_term_id( $option_value ) {
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

	/**
	 * Get all categories from Zoho Inventory.
	 *
	 * @return bool | string
	 */
	public function cmbird_zi_category_sync_call() {
		// $fd = fopen( __DIR__ . '/cmbird_zi_category_sync_call.txt', 'a+' );

		$response = array(); // Response array.
		$zoho_categories = $this->cmbird_get_zoho_item_categories();
		// fwrite( $fd, PHP_EOL . 'categories: ' . print_r( $zoho_categories, true ) );
		// Import category from zoho to woocommerce.
		$response[] = $this->cmbird_zi_response_message( '-', '-', '--- Importing Category from zoho ---' );

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
							$response[] = $this->cmbird_zi_response_message( $category['category_id'], $term->get_error_message(), '-' );
						} else {
							$term_id = $term['term_id'];
						}
					}
					if ( $term_id ) {
						// Update zoho category id for term(category) of woocommerce.
						update_option( 'cmbird_zoho_id_for_term_id_' . $term_id, $category['category_id'] );
					}
					$response[] = $this->cmbird_zi_response_message( $category['category_id'], $category['name'], $term_id );
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
		$response[] = $this->cmbird_zi_response_message( '-', '-', $log_head );
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
					$response[] = $this->cmbird_zi_response_message( $zoho_cat_id, 'Category name : "' . $term->name . '" already synced with zoho', $term->term_id );
				}
			}
		} else {
			$response[] = $this->cmbird_zi_response_message( '-', 'Categories not available to export', '-' );
		}
		$encoded_response = wp_json_encode( $response );
		// fwrite( $fd, PHP_EOL . 'Final Response : ' . print_r( $encoded_response, true ) );
		// fclose( $fd );

		return $encoded_response;
	}

	public function cmbird_get_zoho_item_categories() {
		// $fd = fopen( __DIR__ . '/cmbird_get_zoho_item_categories.txt', 'a+' );

		$zoho_inventory_oid = $this->config['ConnectZI']['OID'];
		$zoho_inventory_url = $this->config['ConnectZI']['APIURL'];

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

	public function cmbird_zi_subcategory_sync_call() {
		// $fd = fopen( __DIR__ . '/ajax_subcategory_sync_call.txt', 'a+' );
		$response = array(); // Response array.
		$zoho_subcategories = cmbird_get_zoho_item_categories();
		// Import category from zoho to woocommerce.
		$response[] = $this->cmbird_zi_response_message( '-', '-', '--- Importing Sub Category from zoho ---' );
		//echo '<pre>'; print_r($zoho_categories);
		foreach ( $zoho_subcategories as $subcategory ) {
			if ( $subcategory['parent_category_id'] > 0 ) {
				if ( '-1' !== $subcategory['category_id'] && $subcategory['category_id'] > 0 ) {
					$term = get_term_by( 'name', $subcategory['name'], 'product_cat' );

					if ( $subcategory['parent_category_id'] > 0 ) {
						$zoho_pid = intval( $this->cmbird_subcategories_term_id( $subcategory['parent_category_id'] ) );
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
							$response[] = $this->cmbird_zi_response_message( $subcategory['category_id'], $child_term->get_error_message(), '-' );
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
					$response[] = $this->cmbird_zi_response_message( $subcategory['category_id'], $subcategory['name'], $term_id );
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
		$response[] = $this->cmbird_zi_response_message( '-', '-', $log_head );
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
							$response[] = $this->cmbird_zi_response_message( $zoho_cat_id, 'Sub Category name : "' . $term->name . '" already synced with zoho', $term->term_id );
						}
						++$c;
					}
				}
			}
		}
		// fwrite( $fd, PHP_EOL . 'Sub Categories : ' . print_r( $response, true ) );
		// fclose( $fd );

		if ( 0 === $c ) {
			$response[] = $this->cmbird_zi_response_message( '-', 'Sub Categories not available to export', '-' );
		}
		return wp_json_encode( $response );
	}

	/**
	 * Create woocommerce category in zoho inventory.
	 *
	 * @return boolean - true if category created successfully.
	 */
	public function cmbird_zi_category_export( $cat_name, $term_id = '0', $pid = '' ) {

		$zoho_inventory_oid = $this->config['ConnectZI']['OID'];
		$zoho_inventory_url = $this->config['ConnectZI']['APIURL'];

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
		$return = $this->cmbird_zi_response_message( $code, $response_msg, $term_id );
		return wp_json_encode( $return );
	}
}
