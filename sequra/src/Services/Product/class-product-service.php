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
use SeQura\WC\Services\Core\Configuration;
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
	 * Constructor
	 */
	public function __construct(
		Configuration $configuration
	) {
		$this->configuration = $configuration;
	}

	/**
	 * Get desired first charge date for a product
	 * 
	 * @param int|WC_Product $product_id The product ID or product object
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
	 * Get service date regex
	 */
	public function get_service_date_regex(): string {
		return '^((\d{4})-([0-1]\d)-([0-3]\d))+$|P(\d+Y)?(\d+M)?(\d+W)?(\d+D)?(T(\d+H)?(\d+M)?(\d+S)?)?$';
	}

	/**
	 * Get product service end date
	 *
	 * @param WC_Product|int $product the product we are building item info for.
	 */
	public function get_service_end_date( $product ): string {
		$product          = $this->get_product_instance( $product );
		$service_end_date = $product->get_meta( self::META_KEY_SEQURA_SERVICE_END_DATE, true );
		if ( ! preg_match( '/' . $this->get_service_date_regex() . '/', $service_end_date ) ) {
			$service_end_date = $this->configuration->get_default_services_end_date();
		}
		return $service_end_date;
	}

	/**
	 * Get registration amount
	 *
	 * @param WC_Product|int $product the product we are building item info for.
	 */
	public function get_registration_amount( $product ): float {
		$product = $this->get_product_instance( $product );
		return (float) $product->get_meta( self::META_KEY_SEQURA_REGISTRATION_AMOUNT, true );
	}
}
