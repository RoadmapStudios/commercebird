<?php

namespace RMS\Admin\Actions\Sync;

defined( 'RMS_PLUGIN_NAME' ) || exit;

class ExactOnlineSync {
	public static function sync_products( array $products, bool $import = false ) {
		error_log( wp_json_encode( array( $import, $products ), 128 ) . PHP_EOL, 3, __DIR__ . '/debug.log' );
	}
}
