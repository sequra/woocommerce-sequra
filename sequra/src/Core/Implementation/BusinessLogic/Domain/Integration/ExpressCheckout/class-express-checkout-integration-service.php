<?php
/**
 * Express Checkout Integration Service implementation
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Implementation\BusinessLogic\Domain\Integration\ExpressCheckout;

use SeQura\Core\BusinessLogic\Domain\Integration\ExpressCheckout\ExpressCheckoutIntegrationInterface;

/**
 * Express Checkout Integration Service implementation.
 *
 * Express checkout is not offered on this platform, so no page can host it.
 */
class Express_Checkout_Integration_Service implements ExpressCheckoutIntegrationInterface {

	/**
	 * Pages where express checkout can be placed.
	 *
	 * @return \SeQura\Core\BusinessLogic\Domain\ExpressCheckout\Models\ExpressCheckoutPage[]
	 */
	public function getAvailablePages(): array {
		return array();
	}
}
