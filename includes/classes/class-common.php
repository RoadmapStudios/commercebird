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
		 * Function to get Tax Class from zoho tax id.
		 */
		public function get_woo_tax_class_from_zoho_tax_id( $zoho_tax_id ) {
			// $fd = fopen( __DIR__ . '/get_woo_tax_class_from_zoho_tax_id.txt', 'w+' );
			// fwrite( $fd, PHP_EOL . 'Zoho Tax ID : ' . $zoho_tax_id );

			global $wpdb;
			$option_table = $wpdb->prefix . 'options';

			// Prepare and execute SQL query
			$tax_option_obj = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM $option_table WHERE option_value LIKE %s LIMIT 1",
					array( "%$zoho_tax_id##%" )
				)
			);

			// fwrite( $fd, PHP_EOL . 'tax_option_obj : ' . print_r( $tax_option_obj, true ) );

			if ( $tax_option_obj ) {
				// Extract tax rate ID from option_value
				$option_value_parts = explode( '##', $tax_option_obj->option_value );

				if ( $option_value_parts ) {
					$rates_table = $wpdb->prefix . 'woocommerce_tax_rates';
					// Get tax rate class from tax rate ID
					$tax_rate_obj = $wpdb->get_row(
						$wpdb->prepare(
							'SELECT tax_rate_class FROM %s WHERE tax_rate_id = %d LIMIT 1',
							$rates_table,
							$zoho_tax_id
						)
					);

					if ( $tax_rate_obj && isset( $tax_rate_obj->tax_rate_class ) ) {
						// Return tax class if found
						return $tax_rate_obj->tax_rate_class;
					}
				}
			}
			// fclose( $fd );
			// Return default tax class if not found
			return 'standard';
		}

		/**
		 * Get the tax class based on the tax percentage.
		 *
		 * @param float $tax_percentage The tax percentage.
		 * @return string|false The tax class if found, or false if not found.
		 */
		public function get_tax_class_by_percentage( $tax_percentage ) {
			global $wpdb;

			// Construct the SQL query to retrieve the tax rate class based on the tax percentage
			$tax_rates_table = $wpdb->prefix . 'woocommerce_tax_rates';
			$query           = $wpdb->prepare(
				"SELECT tax_rate_class
        		FROM $tax_rates_table
        		WHERE tax_rate = %f
        		LIMIT 1",
				$tax_percentage
			);

			// Execute the query
			$tax_class = $wpdb->get_var( $query );

			// Return the tax class if found, or false if not found
			return $tax_class !== null ? $tax_class : false;
		}
	}
}
