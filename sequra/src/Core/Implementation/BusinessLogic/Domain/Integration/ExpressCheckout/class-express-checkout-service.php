<?php
/**
 * Implementation of the Express Checkout integration service.
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Implementation\BusinessLogic\Domain\Integration\ExpressCheckout;

use SeQura\Core\BusinessLogic\Domain\Integration\ExpressCheckout\ExpressCheckoutIntegrationInterface;

/**
 * Express Checkout integration service.
 *
 * Bound because integration-core (>= 5.6) uses this contract to list the storefront pages where the
 * integration can host Express Checkout buttons. WooCommerce does not expose seQura Express
 * Checkout through this contract yet, so this is an interim no-op advertising no pages. Replace
 * with a real implementation when Express Checkout is wired into the storefront.
 */
class Express_Checkout_Service implements ExpressCheckoutIntegrationInterface {

	/**
	 * Returns the pages supported by the platform integration.
	 *
	 * @return \SeQura\Core\BusinessLogic\Domain\ExpressCheckout\Models\ExpressCheckoutPage[]
	 */
	public function getAvailablePages(): array {
		return array();
	}
}
