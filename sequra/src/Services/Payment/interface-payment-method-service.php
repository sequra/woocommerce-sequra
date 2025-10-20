<?php
/**
 * Payment method service interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Payment;

use SeQura\Core\BusinessLogic\Domain\Order\Models\SeQuraForm;
use SeQura\WC\Dto\Payment_Method_Data;
use SeQura\WC\Dto\Payment_Method_Option;
use WC_Order;

/**
 * Handle use cases related to payment methods
 */
interface Interface_Payment_Method_Service {

	/**
	 * Get identification form
	 */
	public function get_identification_form( WC_Order $order ): ?SeQuraForm;

	/**
	 * Get payment methods
	 * 
	 * @return Payment_Method_Option[]
	 */
	public function get_payment_methods( ?WC_Order $order = null );

	/**
	 * Check if the payment method data matches a valid payment method.
	 */
	public function is_payment_method_data_valid( ?Payment_Method_Data $data ): bool;

	/**
	 * Check if the current page is the order pay page
	 */
	public function is_order_pay_page(): bool;

	/**
	 * Check if the current page is the checkout page
	 */
	public function is_checkout(): bool;
}
