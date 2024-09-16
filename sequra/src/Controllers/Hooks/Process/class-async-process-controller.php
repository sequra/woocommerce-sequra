<?php
/**
 * Async Process Controller
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers
 */

namespace SeQura\WC\Controllers\Hooks\Process;

use SeQura\WC\Controllers\Controller;
use SeQura\WC\Services\Interface_Logger_Service;
use SeQura\WC\Services\Report\Interface_Report_Service;

/**
 * Async Process Controller implementation
 */
class Async_Process_Controller extends Controller implements Interface_Async_Process_Controller {

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
		Interface_Report_Service $report_service
	) {
		$this->logger         = $logger;
		$this->templates_path = $templates_path;
		$this->report_service = $report_service;
	}

	/**
	 * Send the delivery report
	 */
	public function send_delivery_report(): void {
		$this->logger->log_info( 'Hook executed', __FUNCTION__, __CLASS__ );
		try {
			$this->report_service->send_delivery_report_for_current_store();
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
		}
	}
}
