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
	 * Get a list of all payment methods defined for store and merchant
	 * 
	 * @param bool $cache Use cache if available.
	 * @return array<string, string>[] See PaymentMethodsResponse::toArray() output
	 */
	public function get_all_payment_methods( ?string $store_id, ?string $merchant, $cache = false ): array;

	/**
	 * Check if the payment method data matches a valid payment method.
	 */
	public function is_payment_method_data_valid( ?Payment_Method_Data $data ): bool;

	/**
	 * Look for available payment methods which can be used with the widget
	 * 
	 * @return array<string, string>[]
	 */
	public function get_all_widget_compatible_payment_methods( string $store_id, ?string $merchant ): array;

	/**
	 * Look for available payment methods that can be used with part payments
	 * 
	 * @throws Throwable
	 * 
	 * @return array<string, string>[]
	 */
	public function get_all_mini_widget_compatible_payment_methods( string $store_id, ?string $merchant ): array;

	/**
	 * Check if the current page is the order pay page
	 */
	public function is_order_pay_page(): bool;

	/**
	 * Check if the current page is the checkout page
	 */
	public function is_checkout(): bool;
}
