<?php
/**
 * Product service
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Product;

use DateInterval;
use DateTime;
use SeQura\WC\Core\Extension\Infrastructure\Configuration\Configuration;
use SeQura\WC\Services\Payment\Sequra_Payment_Gateway;
use SeQura\WC\Services\Pricing\Interface_Pricing_Service;
use SeQura\WC\Services\Regex\Interface_Regex;
use WC_Product;

/**
 * Handle use cases related to products
 */
class Product_Service implements Interface_Product_Service {

	private const META_KEY_DESIRED_FIRST_CHARGE_DATE  = 'sequra_desired_first_charge_date';
	private const META_KEY_IS_SEQURA_SERVICE          = 'is_sequra_service';
	private const META_KEY_IS_SEQURA_BANNED           = 'is_sequra_banned';
	private const META_KEY_SEQURA_SERVICE_END_DATE    = 'sequra_service_end_date';
	private const META_KEY_SEQURA_REGISTRATION_AMOUNT = 'sequra_registration_amount';

	/**
	 * Configuration
	 *
	 * @var Configuration
	 */
	private $configuration;

	/**
	 * Pricing service
	 *
	 * @var Interface_Pricing_Service
	 */
	private $pricing_service;

	/**
	 * RegEx service
	 *
	 * @var Interface_Regex
	 */
	private $regex;

	/**
	 * Constructor
	 */
	public function __construct(
		Configuration $configuration,
		Interface_Pricing_Service $pricing_service,
		Interface_Regex $regex
	) {
		$this->configuration   = $configuration;
		$this->pricing_service = $pricing_service;
		$this->regex           = $regex;
	}

	/**
	 * Get desired first charge date for a product
	 * 
	 * @param int|WC_Product $product The product ID or product object
	 */
	public function get_desired_first_charge_date( $product ): ?DateTime {
		$_product = $this->get_product_instance( $product );
		if ( ! $_product ) {
			return null;
		}
		$raw_date = $_product->get_meta( self::META_KEY_DESIRED_FIRST_CHARGE_DATE, true );
		if ( ! $raw_date ) {
			return null;
		}
		if ( 'P' === substr( $raw_date, 0, 1 ) ) {
			return ( new DateTime() )->add( new DateInterval( $raw_date ) );
		} 
		return new DateTime( $raw_date );
	}

	/**
	 * Get product instance
	 * 
	 * @param int|WC_Product $product The product ID or product object
	 */
	public function get_product_instance( $product ): ?WC_Product {
		if ( $product instanceof WC_Product ) {
			return $product;
		}
		$_product = wc_get_product( $product );
		return ! $_product ? null : $_product;
	}

	/**
	 * Check if product is a seQura service
	 * 
	 * @param int|WC_Product $product The product ID or product object
	 */
	public function is_service( $product ): bool {
		$_product = $this->get_product_instance( $product );
		if ( ! $_product ) {
			return false;
		}
		return 'no' !== $_product->get_meta( self::META_KEY_IS_SEQURA_SERVICE, true );
	}

	/**
	 * Check if product is banned
	 * 
	 * @param int|WC_Product $product The product ID or product object
	 */
	public function is_banned( $product ): bool {
		$_product = $this->get_product_instance( $product );
		if ( ! $_product ) {
			return false;
		}
		return 'yes' === $_product->get_meta( self::META_KEY_IS_SEQURA_BANNED, true );
	}

	/**
	 * Get product service end date
	 *
	 * @param WC_Product|int $product the product we are building item info for.
	 */
	public function get_service_end_date( $product ): string {
		$product          = $this->get_product_instance( $product );
		$service_end_date = $product->get_meta( self::META_KEY_SEQURA_SERVICE_END_DATE, true );
		if ( ! preg_match( $this->regex->date_or_duration(), $service_end_date ) ) {
			$service_end_date = $this->configuration->get_default_services_end_date();
		}
		return $service_end_date;
	}

	/**
	 * Get registration amount
	 *
	 * @param WC_Product|int $product the product we are building item info for.
	 */
	public function get_registration_amount( $product, bool $to_cents = false ): float {
		$product = $this->get_product_instance( $product );
		$value   = (float) $product->get_meta( self::META_KEY_SEQURA_REGISTRATION_AMOUNT, true );
		return $to_cents ? $this->pricing_service->to_cents( $value ) : $value;
	}

	/**
	 * Check if we can display widgets for a product
	 *
	 * @param WC_Product|int $product the product.
	 */
	public function can_display_widgets( $product ): bool {
		if ( ! function_exists( 'WC' ) ) {
			return false;
		}
		
		/**
		 * Payment gateway
		 * 
		 * @var Sequra_Payment_Gateway
		 */
		$gateway = null;
		foreach ( WC()->payment_gateways()->get_available_payment_gateways() as $g ) {
			if ( $g instanceof Sequra_Payment_Gateway ) {
				$gateway = $g;
				break;
			}
		}

		if ( ! $gateway ) {
			return false;
		}

		$product = $this->get_product_instance( $product );
		if ( ! $product ) {
			return false;
		}

		if ( ! $this->configuration->is_enabled_for_services() && ! $product->needs_shipping() ) {
			return false;
		}

		if ( $this->is_banned( $product ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if we can display widget of a payment method for a product
	 * 
	 * @param WC_Product|int $product the product.
	 * @param array<string, string> $method the payment method. See PaymentMethodsResponse::toArray() output
	 */
	public function can_display_widget_for_method( $product, $method ): bool {
		$product = $this->get_product_instance( $product );
		if ( ! $product ) {
			return false;
		}
		// Check if price is too high.
		if ( ! empty( $method['maxAmount'] ) && $method['maxAmount'] < $this->pricing_service->to_cents( (float) $product->get_price( 'edit' ) ) ) {
			return false;
		}

		// Check if is too early to display the widget.
		if ( ! empty( $method['startsAt'] ) && time() < strtotime( $method['startsAt'] ) ) {
			return false;
		}

		// Check if is too late to display the widget.
		if ( ! empty( $method['endsAt'] ) && strtotime( $method['endsAt'] ) < time() ) {
			return false;
		}

		return true;            
	}
}
