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
	 * Get payment gateways as an array. Each element is an array with the following structure:
	 * - product: string (e.g. 'pp3')
	 * - class: string (e.g. 'Sequra_Payment_Gateway_PP3')
	 * 
	 * @throws Throwable
	 * 
	 * @return array<string, string>[]
	 */
	public function get_payment_gateways(): array;

	/**
	 * Get payment methods
	 * 
	 * @throws Throwable
	 * 
	 * @return array<string, string>[]
	 */
	public function get_payment_methods(): array;

	/**
	 * Get the payment gateway class alias
	 * 
	 * @return string|null The payment gateway alias or null if not found
	 */
	public function get_payment_gateway_alias( string $class_name ): ?string;

	/**
	 * Define the payment gateway class
	 */
	public function register_payment_gateway_class( array $payment_method ): void;

	/**
	 * Find the payment gateway with empty class and link it to the given class
	 * 
	 * @return array<string, mixed>|null The payment gateway data or null if not found
	 */
	public function link_class_to_payment_gateway( string $class_name ): mixed;
}
