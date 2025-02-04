<?php

namespace CommerceBird\Admin\Actions\Ajax;

use CommerceBird\Admin\Connectors\CommerceBird;
use CommerceBird\Admin\Traits\AjaxRequest;
use CommerceBird\Admin\Traits\LogWriter;
use CommerceBird\Admin\Traits\OptionStatus;
use CommerceBird\Admin\Traits\Singleton;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
final class ExactOnlineAjax {
	use Singleton;
	use LogWriter;
	use AjaxRequest;
	use OptionStatus;

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
		'webhooks' => array(
			'enable_StockPosition',
			'enable_Item',
		),

	);
	private const ACTIONS = array(
		'save_sync_order_via_cron' => 'sync_order',
		'save_exact_online_connect' => 'connect_save',
		'get_exact_online_connect' => 'connect_load',
		'save_exact_online_cost_center' => 'cost_center_save',
		'save_exact_online_cost_unit' => 'cost_unit_save',
		'save_exact_online_gl_account' => 'gl_account_save',
		'save_exact_online_payment_status' => 'get_payment_save',
		'import_exact_online_product' => 'product_import',
		'map_exact_online_product' => 'product_map',
		'map_exact_online_customer' => 'customer_map',
		'map_exact_online_order' => 'order_map',
		'export_exact_online_order' => 'order_export',
		'get_exact_online_webhooks' => 'webhooks_get',
		'save_exact_online_webhooks' => 'webhooks_save',
	);
	private const OPTIONS = array(
		'connect' => array(
			'token' => 'commercebird-exact-online-token',
		),
		'cost_center' => 'commercebird-exact-online-cost-center',
		'cost_unit' => 'commercebird-exact-online-cost-unit',
		'gl_accounts' => 'commercebird-exact-online-gl-accounts',
	);

	private const SOURCE = 'exact';

	/**
	 * Sync orders from Exact Online.
	 *
	 * @return void
	 */
	public function sync_order(): void {
		$this->verify( array( 'sync' ) );
		if ( $this->data['sync'] ) {
			if ( ! wp_next_scheduled( 'cmbird_exact_online_sync_orders' ) ) {
				wp_schedule_event( time(), 'daily', 'cmbird_exact_online_sync_orders' );
			}
		} else {
			wp_clear_scheduled_hook( 'cmbird_exact_online_sync_orders' );
		}
		update_option( 'cmbird_exact_online_sync_orders', (bool) $this->data['sync'] );
		$this->response = array(
			'success' => true,
			'message' => __( 'Synced', 'commercebird' ),
		);
		$this->serve();
	}

	public function get_payment_save(): void {
		$this->verify();
		$this->get_payment_status();
	}

	/**
	 * Get Payment status from Exact Online. This is used to update the payment status of the order.
	 *
	 * @return void
	 */
	public function get_payment_status(): void {
		// $fd = fopen( __DIR__ . '/get_payment_status.log', 'a+' );

		$start_date = gmdate( 'Y-m-d H:i:s', strtotime( '-200 days' ) );
		$end_date = gmdate( 'Y-m-d H:i:s', strtotime( '-5 days' ) );

		// Get all orders with the included statuses
		$orders = wc_get_orders(
			array(
				'status' => 'wc-on-hold',
				'limit' => -1,
				'date_created' => $start_date . '...' . $end_date,
				'return' => 'ids',
			)
		);
		if ( empty( $orders ) ) {
			$this->response = array(
				'success' => false,
				'message' => __( 'No Invoice orders found', 'commercebird' ),
			);
			$this->serve();
		}
		foreach ( $orders as $order_id ) {
			// send each order_id to wc action scheduler to process if not scheduled
			if ( ! as_has_scheduled_action( 'cmbird_payment_status', array( $order_id ) ) ) {
				as_schedule_single_action(
					time(),
					'cmbird_payment_status',
					array(
						$order_id,
					)
				);
			} else {
				continue;
			}
		}
		// fclose( $fd );

		// Schedule the event to run weekly starting next week
		if ( ! wp_next_scheduled( 'cmbird_eo_get_payment_statuses' ) ) {
			$seven_days_in_future = time() + 7 * DAY_IN_SECONDS;
			wp_schedule_event( $seven_days_in_future, 'weekly', 'cmbird_eo_get_payment_statuses' );
		}
		$this->response = array(
			'success' => true,
			'message' => __( 'Payment status updated', 'commercebird' ),
		);
		$this->serve();
	}

	private function process_orders( $range ): bool {
		$result = 0;
		$orders = ( new CommerceBird() )->order( $range );
		if ( is_string( $orders ) ) {
			$this->response = array(
				'success' => false,
				'message' => $orders,
			);
			$this->serve();
		}
		$chunked = array_chunk( $orders['orders'], 20 );
		foreach ( $chunked as $chunked_order ) {
			$result = as_schedule_single_action(
				time(),
				'cmbird_sync_eo',
				array(
					'orders',
					wp_json_encode( $chunked_order ),
					false,
				)
			);
			if ( empty( $result ) ) {
				break;
			}
		}
		return $result > 0;
	}

	public function order_map() {
		$this->verify( self::FORMS['order'] );
		if ( empty( $this->data ) || empty( $this->data['range'] ) ) {
			$this->response['success'] = false;
			$this->response['message'] = __( 'Select dates', 'commercebird' );
			$this->serve();
		}
		$result = $this->process_orders(
			array(
				'start_date' => gmdate(
					'Y-m-d\TH:i:s.000\Z',
					strtotime( $this->data['range'][0] )
				),
				'end_date' => gmdate(
					'Y-m-d\TH:i:s.000\Z',
					strtotime( $this->data['range'][1] )
				),
			),
		);
		$this->response['success'] = $result > 0;
		$this->response['data'] = $this->data['range'];
		$this->response['message'] = __( 'Mapped', 'commercebird' );
		$this->serve();
	}

	public function export_order( $start_date_raw, $end_date_raw ) {
		// $fd = fopen( __DIR__ . '/export_order.log', 'a+' );

		$start_date = gmdate( 'Y-m-d H:i:s', $start_date_raw );
		$end_date = gmdate( 'Y-m-d H:i:s', $end_date_raw );
		// Define the order statuses to exclude
		$exclude_statuses = array( 'wc-failed', 'wc-pending', 'wc-on-hold', 'wc-cancelled' );
		$posts_per_page = 20;
		$paged = 1;

		do {
			// Query to get orders
			$args = array(
				'date_created' => $start_date . '...' . $end_date,
				'status' => array_diff( array_keys( wc_get_order_statuses() ), $exclude_statuses ),
				'limit' => $posts_per_page,
				'paged' => $paged,
				'orderby' => 'date',
				'order' => 'ASC',
				'return' => 'ids',
			);
			$orders = wc_get_orders( $args );

			// Loop through orders and add customer note
			foreach ( $orders as $order_id ) {
				$order = wc_get_order( $order_id );
				$order->set_status( $order->get_status() );
				$order->save();
			}

			// Increment the offset for the next batch
			++$paged;
		} while ( ! empty( $orders ) );
		// fclose( $fd );
	}

	public function order_export() {
		$this->verify( self::FORMS['order'] );
		if ( empty( $this->data ) || empty( $this->data['range'] ) ) {
			$this->response['success'] = false;
			$this->response['message'] = __( 'Select dates', 'commercebird' );
			$this->serve();
		}
		// Set the date range to last 30 days
		$start_date = strtotime( $this->data['range'][0] );
		$end_date = strtotime( $this->data['range'][1] );
		$this->export_order( $start_date, $end_date );
		$this->response['success'] = true;
		$this->response['message'] = __( 'Exported', 'commercebird' );
		$this->serve();
	}

	public function product_map() {
		$this->verify( self::FORMS['product'] );
		$products = ( new CommerceBird() )->products();
		if ( is_string( $products ) ) {
			$this->response = array(
				'success' => false,
				'message' => $products,
			);
			$this->serve();
		}
		$chunked = array_chunk( $products['items'], 20 );
		foreach ( $chunked as $chunked_products ) {
			$id = as_schedule_single_action(
				time(),
				'cmbird_sync_eo',
				array(
					'product',
					wp_json_encode( $chunked_products ),
					(bool) $this->data['importProducts'],
				)
			);
			if ( empty( $id ) ) {
				break;
			}
		}
		$this->response['message'] = __( 'Items are being mapped in background. You can visit other tabs :).', 'commercebird' );
		$this->serve();
	}

	public function customer_map() {
		$this->verify( self::FORMS['customer'] );
		// get all customers that have a meta key eo_gl_account.
		/*
						  $customers = get_users(
							  array(
								  'meta_query' => array(
									  array(
										  'key' => 'eo_gl_account',
										  'compare' => 'EXISTS',
									  ),
								  ),
							  )
						  );
						  // update each customer by adding "test" to biography of each customer.
						  foreach ( $customers as $customer ) {
							  // use wp_update_user() function to update the user.
							  wp_update_user(
								  array(
									  'ID' => $customer->ID,
									  'description' => 'test',
								  )
							  );
						  }
						  */
		$customers = ( new CommerceBird() )->customer();
		if ( is_string( $customers ) ) {
			$this->response = array(
				'success' => false,
				'message' => $customers,
			);
			$this->serve();
		}
		$chunked = array_chunk( $customers['customers'], 20 );
		foreach ( $chunked as $chunked_customers ) {
			$id = as_schedule_single_action(
				time(),
				'cmbird_sync_eo',
				array(
					'customer',
					wp_json_encode( $chunked_customers ),
					(bool) $this->data['importCustomers'],
				)
			);
			if ( empty( $id ) ) {
				break;
			}
		}
		$this->response['message'] = __( 'Items are being mapped in background. You can visit other tabs :).', 'commercebird' );
		$this->serve();
	}

	public function cost_center_get() {
		return get_option( self::OPTIONS['cost_center'], array() );
	}

	public function cost_unit_get() {
		return get_option( self::OPTIONS['cost_unit'], array() );
	}

	public function gl_account_get() {
		return get_option( self::OPTIONS['gl_accounts'], array() );
	}

	public function get_token() {
		return get_option( self::OPTIONS['connect']['token'], '' );
	}

	public function cost_center_save() {
		$this->verify();
		$response = ( new CommerceBird() )->cost_centers();
		if ( empty( $response ) ) {
			$this->errors['message'] = __( 'Cost centers not found', 'commercebird' );
		} else {
			$centers = array_map(
				function ($item) {
					return "{$item['Code']}-{$item['Description']}";
				},
				$response['data']
			);
			update_option( self::OPTIONS['cost_center'], $centers );
			// remove each meta if it does not exists in the cost centers.
			global $wpdb;
			if ( ! empty( $centers ) ) {
				// Sanitize and prepare the units for the SQL query
				$placeholders = implode( ',', array_fill( 0, count( $centers ), '%s' ) );

				// Prepare the query string
				$query = "DELETE FROM {$wpdb->prefix}wc_orders_meta
              			WHERE meta_key = 'costcenter'
              			AND meta_value NOT IN ($placeholders)";
				$wpdb->query( $wpdb->prepare( $query, ...$centers ) ); // phpcs:ignore
				// also remove from postmeta table.
				$query2 = "DELETE FROM {$wpdb->prefix}postmeta
              			WHERE meta_key = 'costcenter'
              			AND meta_value NOT IN ($placeholders)";
				$wpdb->query( $wpdb->prepare( $query2, ...$centers ) ); // phpcs:ignore
			} else {
				// If $units is empty, delete all meta keys with 'costunit'
				$wpdb->query(
					"DELETE FROM {$wpdb->prefix}wc_orders_meta
         			WHERE meta_key = 'costcenter'"
				);
			}
			$this->response['message'] = __( 'Cost centers saved', 'commercebird' );
		}
		$this->serve();
	}

	public function cost_unit_save() {
		$this->verify();
		$response = ( new CommerceBird() )->cost_units();
		if ( empty( $response ) ) {
			$this->errors['message'] = __( 'Cost units not found', 'commercebird' );
		} else {
			$units = array_map(
				function ($item) {
					return "{$item['Code']}-{$item['Description']}";
				},
				$response['data']
			);
			update_option( self::OPTIONS['cost_unit'], $units );
			// remove each meta if it does not exists in the units.
			global $wpdb;
			if ( ! empty( $units ) ) {
				// Sanitize and prepare the units for the SQL query
				$placeholders = implode( ',', array_fill( 0, count( $units ), '%s' ) );

				// Prepare the query string
				$query = "DELETE FROM {$wpdb->prefix}wc_orders_meta
              			WHERE meta_key = 'costunit'
              			AND meta_value NOT IN ($placeholders)";
				$wpdb->query( $wpdb->prepare( $query, ...$units ) ); // phpcs:ignore
				// also remove from postmeta table
				$query2 = "DELETE FROM {$wpdb->prefix}postmeta
              			WHERE meta_key = 'costunit'
              			AND meta_value NOT IN ($placeholders)";
				$wpdb->query( $wpdb->prepare( $query2, ...$units ) ); // phpcs:ignore
			} else {
				// If $units is empty, delete all meta keys with 'costunit'
				$wpdb->query(
					"DELETE FROM {$wpdb->prefix}wc_orders_meta
         			WHERE meta_key = 'costunit'"
				);
			}
			$this->response['message'] = __( 'Cost units saved', 'commercebird' );
		}
		$this->serve();
	}

	public function gl_account_save() {
		$this->verify();
		$response = ( new CommerceBird() )->gl_accounts();
		if ( empty( $response ) ) {
			$this->errors['message'] = __( 'GL accounts not found', 'commercebird' );
		} else {
			$accounts = array_map(
				function ($item) {
					return "{$item['ID']} : {$item['Description']}";
				},
				$response['data']
			);
			update_option( self::OPTIONS['gl_accounts'], $accounts );
			$this->response['message'] = __( 'GL accounts saved', 'commercebird' );
		}
		$this->serve();
	}

	public function sync_via_cron() {
		$start_date = strtotime( '-1 day' );
		$end_date = strtotime( 'now' );

		$continue = $this->process_orders(
			array(
				'start_date' => $start_date,
				'end_date' => $end_date,
			)
		);

		if ( $continue ) {
			$this->export_order( $start_date, $end_date );
		}
	}

	public function __construct() {
		$this->load_actions();
		add_action( 'cmbird_exact_online_sync_orders', array( $this, 'sync_via_cron' ) );
	}

	public function connect_save() {
		$this->verify( self::FORMS['connect'] );
		if ( isset( $this->data['token'] ) && ! empty( $this->data['token'] ) ) {
			update_option( self::OPTIONS['connect']['token'], $this->data['token'] );
			$this->response['message'] = __( 'Saved', 'commercebird' );
			$this->response['data'] = $this->data;
		} else {
			$this->errors['message'] = __( 'Token is required', 'commercebird' );
		}

		$this->serve();
	}

	public function connect_load() {
		$this->verify();
		$this->response['token'] = get_option( self::OPTIONS['connect']['token'], '' );
		$this->serve();
	}
	/**
	 * Sets the webhooks settings and updates the status.
	 *
	 * @return void
	 */
	public function webhooks_save(): void {
		$this->verify( self::FORMS['webhooks'] );
		$form_data = $this->data;
		foreach ( $form_data as $topic => $is_active ) {
			$webhooks[] = array(
				'topic' => $topic,
				'status' => $is_active ? 'active' : 'inactive',
			);
		}
		$response = ( new CommerceBird() )->subscribe_exact_webhooks( $webhooks );
		// error_log( print_r( $response, true ) );
		$this->option_status_update( $this->data );
		$this->response = array( 'message' => 'Saved!' );
		$this->serve();
	}

	/**
	 * Retrieves the wehbhook settings.
	 *
	 * @return void
	 */
	public function webhooks_get(): void {
		$this->verify();
		$this->response = $this->option_status_get( self::FORMS['webhooks'] );
		$this->serve();
	}
}
