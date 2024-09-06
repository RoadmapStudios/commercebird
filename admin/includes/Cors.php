<?php

namespace RMS\Admin;

/*
|--------------------------------------------------------------------------
| If this file is called directly, abort.
|--------------------------------------------------------------------------
 */

use RMS\Admin\Traits\Singleton;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Cors
 *
 * @package Enable\Cors
 */
final class Cors {
	use Singleton;

	public function __construct() {

		$option = (bool) get_option( 'zoho_cors_status', 0 );
		if ( ! $option ) {
			return;
		}
		$this->headers();
		add_filter( 'rest_pre_serve_request', array( $this, 'headers' ) );
	}

	/**
	 * It sets headers for Cross-Origin Resource Sharing (CORS) based on options set in the
	 * plugin's settings.
	 *
	 * @return void If the `` variable is empty, the function will return nothing (void).
	 */
	public function headers(): void {

		header( 'Access-Control-Allow-Origin: *', true );
		header( 'Access-Control-Allow-Methods: *', true );
		header( 'Access-Control-Allow-Headers: *', true );
		header( 'Access-Control-Allow-Credentials: true', true );
	}

	/**
	 * It modifies the .htaccess file to add headers for allowing fonts and css
	 */
	public function modify_htaccess() {
		$option = (bool) get_option( 'zoho_cors_status', 0 );
		if ( $option ) {
			$lines = array(
				'<IfModule mod_headers.c>',
				'<FilesMatch "\.(ttf|ttc|otf|eot|woff|font.css|css|woff2)$">',
				'Header set Access-Control-Allow-Origin "*"',
				'Header set Access-Control-Allow-Credentials "true"',
				'</FilesMatch>',
				'</IfModule>',
				'<IfModule mod_headers.c>',
				'<FilesMatch "\.(avifs?|bmp|cur|gif|ico|jpe?g|jxl|a?png|svgz?|webp)$">',
				'Header set Access-Control-Allow-Origin "*"',
				'Header set Access-Control-Allow-Credentials "true"',
				'</FilesMatch>',
				'</IfModule>',
			);
		} else {
			$lines = array( '' );
		}

		// Ensure get_home_path() is declared.
		$this->write_htaccess( $lines );
	}

	/**
	 * Inserts an array of strings into a file (.htaccess), placing it between
	 * BEGIN and END markers.
	 *
	 * @param array $lines need to write.
	 *
	 * @return void
	 */
	private function write_htaccess( array $lines ): void {
		// Ensure get_home_path() is declared.
		if ( ! function_exists( 'get_home_path' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		if ( ! function_exists( 'insert_with_markers' ) || ! function_exists( 'got_mod_rewrite' ) ) {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
		}

		$htaccess_file = get_home_path() . '.htaccess';

		if ( got_mod_rewrite() ) {
			insert_with_markers( $htaccess_file, RMS_MENU_SLUG, $lines );
		}
	}

	/**
	 * It writes an empty array to the .htaccess file.
	 */
	public function restore_htaccess() {
		$lines = array( '' );
		$this->write_htaccess( $lines );
	}
}
