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
 * Class Logger_Service
 */
class Logger_Service implements LoggerAdapter {

	/**
	 * Returns log file name.
	 *
	 * @return string Log file name.
	 */
	public static function get_log_file() {
		return 'sequra.log';
	}

	/**
	 * Log message in system.
	 *
	 * @param LogData $data Log data.
	 */
	public function logMessage( LogData $data ) {
		// TODO: Implement logMessage() method.
	}
}
