<?php

/**
 * Common class function.
 */

if ( ! class_exists( 'ZI_CommonClass' ) ) {
	class ZI_CommonClass {


		public function __construct() {
		}

		/**
		 * Function to clear all orphan data.
		 */
		public function clear_orphan_data() {
			global $wpdb;
			$result = absint(
				$wpdb->query(
					"DELETE products
					FROM {$wpdb->posts} products
					LEFT JOIN {$wpdb->posts} wp ON wp.ID = products.post_parent
					WHERE wp.ID IS NULL AND products.post_type = 'product_variation';"
				)
			);
			return $result;
		}

		/**
		 * Get the tax class based on the tax percentage.
		 *
		 * @param float $percentage The tax percentage.
		 * @return string|false The tax class if found, or standard if not found.
		 */
		public function get_tax_class_by_percentage( $percentage ) {
			// $fd = fopen( __DIR__ . '/get_tax_class_by_percentage.txt', 'a+' );

			global $wpdb;
			// Determine the number of decimal places in the provided percentage
			$decimal_places = strlen( substr( strrchr( $percentage, '.' ), 1 ) );

			// Round the percentage to the determined number of decimal places
			$rounded_percentage = round( $percentage, $decimal_places );
			$tax_rates          = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates WHERE ROUND(tax_rate, %d) = %f", $decimal_places, $rounded_percentage ) );

			// If tax rates are found
			if ( $tax_rates ) {
				// Get the tax class from the first matching tax rate
				$tax_class = $tax_rates[0]->tax_rate_class;
				return $tax_class;
			} else {
				// Return null if no tax rates match the provided percentage
				return 'standard';
			}
		}
	}
}
