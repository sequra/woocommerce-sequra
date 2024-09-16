<?php
/**
 * Report service interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Report;

/**
 * Report service
 */
interface Interface_Report_Service {

	/**
	 * Get store id for current context
	 *
	 * @throws Throwable
	 */
	public function send_delivery_report_for_current_store(): void;
}
