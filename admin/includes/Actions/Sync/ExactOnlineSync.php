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
			self::update( $type, $data );
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
		// add logging
		// $fd = fopen( __DIR__ . '/import.txt', 'w+' );
		$endpoint = '';
		$payload = array();

		switch ( $type ) {
			case 'product':
				$endpoint = '/wc/v3/products/batch';
				$filtered_data = array_filter( $data, function ($item) {
					// Exclude item if product already exists via SKU
					return ! wc_get_product_id_by_sku( $item['Code'] );
				} );
				$payload = array(
					'create' => array_map( function ($item) {
						// Skip image import if PictureName is 'placeholder_item'
						if ( isset( $item['PictureName'] ) && $item['PictureName'] !== 'placeholder_item' ) {
							$images = array();
							$image_id = self::get_existing_image_id( $item['PictureName'] );
							// If image exists, add it to the images array, else upload the image and add it to the images array
							if ( $image_id ) {
								$images[] = array( 'id' => $image_id );
							} else {
								$image_id = self::upload_image( $item['PictureUrl'], $item['PictureName'] );
								// If image upload is successful, add it to the images array
								if ( $image_id ) {
									$images[] = array( 'id' => $image_id );
								}
							}
						}
						// Check if category exists with $item['ItemGroupDescription'], else create it and get the ID
						if ( isset( $item['ItemGroupDescription'] ) ) {
							$term = get_term_by( 'name', $item['ItemGroupDescription'], 'product_cat' );
							$term_id = $term->term_id;
							if ( empty( $term_id ) ) {
								$term = wp_insert_term(
									$item['ItemGroupDescription'],
									'product_cat',
									array(
										'parent' => 0,
									)
								);
								$term_id = $term['term_id'];
							}
						} else {
							$term_id = 0;
						}
						// if stock exists then set manage_stock to true
						$manage_stock = isset( $item['Stock'] ) ? true : false;
						return array(
							'name' => $item['Description'],
							'sku' => $item['Code'],
							'status' => 'publish',
							'type' => 'simple',
							'regular_price' => (string) $item['StandardSalesPrice'],
							'images' => $images,
							'stock_quantity' => $item['Stock'],
							'manage_stock' => $manage_stock,
							'categories' => array(
								array(
									'id' => $term_id,
								),
							),
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
					}, $filtered_data )
				);
				break;

			case 'customer':
				$endpoint = '/wc/v3/customers/batch';
				$filtered_data = array_filter( $data, function ($item) {
					// Exclude item if customer already exists via email
					return ! get_user_by( 'email', $item['Email'] );
				} );
				$payload = array(
					'create' => array_map( function ($item) {
						if ( empty( $item['Email'] ) ) {
							return null;
						}
						if ( ! empty( $item['VATNumber'] ) ) {
							$first_name = '';
							$last_name = '';
							$company = $item['Name'];
						} else {
							$names = explode( ' ', $item['Name'] );
							$first_name = array_shift( $names ); // Take the first word as first name
							$last_name = implode( ' ', $names ); // Join the rest as last name
						}
						// generate username based on email
						$username = explode( '@', $item['Email'] )[0];
						$address = array(
							'first_name' => $first_name,
							'last_name' => $last_name,
							'company' => $company ?? '',
							'address_1' => $item['AddressLine1'] ?? '',
							'address_2' => $item['AddressLine2'] ?? '',
							'city' => $item['City'] ?? '',
							'country' => $item['Country'] ?? '',
							'postcode' => $item['Postcode'] ?? '',
							'phone' => $item['Phone'] ?? '',
							'email' => $item['Email'],
						);
						return array(
							'email' => $item['Email'],
							'first_name' => $first_name,
							'last_name' => $last_name,
							'username' => $username,
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
					}, array_filter( $filtered_data, fn( $item ) => ! empty( $item['Email'] ) ) )
				);
				break;

			default:
				return false;
		}

		if ( empty( $endpoint ) || empty( $payload['create'] ) ) {
			return false;
		}
		// log the payload
		// fwrite( $fd, print_r( $payload, true ) );
		$request = new \WP_REST_Request( 'POST', $endpoint );
		$request->set_body_params( $payload );
		$response = rest_do_request( $request );
		// error_log the message if response is error
		if ( is_wp_error( $response ) ) {
			error_log( 'Error ExactOnline Import: ' . $response->get_error_message() );
		}
		// fwrite( $fd, print_r( $response, true ) );
		// fclose( stream: $fd );
		return $response;
	}

	/**
	 * Update data based on Exact Online.
	 * @param string $type of provided data
	 * @return mixed
	 */
	public static function update( string $type, array $data ) {
		// $fd = fopen( __DIR__ . '/update.txt', 'w+' );
		$endpoint = '';
		$payload = array();
		// log data
		// fwrite( $fd, print_r( $data, true ) );

		switch ( $type ) {
			case 'product':
				$filtered_data = array_filter( $data, function ($item) {
					// Exclude item if product does not exists via SKU
					return wc_get_product_id_by_sku( $item['Code'] );
				} );
				$endpoint = '/wc/v3/products/batch';
				$payload = array(
					'update' => array_map( function ($item) {
						// Check if product exists via SKU, else get the product ID by title
						$product_id = wc_get_product_id_by_sku( $item['Code'] ) ?: self::get_product_id_by_title( $item['Description'] );
						// Featured image
						if ( isset( $item['PictureName'] ) && $item['PictureName'] !== 'placeholder_item' ) {
							$image_id = self::get_existing_image_id( $item['PictureName'] );
							// If image exists, add it to the images array, else upload the image and add it to the images array
							if ( $image_id ) {
								set_post_thumbnail( $product_id, $image_id );
								update_post_meta( $image_id, '_wp_attachment_image_alt', $item['Description'] );
								update_post_meta( $product_id, '_thumbnail_id', $image_id );
								wp_update_image_subsizes( $image_id );
							} else {
								$image_id = self::upload_image( $item['PictureUrl'], $item['PictureName'] );
								// If image upload is successful, add it to the images array
								if ( $image_id ) {
									set_post_thumbnail( $product_id, $image_id );
									update_post_meta( $image_id, '_wp_attachment_image_alt', $item['Description'] );
									update_post_meta( $product_id, '_thumbnail_id', $image_id );
									wp_update_image_subsizes( $image_id );
								}
							}
						}
						// update product category
						if ( isset( $item['ItemGroupDescription'] ) ) {
							// Check if term exists by name
							$term = get_term_by( 'name', $item['ItemGroupDescription'], 'product_cat' );
							$term_id = $term->term_id;
							if ( empty( $term_id ) ) {
								$term = wp_insert_term(
									$item['ItemGroupDescription'],
									'product_cat',
									array(
										'parent' => 0,
									)
								);
								$term_id = $term['term_id'];
							}
							// update product category directly
							wp_set_object_terms( $product_id, $term_id, 'product_cat' );
						}
						// update product name and slug if its different from current one
						$product = wc_get_product( $product_id );
						if ( $product->get_name() !== $item['Description'] ) {
							$product->set_name( $item['Description'] );
							$product->set_slug( sanitize_title( $item['Description'] ) );
							$product->save();
						}
						// create meta_data array if product does not contain eo_item_id meta
						$meta_data = get_post_meta( $product_id, 'eo_item_id', true );
						if ( empty( $meta_data ) ) {
							update_post_meta( $product_id, 'eo_item_id', $item['ID'] );
							update_post_meta( $product_id, '_cost_price', $item['CostPriceStandard'] );
							update_post_meta( $product_id, 'eo_unit', $item['Unit'] );
						}
						return array(
							'id' => $product_id,
							'regular_price' => (string) $item['StandardSalesPrice'],
						);
					}, $filtered_data )
				);
				break;

			case 'customer':
				$endpoint = '/wc/v3/customers/batch';
				$payload = array(
					'update' => array_map( function ($item) {
						$user = get_user_by( 'email', $item['Email'] );
						return $user ? array(
							'id' => $user->ID,
							'meta_data' => array(
								array( 'key' => 'eo_account_id', 'value' => $item['ID'] ),
								array( 'key' => 'eo_contact_id', 'value' => $item['MainContact'] ?? '' ),
							),
							// if $item['VATNumber'] is not empty then update the billing company name
							'billing' => ! empty( $item['VATNumber'] ) ? array(
								'company' => $item['Name'],
							) : null,
						) : null;
					}, array_filter( $data, fn( $item ) => get_user_by( 'email', $item['Email'] ) ) )
				);
				break;

			case 'orders':
				$endpoint = '/wc/v3/orders/batch';
				$payload = array(
					'update' => array_map( function ($item) {
						return array(
							'id' => $item['Description'],
							'meta_data' => array(
								array( 'key' => 'eo_order_id', 'value' => $item['OrderID'] ),
								array( 'key' => 'eo_order_number', 'value' => $item['OrderNumber'] ),
							),
						);
					}, $data )
				);
				break;

			default:
				return false;
		}
		// fwrite( $fd, print_r( $payload, true ) );
		if ( empty( $endpoint ) || empty( $payload['update'] ) ) {
			return false;
		}
		$request = new \WP_REST_Request( 'POST', $endpoint );
		$request->set_body_params( $payload );
		$response = rest_do_request( $request );
		// error_log the message if response is error
		if ( is_wp_error( $response ) ) {
			error_log( 'Error ExactOnline Update: ' . $response->get_error_message() );
		}
		// fwrite( $fd, 'response: ' . print_r( $response, true ) );
		// fclose( $fd );
		return $response;
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

		// sanitize the picture name
		$picture_name = sanitize_file_name( $picture_name );

		$query = $wpdb->prepare( "
        SELECT ID FROM {$wpdb->posts}
        WHERE post_type = 'attachment'
        AND post_title LIKE %s
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

		// Fetch image content
		$response = wp_safe_remote_get( $picture_url, array( 'timeout' => 10 ) );

		if ( is_wp_error( $response ) ) {
			error_log( 'Error fetching image: ' . $response->get_error_message() );
			return false;
		}

		$image_data = wp_remote_retrieve_body( $response );
		if ( empty( $image_data ) ) {
			return false;
		}

		// Generate filename
		$upload_dir = wp_upload_dir();
		$filename = sanitize_file_name( $picture_name );
		$file_path = $upload_dir['path'] . '/' . $filename;

		// Save the file
		file_put_contents( $file_path, $image_data );

		// Check if file was saved correctly
		if ( ! file_exists( $file_path ) ) {
			return false;
		}

		// Prepare file array for WordPress
		$file = array(
			'name' => $filename,
			'type' => mime_content_type( $file_path ),
			'tmp_name' => $file_path,
			'size' => filesize( $file_path ),
		);

		// Upload to WordPress Media Library
		$attachment_id = media_handle_sideload( $file, 0 );

		// Check for errors
		if ( is_wp_error( $attachment_id ) ) {
			error_log( 'Error attaching image: ' . $attachment_id->get_error_message() );
			return false;
		}

		return $attachment_id;
	}
}
