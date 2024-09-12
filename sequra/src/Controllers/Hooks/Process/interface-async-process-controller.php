<?php
/**
 * Report Controller interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers
 */

namespace SeQura\WC\Controllers\Hooks\Process;

/**
 * Report Controller interface
 */
interface Interface_Async_Process_Controller {

	/**
	 * Handle an request to webhook to run async process
	 */
	public function handle_async_process_webhook(): void;

	/**
	 * Resume task runner
	 */
	public function resume_task_runner(): void;
	
	/**
	 * Halt task runner
	 */
	public function halt_task_runner(): void;
}
