<?php
/**
 * Payment Controller
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers
 */

namespace SeQura\WC\Controllers\Hooks\Payment;

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use SeQura\Core\Infrastructure\ServiceRegister;
use SeQura\WC\Controllers\Controller;
use SeQura\WC\Services\Interface_Logger_Service;
use SeQura\WC\Services\Payment\Interface_Payment_Service;
use SeQura\WC\Services\Payment\Sequra_Payment_Gateway;
use SeQura\WC\Services\Payment\Sequra_Payment_Gateway_Block_Support;

/**
 * Respond to payment hooks
 */
class Payment_Controller extends Controller implements Interface_Payment_Controller {

	/**
	 * Payment service
	 *
	 * @var Interface_Payment_Service
	 */
	private $payment_service;

	/**
	 * Constructor
	 *
	 * @param Interface_Logger_Service $logger Logger service.
	 */
	public function __construct( Interface_Logger_Service $logger, Interface_Payment_Service $payment_service ) {
		parent::__construct( $logger );
		$this->payment_service = $payment_service;
	}

	/**
	 * Register the gateway classes so that they can be used by WooCommerce
	 *
	 * @param string[] $gateways
	 * @return string[]
	 */
	public function register_gateway_classes( $gateways ): array {
		$this->logger->log_debug( 'Hook executed', __FUNCTION__, __CLASS__ );
		if ( ! in_array( Sequra_Payment_Gateway::class, $gateways, true ) ) {
			$gateways[] = Sequra_Payment_Gateway::class;
		}
		return $gateways;
	}

	/**
	 * Register the payment gateway block so that it can be used by Gutenberg
	 */
	public function register_gateway_gutenberg_block(): void {
		$this->logger->log_debug( 'Hook executed', __FUNCTION__, __CLASS__ );
		add_action( 'woocommerce_blocks_payment_method_type_registration', array( $this, 'register_gateway_gutenberg_block_class' ) );
	}

	/**
	 * Register the payment gateway block class
	 */
	public function register_gateway_gutenberg_block_class( PaymentMethodRegistry $payment_method_registry ): void {
		$this->logger->log_debug( 'Hook executed', __FUNCTION__, __CLASS__ );
		$payment_method_registry->register( ServiceRegister::getService( Sequra_Payment_Gateway_Block_Support::class ) );
	}

	/**
	 * Checkout fields validation
	 * Legacy shortcode only
	 */
	public function checkout_process(): void {
		// TODO: review this
		if ( empty( $_POST['sequra_product_campaign'] ) ) {
			wc_add_notice( __( 'Invalid <strong>sequra_product_campaign</strong>, please check ...' ), 'error' );
		}
	}

	/**
	 * Checkout fields
	 * Legacy shortcode only
	 */
	public function checkout_fields( $fields ): array {
		// TODO: review this
		return $fields;
	}
}
