<?php

namespace RMS\Admin\Actions\Ajax;

use Classfunctions;
use ExecutecallClass;
use RMS\Admin\Template;
use RMS\Admin\Connectors\CommerceBird;
use RMS\Admin\Traits\AjaxRequest;
use RMS\Admin\Traits\OptionStatus;
use RMS\Admin\Traits\Singleton;
use RMS\Admin\Traits\LogWriter;
use Throwable;


defined( 'RMS_PLUGIN_NAME' ) || exit;

/**
 * Initializes the Zoho CRM class.
 * @since 1.0.0
 * @return void
 */
final class ZohoCRMAjax {

	use Singleton;
	use AjaxRequest;
	use OptionStatus;
	use LogWriter;



	private const FORMS = array(
		'connect' => array(
			'token',
		),
		'product' => array(
			'importProducts',
		),
		'order' => array( 'range' ),
		'customer' => array(
			'importCustomers',
		),
		'fields' => array(
			'form',
			'module',
		),
	);
	private const ACTIONS = array(
		'save_sync_order_via_cron' => 'sync_order',
		'save_zcrm_connect' => 'connection_set',
		'get_zcrm_connect' => 'connect_load',
		'import_zcrm_product' => 'product_import',
		'map_zcrm_product' => 'product_map',
		'map_zcrm_customer' => 'customer_map',
		'map_zcrm_order' => 'order_map',
		'export_zcrm_order' => 'order_export',
		'refresh_zcrm_fields' => 'refresh_zcrm_fields',
		'zcrm_get_custom_fields' => 'zcrm_get_custom_fields',
		'zcrm_save_custom_fields' => 'zcrm_save_custom_fields',
		'zcrm_reset_custom_fields' => 'zcrm_reset_custom_fields',
		'zcrm_fields' => 'get_zcrm_fields',
		'handle_code' => 'handle_code',
	);
	private const OPTIONS = array(
		'connect' => array(
			'token' => 'commercebird-exact-online-token',
		),
		'zcrm_sales_orders_fields' => 'zcrm_sales_orders_fields',
		'zcrm_contacts_fields' => 'zcrm_contacts_fields',
		'zcrm_products_fields' => 'zcrm_products_fields',
	);

	public function __construct() {
		$this->load_actions();
	}

	/**
	 * Sets the connection for the Zoho Inventory API.
	 */
	public function connection_set(): void {
		$this->verify(
			array(
				'account_domain',
				'client_id',
				'client_secret',
				'redirect_uri',
			),
		);
		try {
			$crm_url = sprintf( 'https://www.zohoapis.%s/', $this->data['account_domain'] );
			update_option( 'zoho_crm_domain', $this->data['account_domain'] );
			update_option( 'zoho_crm_cid', $this->data['client_id'] );
			update_option( 'zoho_crm_cs', $this->data['client_secret'] );
			update_option( 'zoho_crm_url', $crm_url );
			update_option( 'authorization_redirect_uri', $this->data['redirect_uri'] );
			$redirect = esc_url_raw( 'https://accounts.zoho.' . $this->data['account_domain'] . '/oauth/v2/auth?response_type=code&client_id=' . $this->data['client_id'] . '&scope=ZohoCRM.modules.ALL&redirect_uri=' . $this->data['redirect_uri'] . '&prompt=consent&access_type=offline&state=' . wp_create_nonce( Template::NAME ) );
			$this->response = array(
				'redirect' => $redirect,
				'message' => 'We are redirecting you to zoho. please wait...',
			);
		} catch (Throwable $throwable) {
			$this->errors = array( 'message' => $throwable->getMessage() );
		}

		$this->serve();
	}

	/**
	 * Handles the oAuth code.
	 */
	public function handle_code(): void {
		$this->verify();
		if ( array_key_exists( 'code', $this->request ) ) {
			$class_functions = new Classfunctions();
			$code = $this->request['code'];
			try {
				$access_token = $class_functions->get_zoho_access_token( $code, 'zoho_crm' );
				if ( array_key_exists( 'access_token', $access_token ) ) {
					update_option( 'zoho_crm_auth_code', $code );
					update_option( 'zoho_crm_access_token', $access_token['access_token'] );
					update_option( 'zoho_crm_refresh_token', $access_token['refresh_token'] );
					update_option( 'zoho_crm_timestamp', strtotime( gmdate( 'Y-m-d H:i:s' ) ) + $access_token['expires_in'] );
					$this->response = (array) $access_token;
				} else {
					$this->errors = (array) $access_token;
				}
			} catch (Throwable $throwable) {
				$this->errors = array( 'message' => $throwable->getMessage() );
			}
		}
		$this->serve();
	}



	public function connect_load() {
		$this->verify();
		$zoho_crm_url = get_option( 'zoho_crm_url' );
		$url = $zoho_crm_url . 'crm/v6/org';
		$execute_curl_call_handle = new ExecutecallClass();
		$json = $execute_curl_call_handle->execute_curl_call_get( $url );
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
	 * Get Zoho CRM custom fields.
	 * @since 1.0.0
	 * @return void
	 */
	public function refresh_zcrm_fields() {
		$module = isset( $_GET['module'] ) ? $_GET['module'] : '';
		if ( empty( $module ) ) {
			$this->errors['message'] = 'Module name is required.';
		} else {
			$fields = ( new CommerceBird() )->get_zcrm_fields( $module );
			if ( is_wp_error( $fields ) ) {
				$this->errors['message'] = $fields->get_error_message();
			} else {
				$option_name = 'zcrm_' . strtolower( $module ) . '_fields';
				update_option( self::OPTIONS[ $option_name ], $fields );
				$this->response = array( 'message' => 'Refresh successfully!' );
				$this->response['fields'] = $fields;
			}
			$this->serve();
		}

	}

	/**
	 * Get Zoho CRM fields from wordpress database.
	 */
	public function get_zcrm_fields() {
		$module = isset( $_GET['module'] ) ? $_GET['module'] : '';
		if ( empty( $module ) ) {
			$this->errors['message'] = 'Module name is required.';
		} else {
			$fields = get_option( 'zcrm_' . strtolower( $module ) . '_fields', array() );
			$this->response['fields'] = $fields;
			$this->serve();
		}
	}

	/**
	 * Retrieves the fields and serves the response.
	 *
	 * @return void
	 */
	public function zcrm_get_custom_fields(): void {
		$module = isset( $_GET['module'] ) ? $_GET['module'] : '';
		if ( empty( $module ) ) {
			$this->errors['message'] = 'Module name is required.';
		} else {
			$this->verify();
			$option_name = 'zcrm_' . strtolower( $module ) . '_custom_fields';
			$this->response['form'] = get_option( $option_name, array() );
			$this->serve();
		}
	}

	/**
	 * Sets the custom fields for the form.
	 *
	 * @return void
	 */
	public function zcrm_save_custom_fields(): void {
		$this->verify( self::FORMS['fields'] );
		$module = isset( $this->data['module'] ) ? $this->data['module'] : '';
		if ( empty( $module ) ) {
			$this->errors['message'] = 'Module name is required.';
		} else {
			try {
				$option_name = 'zcrm_' . strtolower( $module ) . '_custom_fields';
				update_option( $option_name, $this->data['form'] );
				$this->response = array( 'message' => 'saved' );
			} catch (Throwable $throwable) {
				$this->errors = array( 'message' => $throwable->getMessage() );
			}
			$this->serve();
		}
	}

	/**
	 * Resets the fields.
	 *
	 * @return void
	 */
	public function zcrm_reset_custom_fields(): void {
		$module = isset( $_GET['module'] ) ? $_GET['module'] : '';
		if ( empty( $module ) ) {
			$this->errors['message'] = 'Module name is required.';
		} else {
			$this->verify();
			$option_name = 'zcrm_' . strtolower( $module ) . '_custom_fields';
			delete_option( $option_name );
			$this->response = array( 'message' => 'Reset successfully!' );
			$this->serve();
		}
	}
}
