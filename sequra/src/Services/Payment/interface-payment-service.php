<?php
/**
 * Payment service interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Payment;

use SeQura\WC\Dto\Payment_Method_Data;
use WC_Order;

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

	/**
	 * Check if the payment method data matches a valid payment method.
	 */
	public function is_payment_method_data_valid( Payment_Method_Data $data ): bool;

	/**
	 * Get payment gateway ID
	 * 
	 * @return string
	 */
	public function get_payment_gateway_id(): string;

	/**
	 * Get the seQura payment method title for the order.
	 * If the order is not a seQura order an empty string is returned.
	 */
	public function get_payment_method_title( WC_Order $order ): string;

	/**
	 * Get the seQura product for the order.
	 * If the value is not found an empty string is returned.
	 */
	public function get_product( WC_Order $order ): string;

	/**
	 * Get the seQura campaign for the order.
	 * If the value is not found an empty string is returned.
	 */
	public function get_campaign( WC_Order $order ): string;

	/**
	 * Save required metadata for the order.
	 * Returns true if the metadata was saved, false otherwise.
	 */
	public function set_order_metadata( WC_Order $order, ?Payment_Method_Data $data ): bool;
}
