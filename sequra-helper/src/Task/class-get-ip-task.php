<?php
/**
 * Task class
 * 
 * @package SeQura/Helper
 */

namespace SeQura\Helper\Task;

use SeQura\Core\Infrastructure\ServiceRegister;
use SeQura\WC\Services\Shopper\Interface_Shopper_Service;

// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.Security.EscapeOutput.ExceptionNotEscaped

/**
 * Task class
 */
class Get_IP_Task extends Task {

	/**
	 * IP address
	 *
	 * @var string
	 */
	private $ip_address = '';

	/**
	 * Execute the task
	 * 
	 * @param array<string, string> $args Arguments for the task.
	 * 
	 * @throws \Exception If the task fails.
	 */
	public function execute( array $args = array() ): void {    
		/** Service
		 *
		 * @var Interface_Shopper_Service $service
		 */
		$service          = ServiceRegister::getService( Interface_Shopper_Service::class );
		$this->ip_address = $service->get_ip();
	}

	/**
	 * Override to provide response payload
	 * 
	 * @return array<string, string> The response payload.
	 */
	protected function response_payload(): array {
		return array( 'ip_address' => $this->ip_address );
	}
}
