<?php

namespace CommerceBird\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CMBIRD_API_Handler_Zoho;
use WP_REST_Response;
use WP_REST_Server;

class ShippingWebhook {
	use Api;

	private static string $endpoint = 'zoho-shipping-status';


	public function __construct() {
		register_rest_route(
			self::$namespace,
			self::$endpoint,
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array( $this, 'handle' ),
				'permission_callback' => array( $this, 'permission_check' ),
			)
		);
	}


	private function process( array $order_data ): WP_REST_Response {
		$response = new WP_REST_Response();
		$response->set_data( $this->empty_response );
		$response->set_status( 400 );
		if ( ! empty( $order_data['salesorder'] ) ) {
			$salesorder = $order_data['salesorder'];
			/* Getting Salesorder id */

			$salesorder_id = $salesorder['salesorder_id'];
			$formatted_status = trim( $salesorder['shipped_status_formatted'] );
			$ship_status = strtolower( $formatted_status );
			$packages = $salesorder['packages'];

			/* Customer Query to get Order Id */
			$order_statuses = wc_get_order_statuses();
			// Find orders with the specific meta key and value
			$orders = wc_get_orders(
				array(
					'meta_key' => 'zi_salesorder_id',
					'meta_value' => $salesorder_id,
					'numberposts' => -1,
				)
			);
			// Loop through the found orders
			foreach ( $orders as $order ) {
				$post_id = $order->get_id();
			}
			// Get Order Object
			$order = wc_get_order( $post_id );
			// process cancelled zoho orders
			if ( 'void' === trim( $salesorder['status'] ) ) {
				$order->update_status( 'cancelled' );
				$order->save();
				$response->set_data( 'Cancelled Order processed' );
				$response->set_status( 200 );

				return $response;
			}

			/* Getting Packages if empty in response */
			if ( empty( $packages ) && ! empty( $post_id ) ) {
				$zoho_inventory_oid = get_option( 'cmbird_zoho_inventory_oid' );
				$zoho_inventory_url = get_option( 'cmbird_zoho_inventory_url' );
				$package_url = $zoho_inventory_url . 'inventory/v1/packages?organization_id=' . $zoho_inventory_oid;
				$execute_curl_call_handle = new CMBIRD_API_Handler_Zoho();
				$json = $execute_curl_call_handle->execute_curl_call_get( $package_url );
				if ( 0 === (int) $json->code ) {
					$all_packages = $json->packages;
					foreach ( $all_packages as $packs ) {
						$order_id = $packs->salesorder_id;
						if ( trim( $order_id ) === trim( $salesorder_id ) ) {
							$tracking_number = $packs->tracking_number;
							if ( empty( $ship_status ) ) {
								$ship_status = trim( $packs->status );
							}
							$order->update_meta_data( 'zi_tracking_number', $tracking_number );
						}
					}
				}
			} elseif ( ! empty( $packages ) && ! empty( $post_id ) ) {
				foreach ( $packages as $package ) {
					/* getting all ship and trace data from package */
					$tracking_number = $package['tracking_number'];
					$carrier = $package['carrier'];
					$status = $package['status'];
					if ( empty( $ship_status ) ) {
						$ship_status = trim( $status );
					}
					$order->update_meta_data( 'zi_tracking_number', $tracking_number );
					$order->update_meta_data( 'zi_shipping_carrier', $carrier );
				}
			} else {
				$error = 'Post id not available for this ' . $salesorder_id . ' sales order';
				$response->set_data( $error );
				$response->set_status( 400 );

				return $response;

			}

			// process shipped status
			if ( ! empty( $ship_status ) && $post_id ) {
				$order_statuses = array_map( 'strtolower', $order_statuses );
				if ( in_array( $ship_status, $order_statuses ) ) {
					$ship_status = remove_accents( $ship_status );
					$order->update_meta_data( 'zi_shipping_status', $ship_status );
					$order->update_status( $ship_status );
				} else {
					$order->update_meta_data( 'zi_shipping_status', $ship_status );
				}
			}
			$order->save();
			$response->set_data( 'Success add tracking id and update shipped status' );
			$response->set_status( 200 );

			return $response;
		}

		return $response;
	}
}
