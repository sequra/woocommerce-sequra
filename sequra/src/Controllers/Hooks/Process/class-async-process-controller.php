<?php
/**
 * Report Controller
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers
 */

namespace SeQura\WC\Controllers\Hooks\Process;

use SeQura\Core\Infrastructure\Logger\LogContextData;
use SeQura\Core\Infrastructure\TaskExecution\Interfaces\AsyncProcessService;
use SeQura\Core\Infrastructure\TaskExecution\Interfaces\TaskRunnerManager;
use SeQura\WC\Controllers\Controller;
use SeQura\WC\Services\Interface_Logger_Service;
use SeQura\WC\Services\Report\Interface_Report_Service;

/**
 * Report Controller implementation
 */
class Async_Process_Controller extends Controller implements Interface_Async_Process_Controller {

	/**
	 * Async process service.
	 *
	 * @var AsyncProcessService
	 */
	private $async_process_service;

	/**
	 * Report service.
	 *
	 * @var Interface_Report_Service
	 */
	private $report_service;

	/**
	 * Constructor.
	 */
	public function __construct( 
		Interface_Logger_Service $logger, 
		string $templates_path, 
		AsyncProcessService $async_process_service,
		Interface_Report_Service $report_service
	) {
		$this->logger                = $logger;
		$this->templates_path        = $templates_path;
		$this->async_process_service = $async_process_service;
		$this->report_service        = $report_service;
	}

	/**
	 * Handle an request to webhook to run async process
	 */
	public function handle_async_process_webhook(): void {
		// phpcs:disable WordPress.Security.NonceVerification
		if ( ! isset( $_SERVER['REQUEST_METHOD'], $_REQUEST['action'], $_REQUEST['guid'] ) 
		|| 'POST' !== $_SERVER['REQUEST_METHOD'] 
		|| $this->report_service->get_async_process_webhook() !== $_REQUEST['action'] ) {
			return;
		}
		$this->logger->log_info( 'Hook executed', __FUNCTION__, __CLASS__ );
		$guid = trim( sanitize_text_field( wp_unslash( $_REQUEST['guid'] ?? '' ) ) );
		// phpcs:enable WordPress.Security.NonceVerification
		if ( '' === $guid ) {
			$this->logger->log_error( 'GUID is missing.', __FUNCTION__, __CLASS__ );
			wp_send_json( 'KO', 401 );
		}
		// $this->logger->log_info( 'Received async process request.', __FUNCTION__, __CLASS__, array( new LogContextData( 'guid', $guid ) ) );
		$this->async_process_service->runProcess( $guid );
		update_option( 'sequra_task_runner_on', 1 );
		wp_send_json( 'OK' );
	}

	/**
	 * Resume task runner
	 */
	public function resume_task_runner(): void {
		$this->logger->log_info( 'Hook executed', __FUNCTION__, __CLASS__ );
		$this->report_service->resume_task_runner();
	}
	
	/**
	 * Halt task runner
	 */
	public function halt_task_runner(): void {
		$this->logger->log_info( 'Hook executed', __FUNCTION__, __CLASS__ );
		$this->report_service->halt_task_runner();

		// TODO: remove this!
		delete_option( 'sequra_task_runner_on' );
	}
}
