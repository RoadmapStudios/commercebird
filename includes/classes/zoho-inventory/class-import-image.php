<?php
/**
 * CommerceBird
 *
 * @package  CommerceBird
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for All Product Data from Woo To Zoho
 *
 * @package  WooZo Inventory
 */

class CMBIRD_Image_ZI {

	private $config;
	public function __construct() {
		$this->config = array(
			'ProductZI' => array(
				'OID' => get_option( 'cmbird_zoho_inventory_oid' ),
				'APIURL' => get_option( 'cmbird_zoho_inventory_url' ),
			),
		);
	}

	/**
	 * Attach image from zoho
	 *
	 * @param [string] $item_id - Item id for image details.
	 * @param [string] $item_name - Item name.
	 * @param [string] $post_id - Post id of product.
	 * @param [string] $image_name - Image name.
	 * @return integer | void
	 */
	public function cmbird_zi_get_image( $item_id, $item_name, $post_id, $image_name ) {
		// $fd = fopen( __DIR__ . '/image_sync.txt', 'a+' );

		$attachment_id = $this->compare_image_with_media_library( $item_name, $image_name );
		if ( $attachment_id ) {
			set_post_thumbnail( $post_id, $attachment_id );
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $item_name );
			update_post_meta( $post_id, '_thumbnail_id', $attachment_id );
			wp_update_image_subsizes( $attachment_id );
			// also delete the zoho_image folder files.
			$upload = wp_upload_dir();
			$folder_path = $upload['basedir'] . '/zoho_image/';
			$file_paths = glob( $folder_path . '/*' );
			foreach ( $file_paths as $file_path ) {
				if ( is_file( $file_path ) ) {
					wp_delete_file( $file_path );
				}
			}
			return $attachment_id;
		}

		$zoho_inventory_oid = $this->config['ProductZI']['OID'];
		$zoho_inventory_url = $this->config['ProductZI']['APIURL'];
		$url = $zoho_inventory_url . 'inventory/v1/items/' . $item_id . '/image';
		$url .= '?organization_id=' . $zoho_inventory_oid;

		$execute_curl_call_handle = new CMBIRD_API_Handler_Zoho();
		$image_url = $execute_curl_call_handle->execute_curl_call_image_get( $url, $image_name );
		if ( empty( $image_url ) ) {
			return;
		}
		$attachment_id = media_sideload_image( $image_url, $post_id, $image_name, 'id' );
		if ( $attachment_id ) {
			set_post_thumbnail( $post_id, $attachment_id );
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $item_name );
			update_post_meta( $post_id, '_thumbnail_id', $attachment_id );
			wp_update_image_subsizes( $attachment_id );
			// also delete the zoho_image folder files.
			$upload = wp_upload_dir();
			$folder_path = $upload['basedir'] . '/zoho_image/';
			$file_paths = glob( $folder_path . '/*' );
			foreach ( $file_paths as $file_path ) {
				if ( is_file( $file_path ) ) {
					wp_delete_file( $file_path );
				}
			}
			return $attachment_id;
		}
	}

	/**
	 * Compare the image with existing media library images based on titles.
	 *
	 * @param string $item_image The name of the image.
	 * @return int|bool The ID of the existing image if a match is found, or false if no match is found.
	 * @since 1.0.0
	 */
	protected function compare_image_with_media_library( $item_name, $item_image ) {

		if ( ! empty( $item_image ) ) {
			$args = array(
				'post_type' => 'attachment',
				'post_mime_type' => 'image',
				'posts_per_page' => -1,
			);
			$media_library_images = get_posts( $args );
			foreach ( $media_library_images as $media_image ) {
				// Get the postmeta zoho_product_image_id of the existing image
				$existing_image_title = get_the_title( $media_image->ID );
				if ( strpos( $existing_image_title, $item_name ) !== false ) {
					return $media_image->ID; // Return the ID of the existing image
				}
				if ( strpos( $existing_image_title, $item_image ) !== false ) {
					return $media_image->ID; // Return the ID of the existing image
				}
			}
		}
		// If no match is found in the loop, return false
		return false;
	}
}
