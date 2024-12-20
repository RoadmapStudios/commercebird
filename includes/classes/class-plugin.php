<?php

namespace CommerceBird;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once ABSPATH . 'wp-admin/includes/upgrade.php';

use CommerceBird\Admin\Actions\Ajax\ExactOnlineAjax;
use CommerceBird\Admin\Actions\Ajax\ZohoInventoryAjax;
use CommerceBird\Admin\Actions\Ajax\ZohoCRMAjax;
use CommerceBird\Admin\Actions\Ajax\AcfAjax;
use CommerceBird\Admin\Cors;
use CommerceBird\Admin\Template;
use CommerceBird\Admin\Acf;
use CommerceBird\CommerceBird_WC_API;

class Plugin {

	public function __construct() {
		add_action( 'admin_init', array( $this, 'init' ) );
		add_filter( 'cron_schedules', array( $this, 'cmbird_custom_cron_intervals' ) );
	}

	public static function activate() {
		// create log table
		// global $wpdb;
		// $charset_collate = $wpdb->get_charset_collate();
		// $zi_product_log_table = "{$wpdb->prefix}cmbird_zi_product_error";
		// $zi_create_sql = "CREATE TABLE $zi_product_log_table ( ID bigint(20) PRIMARY KEY auto_increment, product_id bigint(20) NOT NULL, error_message TEXT NOT NULL, sync_timestamp VARCHAR(20) NOT NULL, status int(10) NOT NULL )$charset_collate;";
		// dbDelta( $zi_create_sql );
	}

	public static function deactivate() {
		wp_clear_scheduled_hook( 'zi_execute_import_sync' );
		wp_clear_scheduled_hook( 'commmercebird_exact_online_get_payment_statuses' );
		wp_clear_scheduled_hook( 'cmbird_exact_online_sync_orders' );
		wp_clear_scheduled_hook( 'cmbird_zoho_sync_category_cron' );
		wp_clear_scheduled_hook( 'cmbird_zoho_contact_sync' );
	}

	public static function uninstall() {
		$post_meta_keys = array(
			'zi_item_id',
			'zi_purchase_account_id',
			'zi_account_id',
			'zi_account_name',
			'zi_inventory_account_id',
			'zi_salesorder_id',
			'zi_category_id',
			'_cost_price',
		);
		$user_meta_keys = array(
			'zi_contact_id',
			'zi_primary_contact_id',
			'zi_created_time',
			'zi_last_modified_time',
			'zi_billing_address_id',
			'zi_shipping_address_id',
			'zi_contact_persons_id',
			'zi_currency_id',
			'zi_currency_code',
		);
		$zi_option_keys = array(
			'cmbird_zi_webhook_password',
			'cmbird_zoho_inventory_cron_class',
			'cmbird_zoho_sync_status',
			'cmbird_zoho_item_category',
			'cmbird_zoho_stock_sync_status',
			'cmbird_zoho_item_name_sync_status',
			'cmbird_zoho_enable_auto_no_status',
			'cmbird_zoho_product_sync_status',
			'cmbird_zoho_disable_image_sync_status',
			'cmbird_zoho_disable_price_sync_status',
			'cmbird_zoho_disable_name_sync_status',
			'cmbird_zoho_disable_description_sync_status',
			'cmbird_zoho_enable_accounting_stock_status',
			'cmbird_zoho_enable_order_status',
			'cmbird_wootozoho_custom_fields',
			'cmbird_zoho_pricelist_id',
			'cmbird_zoho_warehouse_id_status',
			'cmbird_zoho_inventory_auth_code',
			'cmbird_zoho_inventory_access_token',
			'cmbird_zoho_inventory_refresh_token',
			'cmbird_zoho_inventory_timestamp',
			'cmbird_zoho_inventory_oid',
			'cmbird_zoho_inventory_url',
			'cmbird_zoho_inventory_cid',
			'cmbird_zoho_inventory_cs',
			'cmbird_zoho_inventory_domain',
			'cmbird_authorization_redirect_uri',
			'cmbird_zoho_crm_auth_code',
			'cmbird_zoho_crm_access_token',
			'cmbird_zoho_crm_refresh_token',
			'cmbird_zoho_crm_timestamp',
			'cmbird_warehouse_data',
		);

		foreach ( $zi_option_keys as $zi_option ) {
			delete_option( $zi_option );
		}

		foreach ( $post_meta_keys as $post_key ) {
			delete_post_meta_by_key( $post_key );
		}

		$users = get_users();
		foreach ( $users as $user ) {
			foreach ( $user_meta_keys as $user_key ) {
				delete_user_meta( $user->ID, $user_key );
			}
		}
		// deleting mapped categories
		global $wpdb;
		$table_name = $wpdb->prefix . 'options';
		$sql = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %s WHERE option_name LIKE %s', $table_name, '%zoho_id_for_term_id_%' ) );
		foreach ( $sql as $key => $row ) {
			$option_name = $row->option_name;
			$wpdb->delete( $table_name, array( 'option_name' => $option_name ), array( '%s' ) );
		}
		// clear scheduled zcrm_refresh_token
		wp_clear_scheduled_hook( 'zcrm_refresh_token' );
	}

	public static function init() {
		if ( is_admin() ) {
			$php_min_version = '7.4';
			$php_current_version = phpversion();

			if ( version_compare( $php_min_version, $php_current_version, '>' ) ) {
				$error_message = sprintf( 'Your server is running PHP version %s but the commercebird plugin requires at least PHP %s. Please update your PHP version.', $php_current_version, $php_min_version, );
				wp_die(
					esc_html( $error_message ),
					'Plugin Activation Error',
					array(
						'response' => 200,
						'back_link' => true,
					)
				);
			}
			$zoho_inventory_access_token = get_option( 'cmbird_zoho_inventory_access_token' );
			// remove option zoho_inventory_access_token if it contains only one character
			if ( $zoho_inventory_access_token && strlen( $zoho_inventory_access_token ) === 1 ) {
				delete_option( 'cmbird_zoho_inventory_access_token' );
			} else {
				// schedule cronjob for import sync
				$interval = get_option( 'zi_cron_interval' );
				if ( 'none' !== $interval && ! empty( $zoho_inventory_access_token ) ) {
					if ( ! wp_next_scheduled( 'zi_execute_import_sync' ) ) {
						wp_schedule_event( time(), $interval, 'zi_execute_import_sync' );
					}
				} else {
					wp_clear_scheduled_hook( 'zi_execute_import_sync' );
				}
			}
			// TODO: remove this wpdb query in next major version - 28 October 2024 - v2.2.4
			// change the post meta key name from "cost_price" to "_cost_price" using wpdb query
			global $wpdb;
			$wpdb->query( "UPDATE $wpdb->postmeta SET meta_key = '_cost_price' WHERE meta_key = 'cost_price'" );
			// add 'cmbird_' to every option key name that starts with 'zoho_' or 'zi_' using wpdb query
			$zoho_options = $wpdb->get_results( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE 'zoho_%' OR option_name LIKE 'zi_%'" );
			foreach ( $zoho_options as $zoho_option ) {
				// Skip if the option name already starts with 'cmbird_' or is exactly 'cmbird_zi_webhook_password'
				if ( strpos( $zoho_option->option_name, 'cmbird_' ) !== 0 && 'cmbird_zi_webhook_password' !== $zoho_option->option_name ) {
					$new_option_name = 'cmbird_' . $zoho_option->option_name;
					$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->options SET option_name = %s WHERE option_name = %s", $new_option_name, $zoho_option->option_name ) );
				}
			}
			// create webhook password if does not exists.
			if ( ! get_option( 'cmbird_zi_webhook_password', false ) ) {
				add_option( 'cmbird_zi_webhook_password', password_hash( 'commercebird-zi-webhook-token', PASSWORD_BCRYPT ) );
			}
			Template::instance();
			ZohoInventoryAjax::instance();
			ZohoCRMAjax::instance();
			AcfAjax::instance();
			Cors::instance();
		}
		ExactOnlineAjax::instance();
		Acf::instance();
		new CommerceBird_WC_API();
		// CMBIRD_Webhook_Modify::instance();
	}

	/**
	 * Add custom cron intervals
	 * @param array $schedules
	 * @return array
	 */
	public function cmbird_custom_cron_intervals( $schedules ) {
		// add weekly and monthly intervals if it not exists.
		if ( ! isset( $schedules['weekly'] ) ) {
			$schedules['weekly'] = array(
				'interval' => 604800,
				'display' => __( 'Once Weekly', 'commercebird' ),
			);
		}
		if ( ! isset( $schedules['monthly'] ) ) {
			$schedules['monthly'] = array(
				'interval' => 2635200,
				'display' => __( 'Once Monthly', 'commercebird' ),
			);
		}
		return $schedules;
	}
}
