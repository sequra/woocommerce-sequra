<?php
/**
 * Affiliate controller.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers/Hooks/Affiliate
 */

namespace SeQura\WC\Controllers\Hooks\Affiliate;

use SeQura\WC\Services\Affiliate\Interface_Affiliate_Service;
use SeQura\WC\Services\Log\Interface_Logger_Service;
use Throwable;
use WC_Order;

/**
 * Bind WordPress/WooCommerce hooks to the affiliate tracking service.
 */
class Affiliate_Controller implements Interface_Affiliate_Controller {

	/**
	 * Affiliate service.
	 *
	 * @var Interface_Affiliate_Service
	 */
	private $affiliate_service;

	/**
	 * Logger service.
	 *
	 * @var Interface_Logger_Service
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @param Interface_Affiliate_Service $affiliate_service Affiliate service.
	 * @param Interface_Logger_Service    $logger            Logger service.
	 */
	public function __construct( Interface_Affiliate_Service $affiliate_service, Interface_Logger_Service $logger ) {
		$this->affiliate_service = $affiliate_service;
		$this->logger            = $logger;
	}

	/**
	 * Capture the affiliate click on the current request.
	 */
	public function handle_affiliate_click(): void {
		try {
			$this->affiliate_service->capture_click();
		} catch ( Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
		}
	}

	/**
	 * Attribute the affiliate transaction id to a newly created order.
	 *
	 * @param int            $order_id The order ID.
	 * @param \WC_Order|null $order    The order, when provided by the hook.
	 */
	public function handle_new_order( $order_id, $order = null ): void {
		try {
			$order = $order instanceof WC_Order ? $order : \wc_get_order( $order_id );
			if ( $order instanceof WC_Order ) {
				$this->affiliate_service->attribute_order( $order );
			}
		} catch ( Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
		}
	}

	/**
	 * React to an order status change.
	 *
	 * @param int            $order_id   The order ID.
	 * @param string         $old_status The old status.
	 * @param string         $new_status The new status.
	 * @param \WC_Order|null $order      The order, when provided by the hook.
	 */
	public function handle_order_status_changed( $order_id, $old_status, $new_status, $order = null ): void {
		try {
			$order = $order instanceof WC_Order ? $order : \wc_get_order( $order_id );
			if ( $order instanceof WC_Order ) {
				$this->affiliate_service->handle_status_change( $order, (string) $new_status );
			}
		} catch ( Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
		}
	}

	/**
	 * Execute a scheduled affiliate postback (WP-cron callback).
	 *
	 * @param int    $order_id The order ID.
	 * @param string $kind     The postback kind (conversion or cancellation).
	 */
	public function dispatch( $order_id, $kind ): void {
		try {
			$order = \wc_get_order( $order_id );
			if ( $order instanceof WC_Order ) {
				$this->affiliate_service->dispatch( $order, (string) $kind );
			}
		} catch ( Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
		}
	}

	/**
	 * Clear the attribution cookie on the order-received page before output starts.
	 */
	public function clear_cookie_on_received(): void {
		try {
			if ( ! function_exists( 'is_order_received_page' ) || ! \is_order_received_page() ) {
				return;
			}
			$order_received = \get_query_var( 'order-received' );
			$this->affiliate_service->clear_cookie_for_order( \absint( is_scalar( $order_received ) ? $order_received : 0 ) );
		} catch ( Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
		}
	}
}
