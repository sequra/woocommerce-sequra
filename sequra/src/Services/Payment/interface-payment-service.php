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
	 * Get payment gateway ID
	 * 
	 * @return string
	 */
	public function get_payment_gateway_id(): string;

	/**
	 * Get current merchant ID
	 */
	public function get_merchant_id(): ?string;

	/**
	 * Sign the string using HASH_ALGO and merchant's password
	 */
	public function sign( string $message ): string;

	/**
	 * Get IPN webhook identifier
	 */
	public function get_ipn_webhook(): string;

	/**
	 * Get return URL webhook identifier
	 */
	public function get_return_webhook(): string;

	/**
	 * Get event webhook identifier
	 */
	public function get_event_webhook(): string;
}
