<?php
/**
 * Shop Logger Adapter
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Implementation\Infrastructure\Logger\Interfaces;

use SeQura\Core\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use SeQura\Core\Infrastructure\Logger\LogData;

/**
 * Shop Logger Adapter
 */
class Shop_Logger_Adapter implements ShopLoggerAdapter {

	/**
	 * Log message in system.
	 *
	 * @param LogData $data Log data.
	 * @return void
	 */
	public function logMessage( LogData $data ) {
		// Do nothing.
	}
}
