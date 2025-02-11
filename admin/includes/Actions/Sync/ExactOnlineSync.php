<?php

namespace CommerceBird\Admin\Actions\Sync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CommerceBird\Admin\Actions\Ajax\ExactOnlineAjax;
use CommerceBird\Admin\Connectors\CommerceBird;

class ExactOnlineSync {


	/**
	 * Sync data from Exact Online.
	 *
	 * @param string $type product|customer
	 * @param array $data to sync from Exact Online
	 * @param bool $import import or update
	 * @return mixed
	 */
	public static function sync( string $type, array $data, bool $import = false ) {
		if ( empty( $type ) ) {
			return false;
		}
		if ( $import ) {
			self::import( $type, $data );
		} else {
			foreach ( $data as $item ) {
				self::update( $type, $item );
			}
		}
	}
	/**
	 * Import data from Exact Online.
	 *
	 * for product data will be like,
	 * {
	 * "Code":string,
	 * "Description": string,
	 * "ID": string,
	 * "IsSalesItem": bool,
	 * "PictureName": null|string,
	 * "PictureUrl": string,
	 * "StandardSalesPrice": float,
	 * "Stock": int
	 * },
	 *
	 * for Order data will be like,
	 * {
	 * "MainContact": null|string,
	 * "Email": null|string,
	 * "ID": string,
	 * "Name": string,
	 * "AddressLine1": null|string,
	 * "AddressLine2": null|string,
	 * "City": string,
	 * "Country": string,
	 * "Phone": null|string,
	 * "Postcode": string
	 * }
	 * @param string $type of provided data;
	 * @param array $data of import
	 * @return mixed
	 */
	public static function import( string $type, array $data ) {
		$endpoint = '';
		$payload = array();

		switch ( $type ) {
			case 'product':
				$endpoint = '/wc/v3/products/batch';
				$payload = array(
					'create' => array_map( function ($item) {
						$images = array();
						// Skip image import if PictureName is 'placeholder_item'
						if ( isset( $item['PictureName'] ) && $item['PictureName'] !== 'placeholder_item' ) {
							$image_id = self::get_existing_image_id( $item['PictureName'] );

							if ( ! $image_id && ! empty( $item['PictureUrl'] ) ) {
								$image_id = self::upload_image( $item['PictureUrl'], $item['PictureName'] );
							}

							if ( $image_id ) {
								$images[] = array( 'id' => $image_id );
							}
						}
						return array(
							'name' => $item['Description'],
							'sku' => $item['Code'],
							'status' => 'publish',
							'type' => 'simple',
							'regular_price' => (string) $item['StandardSalesPrice'],
							'images' => $images,
							'meta_data' => array(
								array(
									'key' => 'eo_item_id',
									'value' => $item['ID'],
								),
								array(
									'key' => '_cost_price',
									'value' => $item['CostPriceStandard'],
								),
								array(
									'key' => 'eo_unit',
									'value' => $item['Unit'],
								),
							),
						);
					}, $data )
				);
				break;

			case 'customer':
				$endpoint = '/wc/v3/customers/batch';
				$payload = array(
					'create' => array_map( function ($item) {
						if ( empty( $item['Email'] ) ) {
							return null;
						}
						$names = explode( ' ', $item['Name'] );
						$first_name = $names[0] ?? '';
						$last_name = $names[1] ?? '';
						$address = array(
							'first_name' => $first_name,
							'last_name' => $last_name,
							'address_1' => $item['AddressLine1'] ?? '',
							'address_2' => $item['AddressLine2'] ?? '',
							'city' => $item['City'],
							'country' => $item['Country'],
							'postcode' => $item['Postcode'],
							'phone' => $item['Phone'] ?? '',
							'email' => $item['Email'],
						);
						return array(
							'email' => $item['Email'],
							'first_name' => $first_name,
							'last_name' => $last_name,
							'billing' => $address,
							'shipping' => $address,
							'meta_data' => array(
								array(
									'key' => 'eo_account_id',
									'value' => $item['ID'],
								),
								array(
									'key' => 'eo_contact_id',
									'value' => $item['MainContact'] ?? '',
								),
							),
						);
					}, array_filter( $data, fn( $item ) => ! empty( $item['Email'] ) ) )
				);
				break;

			default:
				return false;
		}

		if ( empty( $endpoint ) || empty( $payload['create'] ) ) {
			return false;
		}

		$request = new \WP_REST_Request( 'POST', $endpoint );
		$request->set_body_params( $payload );
		return rest_do_request( $request );
	}

	/**
	 * Update data based on Exact Online.
	 * @param string $type of provided data
	 * @param array $data to match
	 * @return void
	 */
	public static function update( string $type, array $data ) {
		switch ( $type ) {
			case 'product':
				$wc_product_id = wc_get_product_id_by_sku( $data['Code'] );
				if ( empty( $wc_product_id ) ) {
					$wc_product_id = self::get_product_id_by_title( $data['Description'] );
				}
				if ( ! empty( $wc_product_id ) ) {
					update_post_meta( $wc_product_id, 'eo_item_id', $data['ID'] );
					update_post_meta( $wc_product_id, '_cost_price', $data['CostPriceStandard'] );
					update_post_meta( $wc_product_id, 'eo_unit', $data['Unit'] );
					$wc_product = wc_get_product( $wc_product_id );
					$wc_product->set_regular_price( $data['StandardSalesPrice'] );
					$stock = $data['Stock'];
					if ( is_numeric( $stock ) ) {
						$wc_product->set_manage_stock( true );
						$wc_product->set_stock_quantity( $data['Stock'] );
						if ( $stock > 0 ) {
							$wc_product->set_stock_status( 'instock' );
						} else {
							$backorder_status = $wc_product->backorders_allowed();
							$status = ( $backorder_status === 'yes' ) ? 'onbackorder' : 'outofstock';
							$wc_product->set_stock_status( $status );
						}
					}
					$wc_product->save();
				}
				break;
			case 'customer':
				$user = get_user_by( 'email', $data['Email'] );
				if ( empty( $user ) ) {
					break;
				}
				$user_id = $user->ID;
				update_user_meta( $user_id, 'eo_account_id', $data['ID'] );
				if ( ! empty( $data['MainContact'] ) ) {
					update_user_meta( $user_id, 'eo_contact_id', $data['MainContact'] );
				}
				break;
			case 'orders':
				$order = wc_get_order( $data['Description'] );
				if ( empty( $order ) ) {
					break;
				}
				$order->update_meta_data( 'eo_order_id', $data['OrderID'] );
				$order->update_meta_data( 'eo_order_number', $data['OrderNumber'] );
				$order->save();
				break;
			default:
				break;
		}
	}

	private static function get_product_id_by_title( string $product_title ) {
		// Set up the query arguments
		$args = array(
			'post_type' => 'product',
			'posts_per_page' => 1,
			'fields' => 'ids',
			's' => $product_title, // Search by product title
		);

		// Run the query
		$query = new \WP_Query( $args );

		// Get the product ID from the query results
		$product_id = $query->post_count > 0 ? $query->posts[0] : 0;

		// Reset post data
		wp_reset_postdata();

		return $product_id;
	}

	public static function get_payment_status_via_cron() {
		// execute get_payment_status of ExactOnlineAjax class
		$ajax = new ExactOnlineAjax();
		$ajax->get_payment_status();
	}

	/**
	 * Process the payment status of the order via Exact Online.
	 * @param array $
	 * @return void
	 */
	public static function cmbird_payment_status() {
		$args = func_get_args();
		$order_id = $args[0];
		if ( empty( $order_id ) ) {
			return;
		}
		$order = wc_get_order( $order_id );
		$object = array();
		$order_id = $order->get_id();
		$object['OrderID'] = $order_id;
		$customer_id = $order->get_customer_id();
		// get the eo_account_id from the user meta
		$object['AccountID'] = get_user_meta( $customer_id, 'eo_account_id', true );
		$response = ( new CommerceBird() )->payment_status( $object );
		// check response contains "Payment_Status" key
		if ( ! isset( $response['Payment_Status'] ) ) {
			return;
		}
		// if response is Paid then update the order status to completed
		if ( 'Paid' === $response['Payment_Status'] ) {
			// set order as paid
			if ( $order->get_status() === 'completed' ) {
				return;
			}
			$order->payment_complete();
			$order->update_status( 'completed', __( 'Payment processed in Exact Online', 'commercebird' ) );
			$order->save();
		} elseif ( 'Unpaid' === $response['Payment_Status'] ) {
			if ( $order->get_status() === 'on-hold' ) {
				return;
			}
			$order->update_status( 'on-hold', __( 'Payment not processed in Exact Online', 'commercebird' ) );
			$order->save();
		}
	}

	/**
	 * Check if the image exists in the media library.
	 * @param string $picture_name
	 * @return $ID of the image if it exists, otherwise false
	 */
	private static function get_existing_image_id( $picture_name ) {
		global $wpdb;

		$query = $wpdb->prepare( "
        SELECT ID FROM {$wpdb->posts}
        WHERE post_type = 'attachment'
        AND post_title = %s
        LIMIT 1
    	", $picture_name );

		$attachment_id = $wpdb->get_var( $query );
		return $attachment_id ? $attachment_id : false;
	}

	/**
	 * Upload the product image from Exact Online.
	 * @param string $product_id
	 * @param string $picture_name
	 * @return $attachment_id of the uploaded image if successful, otherwise false
	 */
	private static function upload_image( $picture_url, $picture_name ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Upload the image and return the media ID
		$attachment_id = media_sideload_image( $picture_url, 0, $picture_name, 'id' );

		return is_wp_error( $attachment_id ) ? false : $attachment_id;
	}
}
