<?php
/**
 * Payment service interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Payment;

/**
 * Handle use cases related to payments
 */
interface Interface_Payment_Service {

	/**
	 * Get payment methods
	 * 
	 * @throws Throwable
	 * 
	 * @return array<string, string>[]
	 */
	public function get_payment_methods(): array;
}
