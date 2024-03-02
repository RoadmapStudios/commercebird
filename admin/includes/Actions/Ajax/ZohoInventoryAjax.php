<?php

namespace RMS\Admin\Actions\Ajax;

use Classfunctions;
use ExecutecallClass;
use ImportPricelistClass;
use RMS\Admin\Template;
use RMS\Admin\Traits\AjaxRequest;
use RMS\Admin\Traits\OptionStatus;
use RMS\Admin\Traits\Singleton;
use RMS\Admin\Traits\LogWriter;
use Throwable;
use WC_Tax;
use WpOrg\Requests\Exception;
use function gettype;

defined( 'RMS_PLUGIN_NAME' ) || exit;

final class ZohoInventoryAjax {

	use Singleton;
	use AjaxRequest;
	use OptionStatus;
	use LogWriter;

	private const FORMS = array(
		'settings' => array(
			'cors',
			'id',
		),
		'tax'      => array(
			'decimalTax',
			'selectedTaxRates',
			'selectedVatExempt',
		),
		'product'  => array(
			'item_from_zoho',
			'disable_stock_sync',
			'disable_product_sync',
			'enable_accounting_stock',
		),
		'cron'     => array(
			'form',
			'categories',
		),
		'order'    => array(
			'package_sync',
			'disable_sync',
			'enable_auto_number',
			'enable_order_status',
			'enable_multicurrency',
			'order_prefix',
			'warehouse_id',
			'enable_warehousestock',
		),
		'price'    => array(
			'wp_user_role',
			'zoho_inventory_pricelist',
			'wcb2b',
		),
	);

	private const ACTIONS = array(
		'get_subscription'      => 'subscription_get',
		'get_settings'          => 'settings_get',
		'save_settings'         => 'settings_set',
		'reset_settings'        => 'settings_reset',
		'get_zoho_connect'      => 'connection_get',
		'save_zoho_connect'     => 'connection_set',
		'reset_zoho_connect'    => 'connection_reset',
		'get_zoho_tax'          => 'tax_get',
		'save_zoho_tax'         => 'tax_set',
		'reset_zoho_tax'        => 'tax_reset',
		'get_zoho_product'      => 'product_get',
		'save_zoho_product'     => 'product_set',
		'reset_zoho_product'    => 'product_reset',
		'get_zoho_cron'         => 'cron_get',
		'save_zoho_cron'        => 'cron_set',
		'reset_zoho_cron'       => 'cron_reset',
		'get_zoho_order'        => 'order_get',
		'save_zoho_order'       => 'order_set',
		'reset_zoho_order'      => 'order_reset',
		'get_zoho_price'        => 'price_get',
		'save_zoho_price'       => 'price_set',
		'reset_zoho_price'      => 'price_reset',
		'get_zoho_fields'       => 'fields_get',
		'save_zoho_fields'      => 'fields_set',
		'reset_zoho_fields'     => 'fields_reset',
		'is_connected'          => 'connection_done',
		'get_wc_taxes'          => 'wc_tax_collect',
		'get_zoho_taxes'        => 'zoho_tax_rates_collect',
		'get_zoho_categories'   => 'zoho_categories_collect',
		'get_zoho_warehouses'   => 'zoho_warehouses_collect',
		'get_zoho_prices'       => 'zoho_prices_collect',
		'get_all_custom_fields' => 'wc_custom_fields_collect',
		'handle_code'           => 'handle_code',
	);

	public function __construct() {
		$this->load_actions();
	}

	/**
	 * Collects custom checkout fields for WooCommerce.
	 *
	 * This function verifies the request and collects custom fields from the
	 * THWCFD_Utils::get_checkout_fields() function if the THWCFD class exists.
	 *
	 * @return void
	 */
	public function wc_custom_fields_collect(): void {
		$this->verify();
		$types         = array( 'billing', 'shipping', 'additional' );
		$all_fields    = array();
		$custom_fields = array();
		// Get all the fields.
		foreach ( $types as $type ) {
			// Skip if an unsupported type.
			if (
				! in_array(
					$type,
					array(
						'billing',
						'shipping',
						'additional',
					),
					true,
				)
			) {
				continue;
			}

			$temp_fields = get_option( 'wc_fields_' . $type );
			if ( false !== $temp_fields ) {
				$all_fields = array_merge( $all_fields, $temp_fields );
			}
		}
		// Loop through each field to see if it is a custom field.
		foreach ( $all_fields as $name => $options ) {
			if ( isset( $options['custom'] ) && $options['custom'] ) {
				$label                  = trim( $options['label'] );
				$custom_fields[ $name ] = empty( $label ) ? __( 'Please set label' ) : $label;
			}
		}

		$this->response = $custom_fields;

		$this->serve();
	}

	/**
	 * Retrieves the connection details.
	 *
	 * @return void
	 */
	public function connection_get(): void {
		$this->verify();
		$this->response['account_domain']  = get_option( 'zoho_inventory_domain' );
		$this->response['organization_id'] = get_option( 'zoho_inventory_oid' );
		$this->response['client_id']       = get_option( 'zoho_inventory_cid' );
		$this->response['client_secret']   = get_option( 'zoho_inventory_cs' );
		$this->response['inventory_url']   = get_option( 'zoho_inventory_url' );
		$this->response['redirect_uri']    = get_option( 'authorization_redirect_uri' );
		$this->serve();
	}

	/**
	 * Retrieves the subscription details.
	 *
	 * @return void
	 */
	public function subscription_get(): void {
		$this->verify();
		$this->response = $this->get_subscription_data();

		$this->serve();
	}

	/**
	 * @return array
	 */
	public function get_subscription_data(): array {
		$transient = get_transient( 'zoho_subscription' );
		if ( ! empty( $transient ) ) {
			return $transient;
		}

		$data = $this->get_subscription_data_from_api();

		if ( ! empty( $data ) ) {
			set_transient( 'zoho_subscription', $data, DAY_IN_SECONDS );
		}

		return $data;
	}

	private function get_subscription_data_from_api(): array {
		$subscription_id = get_option( 'zoho_id_status', 0 );
		if ( ! $subscription_id ) {
			return array();
		}

		$response = wp_safe_remote_get(
			sprintf( 'https://commercebird.com/wp-json/wc/v3/subscriptions/%s', $subscription_id ),
			array(
				'headers' => array(
					'Accept'        => 'application/json',
					'Authorization' => 'Basic Y2tfYjAzMDViODhmNmQ1ZDI2ZTY0MjNjMDczZjZmOTVkZTExOWNjOWU1NTpjc182MDljMTNmMjgxODE2YjkzNzQ5OWIyYTAwNTJlMTE0NTc0NWFjZGMz',
				),
			),
		);

		if ( is_wp_error( $response ) ) {
			$this->errors = array( 'message' => $response->get_error_messages() );
			return array();
		}

		$body   = wp_remote_retrieve_body( $response );
		$decode = json_decode( $body, true );

		if ( ! $decode || ! array_key_exists( 'line_items', $decode ) ) {
			return array();
		}

		$data                 = $this->extract_data(
			$decode,
			array(
				'fee_lines',
				'total',
				'currency',
				'next_payment_date_gmt',
				'needs_payment',
				'payment_url',
				'status',
				'variation_id',
				'line_items',
			),
		);
		$data['variation_id'] = array_column( $data['line_items'], 'variation_id' );
		$data['plan']         = array_column( $data['line_items'], 'name' );

		return $data;
	}

	/**
	 * Resets the settings details.
	 *
	 * @return void
	 */
	public function settings_reset(): void {
		$this->verify();
		$this->option_status_remove( self::FORMS['settings'] );
		$this->response = array( 'message' => 'Reset successfully!' );
		$this->serve();
	}

	/**
	 * Retrieves the settings details.
	 *
	 * @return void
	 */
	public function settings_get(): void {
		$this->verify();
		$this->response = $this->option_status_get( self::FORMS['settings'] );
		$this->serve();
	}

	/**
	 * Sets the settings for the class.
	 *
	 * @return void
	 */
	public function settings_set(): void {
		try {
			$this->verify( self::FORMS['settings'] );
			if ( $this->data ) {
				$this->option_status_update( $this->data );
				delete_transient( 'zoho_subscription' );
			} else {
				$this->errors = array(
					'message' => 'Invalid Inputs',
					$this->data,
				);
			}
		} catch ( Exception $exception ) {
			$this->errors = array( 'message' => $exception->getMessage() );
		}
		$this->serve();
	}

	/**
	 * Retrieves the fields and serves the response.
	 *
	 * @return void
	 */
	public function fields_get(): void {
		$this->verify();
		$this->response['form'] = get_option( 'wootozoho_custom_fields', array() );
		$this->serve();
	}

	/**
	 * Sets the custom fields for the form.
	 *
	 * @return void
	 */
	public function fields_set(): void {
		$this->verify( array( 'form' ) );
		try {
			update_option( 'wootozoho_custom_fields', $this->data['form'] );
			$this->response = array( 'message' => 'saved' );
		} catch ( Throwable $throwable ) {
			$this->errors = array( 'message' => $throwable->getMessage() );
		}
		$this->serve();
	}

	/**
	 * Resets the fields.
	 *
	 * @return void
	 */
	public function fields_reset(): void {
		$this->verify();
		delete_option( 'wootozoho_custom_fields' );
		$this->response = array( 'message' => 'Reset successfully!' );
		$this->serve();
	}

	/**
	 * Resets the price.
	 *
	 * @return void
	 */
	public function price_reset(): void {
		$this->verify();
		delete_option( 'zoho_pricelist_id' );
		delete_option( 'zoho_pricelist_role' );
		delete_option( 'zoho_pricelist_wcb2b_groups' );
		$this->response = array( 'message' => 'Reset successfully!' );
		$this->serve();
	}

	/**
	 * Retrieves the price information.
	 *
	 * @return void
	 */
	public function price_get(): void {
		$this->verify();
		$this->response['zoho_inventory_pricelist'] = get_option( 'zoho_pricelist_id' );
		$this->response['wp_user_role']             = get_option( 'zoho_pricelist_role' );
		if ( class_exists( 'WooCommerceB2B' ) ) {
			$this->response['wcb2b'] = get_option( 'zoho_pricelist_wcb2b_groups' );
		}
		$this->serve();
	}

	/**
	 * Sets the price and saves the pricelist.
	 *
	 */
	public function price_set(): void {
		$this->verify( self::FORMS['price'] );
		try {
			$import_pricelist = new ImportPricelistClass();
			$success          = $import_pricelist->save_price_list( $this->data );
			if ( class_exists( 'Addify_B2B_Plugin' ) ) {
				update_option( 'zoho_pricelist_role', $this->data['wp_user_role'] );
			} else {
				update_option( 'zoho_pricelist_wcb2b_groups', $this->data['wcb2b'] );
			}
			if ( $success ) {
				$this->response = array( 'message' => 'Saved' );
				if ( class_exists( 'WooCommerceB2B' ) ) {
					$this->response['wcb2b'] = $this->data['wcb2b'];
				}
			} else {
				$this->errors = array( 'message' => 'Failed to save' );
			}
		} catch ( Throwable $throwable ) {
			$this->errors = array( 'message' => $throwable->getMessage() );
			$this->write_log(
				array(
					'message' => $throwable->getMessage(),
					'files'   => wp_list_pluck( $throwable->getTrace(), 'file', 'line' ),
				),
				'zoho-ajax-error'
			);
		}

		$this->serve();
	}

	/**
	 * Collects Zoho prices.
	 *
	 * @return void
	 */
	public function zoho_prices_collect(): void {
		$this->verify();
		$price_list_class = new ImportPricelistClass();
		$prices           = $price_list_class->zi_get_all_pricelist();
		$this->response   = wp_list_pluck( $prices, 'name', 'pricebook_id' );
		$this->serve();
	}

	/**
	 * Sets the order and updates the status.
	 *
	 * @return void
	 */
	public function order_set(): void {
		$this->verify( self::FORMS['order'] );
		$this->option_status_update( $this->data );
		$this->response = array( 'message' => 'Saved!' );
		$this->serve();
	}

	/**
	 * Retrieves the order.
	 *
	 * @return void
	 */
	public function order_get(): void {
		$this->verify();
		$this->response = $this->option_status_get( self::FORMS['order'] );
		$this->serve();
	}

	/**
	 * Resets the order.
	 *
	 * @return void
	 */
	public function order_reset(): void {
		$this->verify();
		$this->option_status_remove( self::FORMS['order'] );
		$this->response = array( 'message' => 'Reset successfully!' );
		$this->serve();
	}

	/**
	 * Collects Zoho warehouses.
	 *
	 * This function verifies the request, retrieves the Zoho inventory
	 * organization ID and URL from the options, constructs the URL
	 * for the API call, and executes the cURL call to retrieve the
	 * warehouses. The response is then processed to extract the
	 * warehouse names and IDs, and stored in the class property.
	 *
	 * @return void
	 */
	public function zoho_warehouses_collect(): void {
		$this->verify();
		$zoho_inventory_oid       = get_option( 'zoho_inventory_oid' );
		$zoho_inventory_url       = get_option( 'zoho_inventory_url' );
		$url                      = $zoho_inventory_url . 'api/v1/settings/warehouses?organization_id=' . $zoho_inventory_oid;
		$execute_curl_call_handle = new ExecutecallClass();
		$json                     = $execute_curl_call_handle->ExecuteCurlCallGet( $url );
		$this->response           = wp_list_pluck( $json->warehouses, 'warehouse_name', 'warehouse_id' );
		$this->serve();
	}

	/**
	 * Sets the cron for the class.
	 *
	 */
	public function cron_set(): void {
		$this->verify( self::FORMS['cron'] );
		if ( array_key_exists( 'form', $this->data ) ) {
			$decode = json_decode( $this->data['form'], true );
			update_option( 'zi_cron_interval', $decode['zi_cron_interval'] );
			unset( $decode['zi_cron_interval'] );
			$this->option_status_update( $decode );
		}
		if ( array_key_exists( 'categories', $this->data ) ) {
			$decode = json_decode( $this->data['categories'] );
			update_option( 'zoho_item_category', serialize( $decode ) );
		}
		$this->response = array( 'message' => 'Saved' );
		$this->serve();
	}

	/**
	 * Executes the cron_get function.
	 *
	 * This function verifies the form status and retrieves the form options
	 * for syncing name, price, image, and description. It also retrieves
	 * the cron interval and the Zoho item categories.
	 *
	 * @return void
	 */
	public function cron_get(): void {
		$this->verify();
		$this->response                             = array();
		$this->response['form']                     = $this->option_status_get(
			array(
				'disable_name_sync',
				'disable_price_sync',
				'disable_image_sync',
				'disable_description_sync',
			),
		);
		$this->response['form']['zi_cron_interval'] = get_option( 'zi_cron_interval', 'none' );
		$this->response['categories']               = unserialize( get_option( 'zoho_item_category', '' ) );
		$this->serve();
	}

	/**
	 * Resets the cron job.
	 *
	 * This function verifies the cron job, removes the specified options,
	 * deletes the 'zoho_item_category' option, and serves the cron job.
	 *
	 * @return void
	 */
	public function cron_reset(): void {
		$this->verify();
		$this->option_status_remove(
			array(
				'disable_name_sync',
				'disable_price_sync',
				'disable_image_sync',
				'disable_description_sync',
			),
		);
		delete_option( 'zoho_item_category' );
		$this->response = array( 'message' => 'Reset successfully!' );
		$this->serve();
	}

	/**
	 * Collects Zoho categories.
	 *
	 * This function verifies the data and retrieves the Zoho item categories.
	 * If the categories are successfully retrieved, it filters the data to
	 * include only the 'name' and 'category_id' fields, and removes the
	 * category with ID -1. Finally, it sets the response with the filtered
	 * categories and serves the response.
	 *
	 */
	public function zoho_categories_collect(): void {
		$this->verify();
		$categories = get_zoho_item_categories();
		if ( gettype( $categories ) === 'array' && array_key_exists( 'categories', $categories ) ) {
			$filtered = wp_list_pluck( $categories['categories'], 'name', 'category_id' );
			unset( $filtered[-1] );
			$this->response = $filtered;
		}
		$this->serve();
	}

	/**
	 * Executes the connection process and handles the response.
	 *
	 */
	public function connection_done(): void {
		$this->verify();
		$zoho_inventory_url       = get_option( 'zoho_inventory_url' );
		$zoho_inventory_oid       = get_option( 'zoho_inventory_oid' );
		$url                      = $zoho_inventory_url . 'api/v1/organizations/?organization_id=' . $zoho_inventory_oid;
		$execute_curl_call_handle = new ExecutecallClass();
		$json                     = $execute_curl_call_handle->ExecuteCurlCallGet( $url );
		if ( is_wp_error( $json ) ) {
			$this->errors = array( 'message' => $json->get_error_message() );
		} elseif ( empty( $json ) ) {
			$this->errors = array( 'message' => 'We lost connection with zoho. please refresh page.' );
		} else {
			$this->response = $json->organizations;
		}
		$this->serve();
	}

	/**
	 * Executes the product_set function.
	 *
	 */
	public function product_set(): void {
		$this->verify( self::FORMS['product'] );
		if ( ! empty( $this->data ) ) {
			$this->option_status_update( $this->data );
		}
		$this->response = array( 'message' => 'Saved!' );
		$this->serve();
	}

	/**
	 * Retrieves the product data.
	 *
	 * @return void
	 */
	public function product_get(): void {
		$this->verify();
		$this->response = $this->option_status_get( self::FORMS['product'] );
		$this->serve();
	}

	/**
	 * Resets the product.
	 *
	 * @return void
	 */
	public function product_reset(): void {
		$this->verify();
		$this->option_status_remove( self::FORMS['product'] );
		$this->response = array( 'message' => 'Reset successfully!' );
		$this->serve();
	}

	/**
	 * Retrieves the tax information.
	 *
	 * @return void
	 */
	public function tax_get(): void {
		$this->verify();
		$this->response = array();
		foreach ( $this->wc_taxes() as $tax ) {
			$this->response['selectedTaxRates'][] = $tax['id'] . '^^' . get_option( 'zoho_inventory_tax_rate_' . $tax['id'] );
		}
		$this->response['selectedVatExempt'] = get_option( 'zi_vat_exempt' );
		$this->response['decimalTax']        = get_option( 'zoho_enable_decimal_tax_status' );
		$this->serve();
	}

	/**
	 * Retrieves an array of all tax rates from WooCommerce.
	 *
	 * @return array An array of all tax rates.
	 */
	public function wc_taxes(): array {
		$wc_tax_array = array();
		$tax_classes  = WC_Tax::get_tax_classes(); // Retrieve all tax classes.
		if ( ! in_array( '', $tax_classes ) ) { // Make sure "Standard rate" (empty class name) is present.
			array_unshift( $tax_classes, '' );
		}

		foreach ( $tax_classes as $tax_class ) { // For each tax class, get all rates.
			$taxes = WC_Tax::get_rates_for_tax_class( $tax_class );

			foreach ( $taxes as $key => $tax ) {
				$taxarray       = (array) $tax;
				$taxarray['id'] = $key;
				$wc_tax_array[] = $taxarray;
			}
		}

		return $wc_tax_array;
	}

	/**
	 * Resets the tax settings.
	 *
	 * This function verifies the tax settings and then deletes the options
	 * related to the tax rates, VAT exemption, and decimal tax status.
	 *
	 * @return void
	 */
	public function tax_reset(): void {
		$this->verify();
		foreach ( $this->wc_taxes() as $tax ) {
			delete_option( 'zoho_inventory_tax_rate_' . $tax['id'] );
		}
		delete_option( 'zi_vat_exempt' );
		delete_option( 'zoho_enable_decimal_tax_status' );
		$this->response = array( 'message' => 'Reset successfully!' );
		$this->serve();
	}

	/**
	 * Saves tax rates and exempt status to the database.
	 *
	 */
	public function tax_set(): void {
		$this->verify( self::FORMS['tax'] );
		$rates = array_filter( $this->data['selectedTaxRates'] );
		try {
			foreach ( $rates as $value ) {
				$valarray = explode( '^^', $value );
				update_option( 'zoho_inventory_tax_rate_' . $valarray[0], $valarray[1] );
			}
			if ( ! empty( $this->data['selectedVatExempt'] ) ) {
				update_option( 'zi_vat_exempt', $this->data['selectedVatExempt'] );
			}
			update_option( 'zoho_enable_decimal_tax_status', $this->data['decimalTax'] );
			$this->response = array( 'message' => 'Saved' );
		} catch ( Throwable $throwable ) {
			$this->errors = array( 'message' => $throwable->getMessage() );
		}

		$this->serve();
	}

	/**
	 * Sets the connection for the Zoho Inventory API.
	 *
	 */
	public function connection_set(): void {
		$this->verify(
			array(
				'account_domain',
				'organization_id',
				'client_id',
				'client_secret',
				'redirect_uri',
			),
		);
		try {
			$inventory = sprintf( 'https://inventory.zoho.%s/', $this->data['account_domain'] );
			update_option( 'zoho_inventory_domain', $this->data['account_domain'] );
			update_option( 'zoho_inventory_oid', $this->data['organization_id'] );
			update_option( 'zoho_inventory_cid', $this->data['client_id'] );
			update_option( 'zoho_inventory_cs', $this->data['client_secret'] );
			update_option( 'zoho_inventory_url', $inventory );
			update_option( 'authorization_redirect_uri', $this->data['redirect_uri'] );
			update_option( 'woocommerce_enable_guest_checkout', 'no' );
			$redirect       = esc_url_raw( 'https://accounts.zoho.' . $this->data['account_domain'] . '/oauth/v2/auth?response_type=code&client_id=' . $this->data['client_id'] . '&scope=ZohoInventory.FullAccess.all&redirect_uri=' . $this->data['redirect_uri'] . '&prompt=consent&access_type=offline&state=' . wp_create_nonce( Template::NAME ) );
			$this->response = array(
				'redirect' => $redirect,
				'message'  => 'We are redirecting you to zoho. please wait...',
			);
		} catch ( Throwable $throwable ) {
			$this->errors = array( 'message' => $throwable->getMessage() );
		}

		$this->serve();
	}

	/**
	 * Resets the connection by deleting specific options from the database.
	 *
	 */
	public function connection_reset(): void {
		$this->verify();
		try {
			$options = array(
				'zoho_inventory_domain',
				'zoho_inventory_oid',
				'zoho_inventory_cid',
				'zoho_inventory_cs',
				'zoho_inventory_url',
				'zoho_inventory_access_token',
			);
			foreach ( $options as $zi_option ) {
				delete_option( $zi_option );
			}
			update_option( 'woocommerce_enable_guest_checkout', 'yes' );
			$this->response = array( 'message' => 'Reset successfully!' );
		} catch ( Throwable $throwable ) {
			$this->errors = array( 'message' => $throwable->getMessage() );
		}

		$this->serve();
	}

	/**
	 * Retrieves the Zoho tax rates for the organization.
	 *
	 * This function verifies the request, retrieves the organization ID and URL
	 * from the options, constructs the URL for the API request, and executes
	 * the cURL call to fetch the tax rates. If the response contains the
	 * 'taxes' key, it sets the response to the tax rates. Otherwise, it sets
	 * the errors to a default message.
	 *
	 */
	public function zoho_tax_rates_collect(): void {
		$this->verify();
		$zoho_inventory_oid = get_option( 'zoho_inventory_oid' );
		$zoho_inventory_url = get_option( 'zoho_inventory_url' );
		$url                = $zoho_inventory_url . 'api/v1/settings/taxes?organization_id=' . $zoho_inventory_oid;
		try {
			$execute_curl_call_handle = new ExecutecallClass();
			$json                     = (array) $execute_curl_call_handle->ExecuteCurlCallGet( $url );
			if ( array_key_exists( 'taxes', $json ) ) {
				$this->response = $json['taxes'];
			} else {
				$this->errors = array( 'message' => 'Something went wrong!' );
			}
		} catch ( Throwable $throwable ) {
			$this->errors = array( 'message' => $throwable->getMessage() );
		}

		$this->serve();
	}

	/**
	 * Executes the wc_tax_collect function.
	 *
	 * This function verifies the current state, retrieves the taxes using the
	 * wc_taxes method, and serves the response.
	 *
	 */
	public function wc_tax_collect(): void {
		$this->verify();
		$this->response = $this->wc_taxes();
		$this->serve();
	}

	/**
	 * Handles the oAuth code.
	 *
	 */
	public function handle_code(): void {
		$this->verify();
		if ( array_key_exists( 'code', $this->request ) ) {
			$class_functions = new Classfunctions();
			$code            = $this->request['code'];
			try {
				$access_token = $class_functions->GetServiceZIAccessToken( $code );
				if ( array_key_exists( 'access_token', $access_token ) ) {
					update_option( 'zoho_inventory_auth_code', $code );
					update_option( 'zoho_inventory_access_token', $access_token['access_token'] );
					update_option( 'zoho_inventory_refresh_token', $access_token['refresh_token'] );
					update_option( 'zoho_inventory_timestamp', strtotime( gmdate( 'Y-m-d H:i:s' ) ) + $access_token['expires_in'] );
					$this->response = (array) $access_token;
				} else {
					$this->errors = (array) $access_token;
				}
			} catch ( Throwable $throwable ) {
				$this->errors = array( 'message' => $throwable->getMessage() );
			}
		}
		$this->serve();
	}
}
