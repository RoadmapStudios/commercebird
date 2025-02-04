<?php

namespace CommerceBird\Admin;

/*
|--------------------------------------------------------------------------
| If this file is called directly, abort.
|--------------------------------------------------------------------------
 */

use CommerceBird\Admin\Traits\Singleton;

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

		$option = (bool) get_option( 'cmbird_zoho_cors_status', 0 );
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
}
