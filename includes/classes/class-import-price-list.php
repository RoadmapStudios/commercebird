<?php
/**
 * Class to import Pricelists from Zoho to WooCommerce using B2B for WooCommerce
 *
 * @package  WooZo Inventory
 */

class ImportPricelistClass {

	private array $config;

	public function __construct() {
		$this->config = array(
			'ProductZI' => array(
				'OID'    => get_option( 'zoho_inventory_oid' ),
				'APIURL' => get_option( 'zoho_inventory_url' ),
			),
		);
	}

	public function zi_get_all_pricelist() {
		$in_cache = get_transient( 'zoho_pricelist' );
		if ( $in_cache ) {
			return $in_cache;
		}

		$url                   = $this->config['ProductZI']['APIURL'] . 'api/v1/pricebooks?organization_id=' . $this->config['ProductZI']['OID'];
		$executeCurlCallHandle = new ExecutecallClass();
		$json                  = $executeCurlCallHandle->ExecuteCurlCallGet( $url );

		if ( isset( $json->pricebooks ) ) {
			set_transient( 'zoho_pricelist', $json->pricebooks, MINUTE_IN_SECONDS );
			return $json->pricebooks;
		}

		return array();
	}


	public function get_zi_pricelist( $pricebook_id ) {
		$url                   = $this->config['ProductZI']['APIURL'] . 'api/v1/pricebooks/' . $pricebook_id . '?organization_id=' . $this->config['ProductZI']['OID'];
		$executeCurlCallHandle = new ExecutecallClass();
		$json                  = $executeCurlCallHandle->ExecuteCurlCallGet( $url );
		return json_decode( json_encode( $json ), true );
	}

	/**
	 * Apply Zoho price list to products.
	 *
	 * @param array $post The post data.
	 * @return array The updated price lists.
	 */
	public function apply_zoho_pricelist( array $post ): array {
		$pricelist_id   = $post['zoho_inventory_pricelist'];
		$data           = $this->get_zi_pricelist( $pricelist_id );
		$pricebook_type = isset( $data['pricebook']['pricebook_type'] ) ? $data['pricebook']['pricebook_type'] : '';

		update_option( 'zoho_pricelist_id', $pricelist_id );

		$newpricelists = array();

		if ( ! empty( $data ) ) {
			if ( $pricebook_type === 'fixed_percentage' ) {
				$percentage = isset( $data['pricebook']['percentage'] ) ? $data['pricebook']['percentage'] : 0;
				$products   = wc_get_products( array( 'status' => 'publish' ) );

				foreach ( $products as $product ) {
					$zi_item_id = intval( get_post_meta( $product->get_id(), 'zi_item_id', true ) );
					if ( $zi_item_id > 0 ) {
						$newpricelists['ids'][ $zi_item_id ] = $percentage;
					}
				}

				$is_increase              = isset( $data['pricebook']['is_increase'] ) ? $data['pricebook']['is_increase'] : false;
				$newpricelists['orderby'] = $is_increase ? 'percentage_increase' : 'percentage_decrease';
			} else {
				$newpricelists['orderby'] = 'fixed_price';

				foreach ( $data['pricebook']['pricebook_items'] as $itemlist ) {
					$item_id = isset( $itemlist['item_id'] ) ? $itemlist['item_id'] : 0;

					if ( is_array( $itemlist['price_brackets'] ) && ! empty( $itemlist['price_brackets'] ) ) {
						$priceBracket = $itemlist['price_brackets'][0];

						$newpricelists['ids'][ $item_id ] = array(
							'start_quantity' => isset( $priceBracket['start_quantity'] ) ? $priceBracket['start_quantity'] : 0,
							'end_quantity'   => isset( $priceBracket['end_quantity'] ) ? $priceBracket['end_quantity'] : 0,
							'pricebook_rate' => isset( $priceBracket['pricebook_rate'] ) ? $priceBracket['pricebook_rate'] : 0,
						);
					} else {
						$newpricelists['ids'][ $item_id ] = isset( $itemlist['pricebook_rate'] ) ? $itemlist['pricebook_rate'] : 0;
					}
				}
			}
		}

		return $newpricelists;
	}

	/**
	 * Save pricelist function to update prices and discounts for user roles.
	 *
	 * @param array $post The post data containing user role and price information.
	 */
	public function save_pricelist( array $post ): void {
		$zoho_pricelists_ids = $this->apply_zoho_pricelist( $post );

		global $wpdb;

		if ( isset( $zoho_pricelists_ids['ids'] ) && is_array( $zoho_pricelists_ids['ids'] ) ) {
			foreach ( $zoho_pricelists_ids['ids'] as $key => $zoho_pricelists_price ) {
				$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'zi_item_id' AND meta_value = '%s' LIMIT 1", $key ) );

				if ( $post_id > 0 ) {
					$formatted_price = str_replace( '.', wc_get_price_decimal_separator(), $zoho_pricelists_price );

					$metavalue = array(
						'discount_type'  => $zoho_pricelists_ids['orderby'],
						'user_role'      => $post['wp_user_role'],
						'discount_value' => is_array( $formatted_price ) ? $formatted_price['pricebook_rate'] : $formatted_price,
						'min_qty'        => is_array( $formatted_price ) ? $formatted_price['start_quantity'] : '',
						'max_qty'        => is_array( $formatted_price ) ? $formatted_price['end_quantity'] : '',
					);
					if ( class_exists( 'Addify_B2B_Plugin' ) ) {
						$this->updateRoleBasedPriceForAddify( $post_id, $metavalue, $post['wp_user_role'] );
					}
				}
			}
		}
	}

	private function updateRoleBasedPriceForAddify( $post_id, $metavalue, $wp_user_role ) {
		$postmeta_array = get_post_meta( $post_id, '_role_base_price', true );
		$updated        = false;

		if ( is_array( $postmeta_array ) && ! empty( $postmeta_array ) ) {
			foreach ( $postmeta_array as &$postmeta ) {
				if ( $postmeta['user_role'] === $wp_user_role ) {
					$postmeta = $metavalue; // Update the existing meta value
					$updated  = true;
					break;
				}
			}
		}

		if ( ! $updated ) {
			$postmeta_array[] = $metavalue; // Append the new meta value if not updated
		}

		update_post_meta( $post_id, '_role_base_price', $postmeta_array );
	}

	/**
	 * Checks if a role-based price exists in the given postmeta array.
	 *
	 * @param array $postmeta_array The array of postmeta to check.
	 * @param string $role The role to check for.
	 * @return bool
	 */
	protected function zi_check_role_based_price_exists( array $postmeta_array, string $role ): bool {
		foreach ( $postmeta_array as $postmeta ) {
			if ( $postmeta['user_role'] === $role ) {
				return true;
			}
		}
		return false;
	}
}
