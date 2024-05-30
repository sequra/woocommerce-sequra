<?php
/**
 * Logger service.
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Services\Core;

use SeQura\Core\Infrastructure\Logger\Interfaces\LoggerAdapter;
use SeQura\Core\Infrastructure\Logger\LogData;

/**
 * Logger
 */
class Logger implements LoggerAdapter {

	/**
	 * Returns log file name.
	 */
	public static function get_log_file(): string {
		return 'sequra.log';
	}

	/**
	 * Log message in system.
	 *
	 * @param LogData $data Log data.
	 * @return void
	 */
	public function logMessage( LogData $data ) {
		// TODO: Implement logMessage() method.
	}
}
