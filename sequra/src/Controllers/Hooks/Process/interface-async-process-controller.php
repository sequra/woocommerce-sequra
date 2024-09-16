<?php
/**
 * Async Process Controller interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers
 */

namespace SeQura\WC\Controllers\Hooks\Process;

/**
 * Async Process Controller interface
 */
interface Interface_Async_Process_Controller {

	/**
	 * Send the delivery report
	 */
	public function send_delivery_report(): void;
}
