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
	public function register_gateway_classes( $gateways );

	/**
	 * Register the payment gateway block so that it can be used by Gutenberg
	 * 
	 * @return void
	 */
	public function register_gateway_gutenberg_block();

	/**
	 * Register the payment gateway block class
	 * 
	 * @param PaymentMethodRegistry $payment_method_registry The payment method registry.
	 * @return void
	 */
	public function register_gateway_gutenberg_block_class( $payment_method_registry );

	/**
	 * Append text after the thank you message on the order received page
	 * 
	 * @param string $text The text to append.
	 * @param mixed $order The order object.
	 * @return string
	 */
	public function order_received_text( $text, $order );

	/**
	 * Set the proper payment method description in the order
	 * 
	 * @param string $value The payment method title.
	 * @param mixed $order The order object.
	 * @return string
	 */
	public function order_get_payment_method_title( $value, $order );
}
