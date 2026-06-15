<?php
/**
 * Affiliate tracking service interface.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services/Affiliate
 */

namespace SeQura\WC\Services\Affiliate;

use WC_Order;

/**
 * Affiliate click attribution and conversion/cancellation postbacks.
 */
interface Interface_Affiliate_Service {

	/**
	 * Capture the affiliate transaction id from the current request and store it in a cookie.
	 */
	public function capture_click(): void;

	/**
	 * Attribute the captured transaction id to an order (idempotent).
	 *
	 * @param WC_Order $order The order.
	 */
	public function attribute_order( WC_Order $order ): void;

	/**
	 * Send the conversion postback to TUNE when the order is paid (deduplicated).
	 *
	 * @param WC_Order $order The order.
	 */
	public function maybe_send_conversion( WC_Order $order ): void;

	/**
	 * React to an order status change: send conversion when paid, rejection when cancelled/refunded.
	 *
	 * @param WC_Order $order      The order.
	 * @param string   $new_status The new status (without the wc- prefix).
	 */
	public function handle_status_change( WC_Order $order, $new_status ): void;

	/**
	 * Remove the attribution cookie (e.g. on the order-received page).
	 *
	 * @param int $order_id The order ID.
	 */
	public function clear_cookie_for_order( $order_id ): void;
}
