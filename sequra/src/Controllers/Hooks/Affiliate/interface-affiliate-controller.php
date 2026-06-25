<?php
/**
 * Affiliate controller interface.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers/Hooks/Affiliate
 */

namespace SeQura\WC\Controllers\Hooks\Affiliate;

/**
 * Bind WordPress/WooCommerce hooks to the affiliate tracking service.
 */
interface Interface_Affiliate_Controller {

	/**
	 * Capture the affiliate click on the current request.
	 */
	public function handle_affiliate_click(): void;

	/**
	 * Attribute the affiliate transaction id to a newly created order.
	 *
	 * @param int            $order_id The order ID.
	 * @param \WC_Order|null $order    The order, when provided by the hook.
	 */
	public function handle_new_order( $order_id, $order = null ): void;

	/**
	 * React to an order status change.
	 *
	 * @param int            $order_id   The order ID.
	 * @param string         $old_status The old status.
	 * @param string         $new_status The new status.
	 * @param \WC_Order|null $order      The order, when provided by the hook.
	 */
	public function handle_order_status_changed( $order_id, $old_status, $new_status, $order = null ): void;

	/**
	 * Execute a scheduled affiliate postback (WP-cron callback).
	 *
	 * @param int    $order_id The order ID.
	 * @param string $kind     The postback kind (conversion or cancellation).
	 */
	public function dispatch( $order_id, $kind ): void;

	/**
	 * Clear the attribution cookie on the order-received page before output starts.
	 */
	public function clear_cookie_on_received(): void;
}
