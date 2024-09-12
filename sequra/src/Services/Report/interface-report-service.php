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
	 * Get webhook to process async reports
	 */
	public function get_async_process_webhook(): string;

	/**
	 * Check if the task runner is halted and resume it
	 */
	public function resume_task_runner(): void;

	/**
	 * Halt the task runner
	 */
	public function halt_task_runner(): void;
}
