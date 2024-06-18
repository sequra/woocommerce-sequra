<?php
/**
 * Payment service interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Payment;

use Exception;
use SeQura\Core\BusinessLogic\AdminAPI\AdminAPI;
use SeQura\WC\Dto\Payment_Method_Data;
use SeQura\WC\Services\Core\Configuration;
use SeQura\WC\Services\I18n\Interface_I18n;
use Throwable;
use WC_Order;

/**
 * Handle use cases related to payments
 */
class Payment_Service implements Interface_Payment_Service {

	private const META_KEY_METHOD_TITLE = '_sq_method_title';
	private const META_KEY_PRODUCT      = '_sq_product';
	private const META_KEY_CAMPAIGN     = '_sq_campaign';

	/**
	 * Configuration
	 *
	 * @var Configuration
	 */
	private $configuration;

	/**
	 * I18n service
	 *
	 * @var Interface_I18n
	 */
	private $i18n;

	/**
	 * Constructor
	 */
	public function __construct( 
		Configuration $configuration,
		Interface_I18n $i18n
	) {
		$this->configuration = $configuration;
		$this->i18n          = $i18n;
	}

	/**
	 * Get payment gateway ID
	 * 
	 * @return string
	 */
	public function get_payment_gateway_id(): string {
		return 'sequra';
	}

	/**
	 * Get payment methods
	 * 
	 * @throws Throwable|Exception
	 * 
	 * @return array<string, string>[]
	 */
	public function get_payment_methods(): array {
		
		$store_id = $this->configuration->get_store_id();
		
		$countries       = AdminAPI::get()->countryConfiguration( $store_id )->getCountryConfigurations()->toArray();
		$merchant        = null;
		$current_country = $this->i18n->get_current_country();

		foreach ( $countries as $country ) {
			if ( $country['countryCode'] === $current_country ) {
				$merchant = $country['merchantId'];
				break;
			}
		}
		if ( empty( $merchant ) ) {
			throw new Exception( 'Merchant not found' );
		}

		return AdminAPI::get()->paymentMethods( $store_id )->getPaymentMethods( $merchant )->toArray();
	}

	/**
	 * Get order meta value by key from a seQura order.
	 * If the order is not a seQura order an empty string is returned.
	 */
	private function get_order_meta( WC_Order $order, $meta_key ): string {
		if ( $order->get_payment_method() !== $this->get_payment_gateway_id() ) {
			return '';
		}
		return strval( $order->get_meta( $meta_key, true ) );
	}

	/**
	 * Get the seQura payment method title for the order.
	 * If the order is not a seQura order an empty string is returned.
	 */
	public function get_payment_method_title( WC_Order $order ): string {
		return $this->get_order_meta( $order, self::META_KEY_METHOD_TITLE );
	}

	/**
	 * Get the seQura product for the order.
	 * If the value is not found an empty string is returned.
	 */
	public function get_product( WC_Order $order ): string {
		return $this->get_order_meta( $order, self::META_KEY_PRODUCT );
	}

	/**
	 * Get the seQura campaign for the order.
	 * If the value is not found an empty string is returned.
	 */
	public function get_campaign( WC_Order $order ): string {
		return $this->get_order_meta( $order, self::META_KEY_CAMPAIGN );
	}

	/**
	 * Save required metadata for the order.
	 * Returns true if the metadata was saved, false otherwise.
	 */
	public function set_order_metadata( WC_Order $order, ?Payment_Method_Data $dto ): bool {
		if ( ! $dto || ! $this->is_payment_method_data_valid( $dto ) ) {
			return false;
		}

		$order->update_meta_data( self::META_KEY_PRODUCT, $dto->product );
		if ( ! empty( $dto->campaign ) ) {
			$order->update_meta_data( self::META_KEY_CAMPAIGN, $dto->campaign );
		}
		$order->update_meta_data( self::META_KEY_METHOD_TITLE, $dto->title );
		$order->save();
		return true;
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
}
