<?php

namespace RMS\Admin\Actions\Ajax;

use RMS\Admin\Actions\Sync\ExactOnlineSync;
use RMS\Admin\Connectors\CommerceBird;
use RMS\Admin\Traits\AjaxRequest;
use RMS\Admin\Traits\LogWriter;
use RMS\Admin\Traits\Singleton;

defined( 'RMS_PLUGIN_NAME' ) || exit;

final class ExactOnlineAjax {
	use Singleton;
	use LogWriter;
	use AjaxRequest;

	private const FORMS   = array(
		'connect'  => array(
			'token',
		),
		'product'  => array(
			'importProducts',
		),
		'order'    => array( 'range' ),
		'customer' => array(
			'importCustomers',
		),
	);
	private const ACTIONS = array(
		'save_sync_order_via_cron'      => 'sync_order',
		'save_exact_online_connect'     => 'connect_save',
		'get_exact_online_connect'      => 'connect_load',
		'save_exact_online_cost_center' => 'cost_center_save',
		'save_exact_online_cost_unit'   => 'cost_unit_save',
		'import_exact_online_product'   => 'product_import',
		'map_exact_online_product'      => 'product_map',
		'map_exact_online_customer'     => 'customer_map',
		'map_exact_online_order'        => 'order_map',
		'export_exact_online_order'     => 'order_export',
	);
	private const OPTIONS = array(
		'connect'     => array(
			'token' => 'commercebird-exact-online-token',
		),
		'cost_center' => 'commercebird-exact-online-cost-center',
		'cost_unit'   => 'commercebird-exact-online-cost-unit',
	);

	public function sync_order(): void {
		$this->verify( array( 'sync' ) );
		if ( $this->data['sync'] ) {
			if ( ! wp_next_scheduled( 'commmercebird_exact_online_sync_orders' ) ) {
				wp_schedule_event( time(), 'daily', 'commmercebird_exact_online_sync_orders' );
			}
		} else {
			wp_clear_scheduled_hook( 'commmercebird_exact_online_sync_orders' );
		}
		$this->response = array(
			'success' => true,
			'message' => __( 'Synced', 'commercebird' ),
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
				'sync_eo',
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
		$result                    = $this->process_orders(
			array(
				'start_date' => date(
					'Y-m-d\TH:i:s.000\Z',
					strtotime( $this->data['range'][0] )
				),
				'end_date'   => date(
					'Y-m-d\TH:i:s.000\Z',
					strtotime( $this->data['range'][1] )
				),
			),
		);
		$this->response['success'] = $result > 0;
		$this->response['data']    = $this->data['range'];
		$this->response['message'] = __( 'Mapped', 'commercebird' );
		$this->serve();
	}

	public function export_order( $start_date, $end_date ) {
		// Define the order statuses to exclude
		$exclude_statuses = array( 'failed', 'pending', 'on-hold', 'cancelled', 'refunded' );
		$posts_per_page   = 50;

		$paged = 1;
		do {
			// Query to get orders
			$args   = array(
				'date_created'   => $start_date . '...' . $end_date,
				'status'         => array_diff( wc_get_order_statuses(), $exclude_statuses ),
				'posts_per_page' => $posts_per_page,
				'paged'          => $paged,
			);
			$orders = wc_get_orders( $args );

			// Loop through orders and add customer note
			foreach ( $orders as $order ) {
				$order->set_status( $order->get_status() );
				$order->save();
			}

			++$paged;
		} while ( ! empty( $orders ) );
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
		$end_date   = strtotime( $this->data['range'][1] );
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
				'sync_eo',
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
		$customers = ( new CommerceBird() )->customer();
		$chunked   = array_chunk( $customers['customers'], 20 );
		foreach ( $chunked as $chunked_customers ) {
			$id = as_schedule_single_action(
				time(),
				'sync_eo',
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
				function ( $item ) {
					return "{$item['Code']}-{$item['Description']}";
				},
				$response['data']
			);
			update_option( self::OPTIONS['cost_center'], $centers );
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
				function ( $item ) {
					return "{$item['Code']}-{$item['Description']}";
				},
				$response['data']
			);
			update_option( self::OPTIONS['cost_unit'], $units );
			$this->response['message'] = __( 'Cost units saved', 'commercebird' );
		}
		$this->serve();
	}

	public function sync_via_cron() {
		$start_date = strtotime( '-1 day' );
		$end_date   = strtotime( '+1 day' );

		$continue = $this->process_orders(
			array(
				'start_date' => $start_date,
				'end_date'   => $end_date,
			)
		);

		if ( $continue ) {
			$this->export_order( $start_date, $end_date );
		}
	}

	public function __construct() {
		$this->load_actions();
		add_action( 'commmercebird_exact_online_sync_orders', array( $this, 'sync_via_cron' ) );
	}

	public function connect_save() {
		$this->verify( self::FORMS['connect'] );
		if ( isset( $this->data['token'] ) && ! empty( $this->data['token'] ) ) {
			update_option( self::OPTIONS['connect']['token'], $this->data['token'] );
			$this->response['message'] = __( 'Saved', 'commercebird' );
			$this->response['data']    = $this->data;
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
}
