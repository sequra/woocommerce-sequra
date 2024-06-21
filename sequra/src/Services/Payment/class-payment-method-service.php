<?php
/**
 * Payment method service
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Payment;

use Exception;
use SeQura\Core\BusinessLogic\CheckoutAPI\CheckoutAPI;
use SeQura\WC\Dto\Payment_Method_Data;
use SeQura\WC\Services\Core\Configuration;
use Throwable;

/**
 * Handle use cases related to payment methods
 */
class Payment_Method_Service implements Interface_Payment_Method_Service {

	/**
	 * Configuration
	 *
	 * @var Configuration
	 */
	private $configuration;

	/**
	 * Payment methods
	 *
	 * @var array<string, string>[]
	 */
	private $payment_methods;

	/**
	 * Constructor
	 */
	public function __construct( Configuration $configuration ) {
		$this->configuration = $configuration;
	}

	/**
	 * Get payment methods
	 * 
	 * @throws Throwable|Exception
	 * 
	 * @return array<string, string>[]
	 */
	public function get_payment_methods(): array {

		if ( null !== $this->payment_methods ) {
			return $this->payment_methods;
		}

		// TODO: add try catch block
	
		$settings = AdminAPI::get()->generalSettings( $this->configuration->get_store_id() )->getGeneralSettings();
		if ( ! $settings->isSuccessful() || ! $builder->is_allowed_for( $settings ) ) {
			return array();
		}
	
		$response = CheckoutAPI::get()->solicitation( $this->configuration->get_store_id() )->solicitFor( $this->create_order_request_builder );
	
		if ( ! $response->isSuccessful() ) {
			return array();
		}
	
		$this->payment_methods = $response->toArray()['availablePaymentMethods'];

		return $this->payment_methods;
	}
		// TODO: finish this
		// $quote = $this->cartProvider->getQuote($cartId);

		// if (empty($quote->getShippingAddress()->getCountryId())) {
		// return [];
		// }

		// /** @var CreateOrderRequestBuilder $builder */
		// $builder = $this->createOrderRequestBuilderFactory->create([
		// 'cartId' => $quote->getId(),
		// 'storeId' => (string)$quote->getStore()->getId(),
		// ]);

		// $generalSettings = AdminAPI::get()->generalSettings((string)$quote->getStore()->getId())->getGeneralSettings();
		// if (!$generalSettings->isSuccessful() || !$builder->isAllowedFor($generalSettings)) {
		// return [];
		// }

		// $response = CheckoutAPI::get()
		// ->solicitation((string)$quote->getStore()->getId())
		// ->solicitFor($builder);

		// if (!$response->isSuccessful()) {
		// return [];
		// }

		// return $response->toArray()['availablePaymentMethods'];

		$c  = WC()->cart;
		$s  = WC()->session;
		$cu = WC()->customer;
		
		$store_id = $this->configuration->get_store_id();
		$merchant = $this->get_merchant_id();
	if ( empty( $merchant ) ) {
		return array();
	}

		return AdminAPI::get()->paymentMethods( $store_id )->getPaymentMethods( $merchant )->toArray();
}

	/**
	 * Check if the payment method data matches a valid payment method.
	 */
public function is_payment_method_data_valid( Payment_Method_Data $data ): bool {
	foreach ( $this->get_payment_methods() as $pm ) {
		if ( $pm['product'] === $data->product && $pm['campaign'] === $data->campaign ) {
			return true;
		}
	}
	return false;
}
	
	/**
	 * Get checkout form
	 */
public function get_checkout_form(): string {
	return ''; // TODO: Implement get_checkout_form() method.
}
}
