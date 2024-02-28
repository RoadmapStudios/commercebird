<?php

namespace RMS\Admin\Traits;

trait LogWriter {
	/**
	 * Logs error messages with timestamp and data.
	 *
	 * @param mixed $data The data to be encoded and logged.
	 * @param $filename
	 *
	 * @return void
	 */
	private function write_log( $data, $filename ): void {
		$timestamp = gmdate( 'Y-m-d H:i:s' );
		$json_data = wp_json_encode( $data, JSON_PRETTY_PRINT );
		$log_dir   = RMS_DIR_PATH . 'logs/';
		if ( ! file_exists( $log_dir ) ) {
			wp_mkdir_p( $log_dir );
		}
		$log_dir     = $log_dir . $filename . '.log';
		$log_message = sprintf( '%s - %s %s', $timestamp, $json_data, PHP_EOL );
		error_log( $log_message, 3, $log_dir );
	}
}
