<?php
/**
 * Task class
 * 
 * @package SeQura/Helper
 */

namespace SeQura\Helper\Task;

use SeQura\Core\Infrastructure\ServiceRegister;
use SeQura\WC\Services\Interface_Logger_Service;

// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.Security.EscapeOutput.ExceptionNotEscaped

/**
 * Task class
 */
class Print_Logs_Task extends Task {

	/**
	 * Execute the task
	 * 
	 * @throws \Exception If the task fails
	 */
	public function execute( array $args = array() ): void {

		// Dump logs first.
		( new Remove_Log_Task() )->execute();

		// Then fill with dummy logs.
	
		/** Logger
		 *
		 * @var Interface_Logger_Service $logger
		 */
		$logger = ServiceRegister::getService( Interface_Logger_Service::class );
		$logger->log_debug( 'Log with severity level of DEBUG' );
		$logger->log_info( 'Log with severity level of INFO' );
		$logger->log_warning( 'Log with severity level of WARNING' );
		$logger->log_error( 'Log with severity level of ERROR' );
	}
}
