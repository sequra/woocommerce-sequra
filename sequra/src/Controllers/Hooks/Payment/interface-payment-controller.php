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

	/**
	 * Append text after the thank you message on the order received page
	 */
	public function order_received_text( string $text, mixed $order ): string;

	/**
	 * Set the proper payment method description in the order
	 */
	public function order_get_payment_method_title( string $value, mixed $order ): string;

	/**
	 * Add support to custom meta query vars for the order query
	 *
	 * @param array $wp_query_args Args for WP_Query.
	 * @param array $query_vars Query vars from WC_Order_Query.
	 * @param WC_Order_Data_Store_CPT $order_data_store WC_Order_Data_Store instance.
	 * @return array modified $query
	 */
	public function handle_custom_query_vars( array $wp_query_args, array $query_vars, $order_data_store ): array;
}
