<?php
/**
 * Payment Controller interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers
 */

namespace SeQura\WC\Controllers\Hooks\Payment;

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

/**
 * Respond to payment hooks
 */
interface Interface_Payment_Controller {

	/**
	 * Register the gateway classes so that they can be used by WooCommerce
	 *
	 * @param string[] $gateways
	 * @return string[]
	 */
	public function register_gateway_classes( $gateways ): array;

	/**
	 * Register the payment gateway block so that it can be used by Gutenberg
	 */
	public function register_gateway_gutenberg_block(): void;

	/**
	 * Register the payment gateway block class
	 */
	public function register_gateway_gutenberg_block_class( PaymentMethodRegistry $payment_method_registry ): void;
}
