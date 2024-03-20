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
			$post_table = $wpdb->prefix . 'posts';
			$meta_table = $wpdb->prefix . 'postmeta';

			$post_query    = "DELETE o FROM $post_table o LEFT OUTER JOIN $post_table r ON o.post_parent = r.ID WHERE r.id IS null AND o.post_type = 'product_variation'";
			$effected_rows = $wpdb->query( $wpdb->prepare( 'DELETE o FROM %s o LEFT OUTER JOIN %s r ON o.post_parent = r.ID WHERE r.id IS null AND o.post_type = %s', $post_table, $post_table, 'product_variation' ) );

			$meta_query    = "DELETE m FROM $meta_table m LEFT JOIN $post_table p ON p.ID = m.post_id WHERE p.ID IS NULL";
			$effected_rows = $wpdb->query( $wpdb->prepare( 'DELETE m FROM %s m LEFT JOIN %s p ON p.ID = m.post_id WHERE p.ID IS NULL', $meta_table, $post_table ) );
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
			$sql_query          = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates WHERE ROUND(tax_rate, %d) = %f", $decimal_places, $rounded_percentage );
			$tax_rates          = $wpdb->get_results( $sql_query );

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
