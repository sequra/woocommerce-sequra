<?php
/**
 * Pricing service
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Pricing;

/**
 * Handle use cases related to pricing
 */
class Pricing_Service implements Interface_Pricing_Service {
	private const CENTS_PER_WHOLE = 100;

	/**
	 * Get price in cents
	 */
	public function to_cents( float $price ): int {
		return is_numeric( $price ) ?
		intval( round( self::CENTS_PER_WHOLE * (float) $price ) ) :
		0;
	}
}
