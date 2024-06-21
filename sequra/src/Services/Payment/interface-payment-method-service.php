<?php
/**
 * Payment method service interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Payment;

use SeQura\WC\Dto\Payment_Method_Data;
use Throwable;

/**
 * Handle use cases related to payment methods
 */
interface Interface_Payment_Method_Service {

	/**
	 * Get payment methods
	 * 
	 * @throws Throwable
	 * 
	 * @return array<string, string>[]
	 */
	public function get_payment_methods(): array;

	/**
	 * Check if the payment method data matches a valid payment method.
	 */
	public function is_payment_method_data_valid( Payment_Method_Data $data ): bool;
	
	/**
	 * Get checkout form
	 */
	public function get_checkout_form(): string;
}
