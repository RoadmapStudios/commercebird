<?php

namespace RMS\Admin;

use Exception;
use RMS\Admin\Connectors\CommerceBird;
use RMS\Admin\Traits\Singleton;

final class Order {
	use Singleton;

	public function __construct() {
//		Action hook fired after an order is created used to add custom meta to the order.
		add_action( 'woocommerce_checkout_update_order_meta', [ $this, 'add_meta' ] );
	}


	/**
	 * Add the product meta as order item meta when order is created
	 * @throws Exception
	 */
	public function item_meta( $order_id, $data ) {
		$order = wc_get_order( $order_id );
		// Get the product ID associated with the order item
		$user_id = $order->get_customer_id();
		foreach ( $order->get_items() as $item ) {
			$product_id = $item->get_product_id();
			// Check if the product is associated with a product
			if ( $product_id > 0 ) {
				// Get the product meta value based on the product ID and meta key
				$product_meta_value = get_post_meta( $product_id, 'eo_item_id', true );
				//	Get item id from api
				if ( empty( $product_meta_value ) ) {
					$response = ( new CommerceBird() )->item_id( $product_id );
					if ( ! empty( $response ) ) {
						$product_meta_value = $response['item_id'];
						update_post_meta( $product_id, 'eo_item_id', $product_meta_value );
					}
				}
				// Add the product meta as order item meta
				if ( ! empty( $product_meta_value ) ) {
					wc_add_order_item_meta( $product_id, 'eo_item_id', $product_meta_value );
				}
			}
		}

		// Check if the order is associated with a user
		if ( $user_id > 0 ) {
			// Get the user meta value based on the user ID and meta key
			$user_meta_value    = get_user_meta( $user_id, 'eo_account_id', true );
			$company_meta_value = get_user_meta( $user_id, 'eo_company_id', true );

			if ( empty( $user_meta_value ) ) {
				$customer = array( 'customer_email' => $order->get_billing_email() );
				if ( $order->get_billing_company() ) {
					$customer['company_name'] = $order->get_billing_company();
				}
				$response = ( new CommerceBird() )->customer( $customer );
				if ( ! empty( $response ) ) {
					$user_meta_value = $response['account_id'];
					update_user_meta( $user_id, 'eo_account_id', $user_meta_value );
					if ( isset( $response['company_id'] ) ) {
						update_user_meta( $user_id, 'eo_company_id', $response['company_id'] );
					}
				}
			}

			// Add the user meta as order meta
			if ( ! empty( $user_meta_value ) ) {
				$order->update_meta_data( 'eo_account_id', $user_meta_value );
				$order->update_meta_data( 'eo_company_id', $company_meta_value );
				$order->save();
			}
		}

	}


}