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
use SeQura\WC\Services\Core\Configuration;
use SeQura\WC\Services\I18n\Interface_I18n;
use Throwable;

/**
 * Handle use cases related to payments
 */
class Payment_Service implements Interface_Payment_Service {

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
}
