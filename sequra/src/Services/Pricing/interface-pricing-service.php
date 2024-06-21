<?php
/**
 * Pricing service interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Pricing;

/**
 * Handle use cases related to pricing
 */
interface Interface_Pricing_Service {

	/**
	 * Get price in cents
	 */
	public function to_cents( float $price ): int;
}
