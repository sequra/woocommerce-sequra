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
use WC_Order;

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
	 */
	public function __construct( Interface_Logger_Service $logger, string $templates_path, Interface_Payment_Service $payment_service ) {
		parent::__construct( $logger, $templates_path );
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
	 * Append content to the bottom of the receipt page
	 */
	public function receipt_page( int $order_id ): void {
		$this->logger->log_debug( 'Hook executed', __FUNCTION__, __CLASS__ );

		$order = wc_get_order( $order_id );

		if ( ! $order instanceof WC_Order ) {
			$this->logger->log_error( 'Order not found', __FUNCTION__, __CLASS__ );
			return;
		}

		/**
		 * TODO: Check the current usage of this filter in the existent integrations because the current implementation doesn't save settings inside the payment gateway.
		 * Filter the options to be sent to seQura if needed
		 * 
		 * @since 2.0.0
		 * */
		$args = apply_filters(
			'wc_sequra_pumbaa_options', 
			array(
				'product'  => $this->payment_service->get_product( $order ),
				'campaign' => $this->payment_service->get_campaign( $order ),
			), 
			$order,
			array() 
		);

		// TODO: finish this.
		wc_get_template( 'front/receipt_page.php', $args, '', $this->templates_path );
	}

	/**
	 * Append text after the thank you message on the order received page
	 */
	public function order_received_text( string $text, mixed $order ): string {
		$this->logger->log_debug( 'Hook executed', __FUNCTION__, __CLASS__ );
		$notices = wc_print_notices( true );
		return $notices . $text;
	}

	/**
	 * Set the proper payment method description in the order
	 */
	public function order_get_payment_method_title( string $value, mixed $order ): string {
		$this->logger->log_debug( 'Hook executed', __FUNCTION__, __CLASS__ );
		return ! $order instanceof WC_Order ? $value : $this->payment_service->get_payment_method_title( $order ); 
	}
}
