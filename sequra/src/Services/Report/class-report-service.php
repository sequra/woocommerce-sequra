<?php
/**
 * Report service interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Report;

use SeQura\Core\Infrastructure\TaskExecution\Interfaces\TaskRunnerManager;
use SeQura\WC\Core\Extension\Infrastructure\Configuration\Configuration;

/**
 * Report service
 */
class Report_Service implements Interface_Report_Service {

	/**
	 * Configuration
	 *
	 * @var Configuration
	 */
	private $configuration;

	/**
	 * Task runner manager
	 *
	 * @var TaskRunnerManager
	 */
	private $task_runner_manager;

	/**
	 * Constructor.
	 */
	public function __construct( Configuration $configuration, TaskRunnerManager $task_runner_manager ) {
		$this->configuration       = $configuration;
		$this->task_runner_manager = $task_runner_manager;
	}

	/**
	 * Get webhook to process async reports
	 */
	public function get_async_process_webhook(): string {
		return 'sequra_async_process';
	}

	/**
	 * Check if the task runner is halted and resume it
	 */
	public function resume_task_runner(): void {
		if ( $this->configuration->isTaskRunnerHalted() ) {
			$this->task_runner_manager->resume();
		}
	}

	/**
	 * Halt the task runner
	 */
	public function halt_task_runner(): void {
		$this->task_runner_manager->halt();
	}
}
