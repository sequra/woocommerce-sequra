<?php
/**
 * Payment service interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Payment;

use SeQura\Core\BusinessLogic\AdminAPI\AdminAPI;
use SeQura\WC\Core\Extension\Infrastructure\Configuration\Configuration;
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
	 * Get payment gateway ID
	 */
	public function get_payment_gateway_id(): string {
		return 'sequra';
	}

	/**
	 * Get payment gateway webhook identifier
	 */
	public function get_event_webhook(): string {
		return 'woocommerce_' . $this->get_payment_gateway_id();
	}

	/**
	 * Get IPN webhook identifier
	 */
	public function get_ipn_webhook(): string {
		return 'woocommerce_' . $this->get_payment_gateway_id() . '_ipn';
	}

	/**
	 * Get return URL webhook identifier
	 */
	public function get_return_webhook(): string {
		return 'woocommerce_' . $this->get_payment_gateway_id() . '_return';
	}
	
	/**
	 * Get current merchant ID
	 */
	public function get_merchant_id(): ?string {
		try {
			$store_id = $this->configuration->get_store_id();
		
			$countries = AdminAPI::get()
			->countryConfiguration( $store_id )
			->getCountryConfigurations()
			->toArray();

			$merchant        = null;
			$current_country = $this->i18n->get_current_country();

			foreach ( $countries as $country ) {
				if ( $country['countryCode'] === $current_country ) {
					$merchant = $country['merchantId'];
					break;
				}
			}
			return empty( $merchant ) ? null : $merchant;
		} catch ( Throwable $e ) {
			return null;
		}
	}

	/**
	 * Sign the string using HASH_ALGO and merchant's password
	 */
	public function sign( string $message ): string {
		return hash_hmac( 'sha256', $message, $this->configuration->get_password() );
	}
}
