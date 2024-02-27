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
		$jsonData  = wp_json_encode( $data, JSON_PRETTY_PRINT );
		$logDir    = RMS_DIR_PATH . 'logs/';
		if ( ! file_exists( $logDir ) ) {
			wp_mkdir_p( $logDir );
		}
		$logDir     = $logDir . $filename . '.log';
		$logMessage = sprintf( '%s - %s %s', $timestamp, $jsonData, PHP_EOL );
		error_log( $logMessage, 3, $logDir );
	}
}
