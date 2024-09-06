<?php

namespace CommerceBird\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CommerceBird\Admin\Traits\LogWriter;
use WP_REST_Response;
use WC_Coupon;

class CreateSFOrderWebhook {

	use Api;
	use LogWriter;

	private static string $endpoint = 'create-sf-order';

	public function __construct() {
		register_rest_route(
			self::$namespace,
			self::$endpoint,
			array(
				'methods' => 'POST',
				'callback' => array( $this, 'handle' ),
				'permission_callback' => array( $this, 'permission_check' ),
			)
		);
	}

	private function process( array $order_data ): WP_REST_Response {
		$response = new WP_REST_Response();

		if ( empty( $order_data ) ) {
			$response->set_data( $this->empty_response );
			$response->set_status( 404 );
			return $response;
		}

		$billing_address = $order_data['billing'];
		$shipping_address = $order_data['shipping'];
		if ( isset( $order_data['billing']['email'] ) ) {
			$customer_data = $order_data['billing'];
			$customer_mail = $customer_data['email'];
			$customer = get_user_by( 'email', $customer_mail );
			if ( empty( $customer ) ) {
				$customer_id = wc_create_new_customer( $customer_mail );
				$customer = get_user_by( 'id', $customer_id );
			}
		}
		if ( 'joananwolf+test@cobracrm.nl' === $customer_mail ) {
			$order_status = 'wc-pending';
		} else {
			$order_status = 'wc-sf-order';
		}

		if ( empty( $order_data['line_items'] ) ) {
			$message = sprintf( __( 'SF order #%1$s could not be created in your store %2$s because of missing line items.', 'commercebird' ), $order_data['order_id'], get_bloginfo( 'name' ) );
			error_log_api_email( __( 'SF Order Sync', 'commercebird' ), $message );
			$response->set_status( 500 );
			$response->set_data( $message );
			return $response;
		}

		$line_items = $this->get_items( $order_data['line_items'] );
		$coupon_code = $this->get_coupon_code( $order_data );

		if ( ! empty( $line_items['not_found'] ) ) {
			$message = sprintf( __( 'SF order #%1$s could not be created in your store %2$s because of missing items: %3$s', 'commercebird' ), $order_data['order_id'], get_bloginfo( 'name' ), implode( ', ', $line_items['not_found'] ) );
			error_log_api_email( __( 'SF Order Sync', 'commercebird' ), $message );
			$response->set_status( 500 );
			$response->set_data( $message );
			return $response;
		}
		// Update existing order if it exists
		$existing_order = wc_get_order( $order_data['order_id'] );
		if ( ! empty( $existing_order ) && empty( $line_items['not_found'] ) ) {
			$existing_order->set_address( $shipping_address, 'shipping' );
			// Remove existing order items
			$existing_order->remove_order_items( 'line_item' );
			// Get all applied coupons and remove them
			$applied_coupons = $existing_order->get_coupon_codes();
			foreach ( $applied_coupons as $coupon_code_old ) {
				$existing_order->remove_coupon( $coupon_code_old );
			}
			// Add products to the order// Add products to the order
			foreach ( $order_data['line_items'] as $order_data_item ) {
				$sku = isset( $order_data_item['SKU'] ) ? $order_data_item['SKU'] : ( isset( $order_data_item['variation_SKU'] ) ? $order_data_item['variation_SKU'] : null );
				$product_id = wc_get_product_id_by_sku( $sku );
				if ( ! empty( $product_id ) ) {
					$product = wc_get_product( $product_id );
					if ( $product && $product->is_type( 'variation' ) ) {
						// Get variation object
						$variation = wc_get_product_object( 'variation', $product_id );
						if ( $variation ) {
							$existing_order->add_product( $variation, $order_data_item['quantity'] );
						}
					} else {
						$existing_order->add_product( $product, $order_data_item['quantity'] );
					}
				} else {
					$response->set_status( 500 );
					$response->set_data(
						array(
							'status' => 'Product SKU: ' . $sku . ' not found in store',
						)
					);
					return $response;
				}
			}
			// Save the changes to the order
			$existing_order->set_customer_note( isset( $order_data['notes'] ) ? $order_data['notes'] : '' );
			$existing_order->update_status( $order_status );
			$existing_order->apply_coupon( $coupon_code );
			$existing_order->calculate_totals();
			$existing_order->set_payment_method( 'bacs' );
			$order_id = $existing_order->save();
			$existing_order->add_order_note( 'Updated by Salesforce' );
			wc_delete_shop_order_transients( $existing_order );
			$response->set_data(
				array(
					'order_id' => $order_id,
					'payment_url' => $existing_order->get_checkout_payment_url(),
					'total' => $existing_order->get_total(),
					'status' => 'Success',
				)
			);
			$response->set_status( 200 );
		} elseif ( empty( $line_items['not_found'] ) ) {
			$order = wc_create_order();
			if ( $customer ) {
				$order->set_customer_id( $customer->ID );
			}
			// Add products to the order
			foreach ( $order_data['line_items'] as $order_data_item ) {

				$sku = isset( $order_data_item['SKU'] ) ? $order_data_item['SKU'] : ( isset( $order_data_item['variation_SKU'] ) ? $order_data_item['variation_SKU'] : null );
				$product_id = wc_get_product_id_by_sku( $sku );
				if ( ! empty( $product_id ) ) {
					$product = wc_get_product( $product_id );
					if ( $product && $product->is_type( 'variation' ) ) {
						// Get variation object
						$variation = wc_get_product_object( 'variation', $product_id );
						if ( $variation ) {
							$order->add_product( $variation, $order_data_item['quantity'] );
						}
					} else {
						$order->add_product( $product, $order_data_item['quantity'] );
					}
				} else {
					$response->set_status( 500 );
					$response->set_data(
						array(
							'status' => 'Product SKU: ' . $sku . ' not found in store',
						)
					);
					return $response;
				}
			}
			$order->set_address( $billing_address, 'billing' );
			$order->set_address( $shipping_address, 'shipping' );
			$order->set_status( $order_status );
			$order->apply_coupon( $coupon_code );
			$order->calculate_totals();
			$order->set_customer_note( $order_data['notes'] );
			$order->set_payment_method( 'bacs' );
			$order_id = $order->save();
			// Add metadata to the order
			$order->update_meta_data( 'sf_order_id', $order_data['sf_order_id'] );
			$order->update_meta_data( 'wk_sf_order_id', $order_data['sf_order_id'] );
			$order->update_meta_data( 'wk_sf_opp_id', $order_data['sf_order_id'] );
			// add order note to order
			$order->add_order_note( 'Synced via Salesforce' );
			$response->set_data(
				array(
					'order_id' => $order_id,
					'payment_url' => $order->get_checkout_payment_url(),
					'total' => $order->get_total(),
					'status' => 'Success',
				)
			);
			$response->set_status( 200 );
		} else {
			$response->set_data(
				array(
					'status' => 'Something went wrong. Please try again.',
				)
			);
			$response->set_status( 500 );
		}

		return $response;
	}

	/**
	 * Find the product ids from the line items from salesforce.
	 * @param mixed $line_items
	 * @return array
	 */

	private function get_items( $line_items ): array {
		$product_ids = array();
		$not_found = array();

		foreach ( $line_items as $item ) {
			// Check if the SKU key is present, if not, fallback to variation_SKU
			$sku = isset( $item['SKU'] ) ? $item['SKU'] : ( isset( $item['variation_SKU'] ) ? $item['variation_SKU'] : null );
			if ( $sku !== null ) {
				$product_id = wc_get_product_id_by_sku( $sku );
				if ( $product_id ) {
					$product_ids[] = $product_id;
				} else {
					$not_found[] = $sku;
				}
			}
		}
		if ( ! empty( $not_found ) ) {
			return array( 'not_found' => $not_found );
		}
		return $product_ids;
	}

	/**
	 * Find the coupon code based on Discount or create the coupon code and return it.
	 * @param mixed $order_data The order data from salesforce.
	 * @return string The coupon code.
	 */
	private function get_coupon_code( $order_data ): string {
		if ( isset( $order_data['Discount'] ) && ! empty( $order_data['Discount'] ) ) {
			$fixed_cart_amount = $order_data['Discount'];
			// Search for an existing coupon by fixed cart amount
			$current_datetime = time();
			$existing_coupon = get_posts(
				array(
					'post_type' => 'shop_coupon',
					'post_status' => 'publish',
					'post_per_page' => -1,
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'key' => 'discount_type',
							'value' => 'percent',
						),
						array(
							'key' => 'coupon_amount',
							'value' => $fixed_cart_amount,
							'compare' => '=',
						),
						array(
							'relation' => 'OR',
							array(
								'key' => 'date_expires',
								'compare' => 'NOT EXISTS',
							),
							array(
								'key' => 'date_expires',
								'value' => $current_datetime,
								'compare' => '>=',
							),
						),
					),
				)
			);

			if ( $existing_coupon ) {
				$coupon_code = $existing_coupon[0]->post_title;
				return $coupon_code;
			} else {
				// If not found, create a new coupon with a random code
				$coupon_code = 'AUTO_' . wp_generate_password( 8, false );
				$coupon = new WC_Coupon();
				$coupon->set_code( $coupon_code );
				$coupon->set_description( 'Auto-generated coupon.' );
				$coupon->set_discount_type( 'percent' );
				$coupon->set_amount( $fixed_cart_amount );
				$coupon->save();
				return $coupon_code;
			}
		}
		return '';
	}
}
