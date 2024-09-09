<?php

namespace CommerceBird\Admin\Actions\Ajax;

use CMBIRD_Auth_Zoho;
use CMBIRD_API_Handler_Zoho;
use CommerceBird\Admin\Actions\Sync\ZohoCRMSync;
use CommerceBird\Admin\Template;
use CommerceBird\Admin\Traits\AjaxRequest;
use CommerceBird\Admin\Traits\OptionStatus;
use CommerceBird\Admin\Traits\Singleton;
use CommerceBird\Admin\Traits\LogWriter;
use Throwable;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
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
		'get_zcrm_connect' => 'connection_get',
		'save_zcrm_connect' => 'connection_set',
		'reset_zcrm_connect' => 'connection_reset',
		'is_zcrm_connected' => 'connection_done',
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
		'zcrm_handle_code' => 'handle_code',
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
	 * Sets the connection for the Zoho crm API.
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
			$redirect = esc_url_raw(
				'https://accounts.zoho.'
				. $this->data['account_domain']
				. '/oauth/v2/auth?response_type=code&client_id='
				. $this->data['client_id'] . '&scope=ZohoCRM.users.ALL,ZohoCRM.bulk.ALL,ZohoCRM.modules.ALL,ZohoCRM.settings.ALL,ZohoCRM.org.ALL,profile.userphoto.READ,ZohoFiles.files.CREATE&redirect_uri='
				. $this->data['redirect_uri'] . '&prompt=consent&access_type=offline&state='
				. wp_create_nonce( Template::NAME )
			);
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
	 * Retrieves the connection details.
	 *
	 * @return void
	 */
	public function connection_get(): void {
		$this->verify();
		$this->response['account_domain'] = get_option( 'zoho_crm_domain' );
		$this->response['client_id'] = get_option( 'zoho_crm_cid' );
		$this->response['client_secret'] = get_option( 'zoho_crm_cs' );
		$this->response['crm_url'] = get_option( 'zoho_crm_url' );
		$this->response['redirect_uri'] = get_option( 'authorization_redirect_uri' );
		$this->serve();
	}

	/**
	 * Handles the oAuth code.
	 */
	public function handle_code(): void {
		$this->verify();
		if ( array_key_exists( 'code', $this->request ) ) {
			$class_functions = new CMBIRD_Auth_Zoho();
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

	/**
	 * Resets the connection by deleting specific options from the database.
	 */
	public function connection_reset(): void {
		$this->verify();
		try {
			$options = array(
				'zoho_crm_domain',
				'zoho_crm_cid',
				'zoho_crm_cs',
				'zoho_crm_url',
				'zoho_crm_access_token',
			);
			foreach ( $options as $zi_option ) {
				delete_option( $zi_option );
			}
			$this->response = array( 'message' => 'Reset successfully!' );
		} catch (Throwable $throwable) {
			$this->errors = array( 'message' => $throwable->getMessage() );
		}

		$this->serve();
	}


	public function connection_done() {
		$this->verify();
		$zoho_crm_url = get_option( 'zoho_crm_url' );
		$url = $zoho_crm_url . 'crm/v6/org';
		$execute_curl_call_handle = new CMBIRD_API_Handler_Zoho();
		$json = $execute_curl_call_handle->execute_curl_call_get( $url );
		if ( is_wp_error( $json ) ) {
			$this->errors = array( 'message' => $json->get_error_message() );
		} elseif ( empty( $json ) ) {
			$this->errors = array( 'message' => 'We lost connection with zoho. please refresh page.' );
		} else {
			$this->response = $json->org;
			// schedule a wp cron to refresh the token after one hour from now.
			if ( ! wp_next_scheduled( 'zcrm_refresh_token' ) ) {
				wp_schedule_event( time() + 3600, 'hourly', 'zcrm_refresh_token' );
			} else {
				// unschedule first and reschedule.
				wp_unschedule_event( wp_next_scheduled( 'zcrm_refresh_token' ), 'zcrm_refresh_token' );
				wp_schedule_event( time() + 3600, 'hourly', 'zcrm_refresh_token' );
			}
		}
		$this->serve();
	}

	/**
	 * Get Zoho CRM custom fields.
	 * @since 1.0.0
	 * @return void
	 */
	public function refresh_zcrm_fields() {
		$module = isset( $_GET['module'] ) ? sanitize_text_field( wp_unslash( $_GET['module'] ) ) : '';
		if ( empty( $module ) ) {
			$this->errors['message'] = 'Module name is required.';
		} else {
			$fields = ( new ZohoCRMSync() )->get_custom_fields( $module );
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
		$module = isset( $_GET['module'] ) ? sanitize_text_field( wp_unslash( $_GET['module'] ) ) : '';
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
	 * @return object
	 */
	public function zcrm_get_custom_fields(): void {

		$module = $_GET['module'] ? sanitize_text_field( wp_unslash( $_GET['module'] ) ) : '';

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
		$module = isset( $_GET['module'] ) ? sanitize_text_field( wp_unslash( $_GET['module'] ) ) : '';
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
