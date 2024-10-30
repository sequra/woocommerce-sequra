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
use SeQura\WC\Services\Order\Interface_Order_Service;
use SeQura\WC\Services\Payment\Sequra_Payment_Gateway;
use SeQura\WC\Services\Payment\Sequra_Payment_Gateway_Block_Support;
use WC_Order;

/**
 * Respond to payment hooks
 */
class Payment_Controller extends Controller implements Interface_Payment_Controller {

	/**
	 * Order service
	 *
	 * @var Interface_Order_Service
	 */
	private $order_service;

	/**
	 * Constructor
	 */
	public function __construct( 
		Interface_Logger_Service $logger, 
		string $templates_path,
		Interface_Order_Service $order_service 
	) {
		parent::__construct( $logger, $templates_path );
		$this->order_service = $order_service;
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
	 * Append text after the thank you message on the order received page
	 * 
	 * @param mixed $order The order object.
	 */
	public function order_received_text( string $text, $order ): string {
		$this->logger->log_debug( 'Hook executed', __FUNCTION__, __CLASS__ );
		$notices = wc_print_notices( true );
		return $notices . $text;
	}

	/**
	 * Set the proper payment method description in the order
	 * 
	 * @param mixed $order The order object.
	 */
	public function order_get_payment_method_title( string $value, $order ): string {
		$this->logger->log_debug( 'Hook executed', __FUNCTION__, __CLASS__ );
		$sequra_payment_method = $order instanceof WC_Order ? $this->order_service->get_payment_method_title( $order ) : null;
		return empty( $sequra_payment_method ) ? $value : $sequra_payment_method;
	}
}
