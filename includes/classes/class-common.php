<?php

/**
 * Common class function.
 */

if (!class_exists('ZI_CommonClass')) {
    class ZI_CommonClass
    {

        public function __construct()
        {

        }

        /**
         * Function to clear all orphan data.
         */
        public function clear_orphan_data()
        {
            global $wpdb;
            $post_table = $wpdb->prefix . 'posts';
            $meta_table = $wpdb->prefix . 'postmeta';

            $post_query = "DELETE o FROM $post_table o LEFT OUTER JOIN $post_table r ON o.post_parent = r.ID WHERE r.id IS null AND o.post_type = 'product_variation'";
            $effected_rows = $wpdb->query($post_query);

            $meta_query = "DELETE m FROM $meta_table m LEFT JOIN $post_table p ON p.ID = m.post_id WHERE p.ID IS NULL";
            $effected_rows = $wpdb->query($meta_query);
        }

        /**
         * Function to get Tax Class from zoho tax id.
         */
        public function get_woo_tax_class_from_zoho_tax_id($zoho_tax_id)
        {
            global $wpdb;
            $option_table = $wpdb->prefix . 'options';
            $tax_option_obj = $wpdb->get_row($wpdb->prepare("SELECT * FROM $option_table WHERE option_value LIKE '%s' LIMIT 1", "$zoho_tax_id##%"));

            $tax_option_key = $tax_option_obj->option_name;
            // Eg : zoho_inventory_tax_rate_{id}
            $tax_class = '';
            if ($tax_option_key) {
                $tax_rate_id = explode("zoho_inventory_tax_rate_", $tax_option_key)[1];
                if ($tax_rate_id) {
                    $rates_table = $wpdb->prefix . 'woocommerce_tax_rates';
                    $tax_rate_obj = $wpdb->get_row($wpdb->prepare("SELECT tax_rate_class FROM $rates_table WHERE tax_rate_id = %d LIMIT 1", $tax_rate_id));
                    if ($tax_rate_obj && $tax_rate_obj->tax_rate_class) {
                        $tax_class = $tax_rate_obj->tax_rate_class;
                    } elseif ($tax_rate_obj && empty($tax_rate_obj->tax_rate_class)) {
                        $tax_class = 'standard';
                    }
                }
            }
            return $tax_class;
        }

    }
}
