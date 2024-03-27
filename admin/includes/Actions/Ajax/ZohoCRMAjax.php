<?php

namespace RMS\Admin\Actions\Ajax;

use RMS\Admin\Connectors\CommerceBird;
use RMS\Admin\Traits\AjaxRequest;
use RMS\Admin\Traits\OptionStatus;
use RMS\Admin\Traits\Singleton;
use RMS\Admin\Traits\LogWriter;
use Throwable;


defined('RMS_PLUGIN_NAME') || exit;

/**
 * Initializes the Zoho CRM class.
 * @since 1.0.0
 * @return void
 */
final class ZohoCRMAjax
{

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
		'order' => array('range'),
		'customer' => array(
			'importCustomers',
		),
	);
	private const ACTIONS = array(
		'save_sync_order_via_cron' => 'sync_order',
		'save_zcrm_connect' => 'connect_save',
		'get_zcrm_connect' => 'connect_load',
		'import_zcrm_product' => 'product_import',
		'map_zcrm_product' => 'product_map',
		'map_zcrm_customer' => 'customer_map',
		'map_zcrm_order' => 'order_map',
		'export_zcrm_order' => 'order_export',
		'refresh_zcrm_fields' => 'refresh_zcrm_fields',
		'get_zcrm_fields' => 'fields_get',
		'save_zcrm_fields' => 'fields_set',
		'reset_zcrm_fields' => 'fields_reset',
		'zcrm_orders_fields' => 'zcrm_orders_fields',
		'zcrm_contacts_fields' => 'zcrm_contacts_fields',
		'zcrm_products_fields' => 'zcrm_products_fields'
	);
	private const OPTIONS = array(
		'connect' => array(
			'token' => 'commercebird-exact-online-token',
		),
		'zcrm_Sales_Orders_fields' => 'zcrm_Sales_Orders_fields',
		'zcrm_Contacts_fields' => 'zcrm_Contacts_fields',
		'zcrm_Products_fields' => 'zcrm_Products_fields',
	);

	public function __construct()
	{
		$this->load_actions();
	}

	/**
	 * Get Zoho CRM token.
	 * @since 1.0.0
	 * @return mixed
	 */
	public function get_token()
	{
		return get_option(self::OPTIONS['connect']['token'], '');
	}

	public function connect_save()
	{
		$this->verify(self::FORMS['connect']);
		if (isset ($this->data['token']) && !empty ($this->data['token'])) {
			update_option(self::OPTIONS['connect']['token'], $this->data['token']);
			$this->response['message'] = __('Saved', 'commercebird');
			$this->response['data'] = $this->data;
		} else {
			$this->errors['message'] = __('Token is required', 'commercebird');
		}

		$this->serve();
	}

	public function connect_load()
	{
		$this->verify();
		$this->response['token'] = get_option(self::OPTIONS['connect']['token'], '');
		$this->serve();
	}

	/**
	 * Get Zoho CRM custom fields.
	 * @since 1.0.0
	 * @return void
	 */
	public function refresh_zcrm_fields()
	{
		$module = $_GET['module'];
		$fields = (new CommerceBird())->get_zcrm_fields($module);
		if (is_wp_error($fields)) {
			$this->errors['message'] = $fields->get_error_message();
		} else {
			$option_name = 'zcrm_' . $module . '_fields';
			update_option(self::OPTIONS[$option_name], $fields);
			$this->response = array('message' => 'Refresh successfully!');
		}
		$this->serve();
	}

	/**
	 * Get zoho crm order fields from database
	 */
	public function zcrm_orders_fields()
	{
		$this->verify();
		$this->response['fields'] = get_option('zcrm_Sales_Orders_fields', array());
		$this->serve();
	}

	/**
	 * Get zoho crm contact fields from database
	 */
	public function zcrm_contacts_fields()
	{
		$this->verify();
		$this->response['fields'] = get_option('zcrm_Contacts_fields', array());
		$this->serve();
	}

	/**
	 * Get zoho crm product fields from database
	 */
	public function zcrm_products_fields()
	{
		$this->verify();
		$this->response['fields'] = get_option('zcrm_Products_fields', array());
		$this->serve();
	}

	/**
	 * Retrieves the fields and serves the response.
	 *
	 * @return void
	 */
	public function fields_get(): void
	{
		$this->verify();
		$this->response['form'] = get_option('wootozohocrm_custom_fields', array());
		$this->serve();
	}

	/**
	 * Sets the custom fields for the form.
	 *
	 * @return void
	 */
	public function fields_set(): void
	{
		$this->verify(array('form'));
		try {
			update_option('wootozohocrm_custom_fields', $this->data['form']);
			error_log('' . $this->data['form']);
			$this->response = array('message' => 'saved');
		} catch (Throwable $throwable) {
			$this->errors = array('message' => $throwable->getMessage());
		}
		$this->serve();
	}

	/**
	 * Resets the fields.
	 *
	 * @return void
	 */
	public function fields_reset(): void
	{
		$this->verify();
		delete_option('wootozohocrm_custom_fields');
		$this->response = array('message' => 'Reset successfully!');
		$this->serve();
	}
}

