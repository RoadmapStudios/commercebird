<?php
// Function for image support.
if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
	require_once ABSPATH . 'wp-admin/includes/image.php';
}
if ( ! function_exists( 'wp_insert_attachment' ) ) {
	require_once ABSPATH . 'wp-includes/post.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
}
if ( ! function_exists( 'download_url' ) ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
}

/**
 * Class for All Product Data from Woo To Zoho
 *
 * @package  WooZo Inventory
 */

class ImageClass {

	private $config;
	public function __construct() {
		$this->config = array(
			'ProductZI' => array(
				'OID'    => get_option( 'zoho_inventory_oid' ),
				'APIURL' => get_option( 'zoho_inventory_url' ),
			),
		);
	}

	/**
	 * Attach image from zoho
	 *
	 * @param [string] $item_id - Item id for image details.
	 * @param [string] $item_name - Item name.
	 * @param [string] $post_id - Post id of image.
	 * @param [string] $image_name - Image name.
	 * @param [string] $author_id - Author id
	 * @return void
	 */
	public function args_attach_image( $item_id, $item_name, $post_id, $author_id ) {
		// $fd = fopen(__DIR__ . '/image_sync.txt', 'a+');
		global $wpdb;
		$zoho_inventory_oid = $this->config['ProductZI']['OID'];
		$zoho_inventory_url = $this->config['ProductZI']['APIURL'];
		$url                = $zoho_inventory_url . 'api/v1/items/' . $item_id . '/image';
		$url               .= '?organization_id=' . $zoho_inventory_oid;

		// fwrite($fd, PHP_EOL . '$url : ' . $url);
		$execute_curl_call_handle = new ExecutecallClass();
		$image_url                = $execute_curl_call_handle->ExecuteCurlCallImageGet( $url, $item_id );
		// fwrite($fd, PHP_EOL . 'Sync init');
		$temp_file = download_url( $image_url );
		// Get the MIME type of the downloaded image
		$file_info = getimagesize( $temp_file );
		if ( $file_info && isset( $file_info['mime'] ) ) {
			$file_type = $file_info['mime'];
		} else {
			// If the MIME type cannot be determined, handle the error accordingly
			// For example, you can log an error message and return from the function
			return;
		}
		// fwrite($fd, PHP_EOL . 'File Type: ' . $file_type);
		$img_extension = $this->get_extension( $file_type );
		if ( $img_extension ) {
			$image_name        = '' . $item_id . '' . $img_extension;
			$image_name_scaled = '' . $item_id . '-scaled' . $img_extension;
		} else {
			return;
		}

		$attach_id               = intval( get_post_meta( $post_id, 'zoho_product_image_id', true ) );
		$image_exists_in_library = $this->compare_image_with_media_library( $temp_file );

		if ( $image_exists_in_library ) {
			$attach_id = $image_exists_in_library;
			wp_delete_file( $temp_file );
		} else {
			$image_post_id        = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' and meta_value LIKE %s",
					'%' . $wpdb->esc_like( $image_name ) . '%'
				)
			);
			$image_post_scaled_id = intval( $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' and meta_value LIKE %s", '%' . $image_name_scaled . '%' ) ) );
			$image_post_id        = ( 0 === $image_post_id ) ? $image_post_scaled_id : $image_post_id;
		}
		// fwrite($fd,PHP_EOL.'$image_post_id : '.$image_post_id);
		// fwrite($fd, PHP_EOL . '$image_post_id: ' . $image_post_id);

		if ( 0 === $image_post_id ) {
			if ( ! is_wp_error( $temp_file ) ) {
				// fwrite($fd, PHP_EOL . 'Inside If: ');
				// Set variables for storage, fix file filename for query strings.
				$file = array(
					'name'     => $image_name,
					'type'     => $file_type,
					'tmp_name' => $temp_file,
					'error'    => 0,
					'size'     => filesize( $temp_file ),
				);

				$overrides = array(
					// Tells WordPress to not look for the POST form
					'test_form' => false,
					// Setting this to false lets WordPress allow empty files, not recommended.
					'test_size' => true,
				);

				// fwrite($fd, PHP_EOL . 'Before handle sideload: ');
				// Move the temporary file into the uploads directory.
				$results = wp_handle_sideload( $file, $overrides );

				if ( ! empty( $results['error'] ) ) {
					// fwrite($fd, PHP_EOL . 'Error: ');
					return;
				} else {
					// fwrite($fd, PHP_EOL . 'Inside If: ');
					$file_dir  = $results['file']; // Full path to the file.
					$file_url  = $results['url']; // URL to the file in the uploads dir.
					$file_type = $results['type']; // MIME type of the file.

					$attachment    = array( // Set up images post data.
						'guid'           => $file_url,
						'post_mime_type' => $file_type,
						'post_title'     => $item_name,
						'post_author'    => $author_id,
						'post_content'   => '',
					);
					$attachment_id = wp_insert_attachment( $attachment, $file_dir, $post_id );
					// fwrite($fd, PHP_EOL . 'Insert Attachment: ' . $attachment_id);

					if ( ! is_wp_error( $attachment_id ) ) {
						// Generate the necessary attachment data, filesize, height, width etc.
						$attach_data = wp_generate_attachment_metadata( $attachment_id, $file_dir );
						// Add/update the above meta data to new image post.
						wp_update_attachment_metadata( $attachment_id, $attach_data );
						update_post_meta( $attachment_id, '_wp_attachment_image_alt', $item_name );
						update_post_meta( $post_id, 'zoho_product_image_id', $attachment_id );
						update_post_meta( $post_id, '_thumbnail_id', $attach_id );
						set_post_thumbnail( $post_id, $attachment_id );
						wp_update_image_subsizes( $attachment_id );

						// Remove all files from zoho folder when done
						$upload      = wp_upload_dir();
						$folder_path = $upload['basedir'] . '/zoho_image/';
						// Get list of file paths in the folder
						$file_paths = glob( $folder_path . '*' );
						// Loop through each file and delete it
						foreach ( $file_paths as $file_path ) {
							if ( is_file( $file_path ) ) {
								wp_delete_file( $file_path );
							}
						}
						return;
					}
				}
			} else {
				return;
			}
		} else {
			set_post_thumbnail( $post_id, $image_post_id );
			return;
		}
		// fclose($fd);
		return;
	}

	/**
	 * Compare the image with existing media library images based on titles.
	 *
	 * @param string $image_path The path to the image to be checked.
	 * @return int|bool The ID of the existing image if a match is found, or false if no match is found.
	 * @since 1.0.0
	 */
	protected function compare_image_with_media_library( $image_path ) {
		// Get the title of the image you want to check
		$compare_image_title = sanitize_file_name( pathinfo( $image_path, PATHINFO_FILENAME ) );

		if ( ! empty( $compare_image_title ) ) {
			$args                 = array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'posts_per_page' => -1,
			);
			$media_library_images = get_posts( $args );

			foreach ( $media_library_images as $media_image ) {
				// Get the title of the existing image
				$existing_image_path  = get_attached_file( $media_image->ID );
				$existing_image_title = get_the_title( pathinfo( $existing_image_path )['filename'] );

				// Compare the titles
				if ( $compare_image_title === $existing_image_title ) {
					return $media_image->ID; // Return the ID of the existing image
				}
			}
		}

		// If no match is found in the loop, return false
		return false;
	}

	/**
	 * Get image file extension from mimetype
	 *
	 * @param String $imagetype - Image mime type.
	 * @return extension or false
	 */
	public function get_extension( $imagetype ) {
		if ( empty( $imagetype ) ) {
			return false;
		}
		switch ( $imagetype ) {
			case 'image/bmp':
				return '.bmp';
			case 'image/cis-cod':
				return '.cod';
			case 'image/gif':
				return '.gif';
			case 'image/ief':
				return '.ief';
			case 'image/jpeg':
				return '.jpg';
			case 'image/pipeg':
				return '.jfif';
			case 'image/tiff':
				return '.tif';
			case 'image/x-cmu-raster':
				return '.ras';
			case 'image/x-cmx':
				return '.cmx';
			case 'image/x-icon':
				return '.ico';
			case 'image/x-portable-anymap':
				return '.pnm';
			case 'image/x-portable-bitmap':
				return '.pbm';
			case 'image/x-portable-graymap':
				return '.pgm';
			case 'image/x-portable-pixmap':
				return '.ppm';
			case 'image/x-rgb':
				return '.rgb';
			case 'image/x-xbitmap':
				return '.xbm';
			case 'image/x-xpixmap':
				return '.xpm';
			case 'image/x-xwindowdump':
				return '.xwd';
			case 'image/png':
				return '.png';
			case 'image/x-jps':
				return '.jps';
			case 'image/x-freehand':
				return '.fh';
			case 'image/webp':
				return '.webp';
			default:
				return false;
		}
	}
}
