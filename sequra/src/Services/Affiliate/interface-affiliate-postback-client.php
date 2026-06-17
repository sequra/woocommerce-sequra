<?php
/**
 * Affiliate postback client interface.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services/Affiliate
 */

namespace SeQura\WC\Services\Affiliate;

/**
 * Sends the affiliate conversion/cancellation postbacks.
 *
 * The plugin does not call third-party endpoints directly: the concrete client routes
 * the calls server-side (integration-core resolves the endpoint from the deployment and
 * timon proxies them). See QRD-7898.
 */
interface Interface_Affiliate_Postback_Client {

	/**
	 * Send the affiliate conversion postback. Returns true on success.
	 *
	 * @param string $offer_id       The offer ID.
	 * @param string $security_token The security token.
	 * @param string $transaction_id The affiliate transaction ID.
	 * @param float  $amount         The conversion amount.
	 * @param int    $order_id       The order ID.
	 */
	public function send_conversion( string $offer_id, string $security_token, string $transaction_id, float $amount, int $order_id ): bool;

	/**
	 * Send the affiliate cancellation/rejection. Returns true on success.
	 *
	 * @param string $offer_id       The offer ID.
	 * @param string $security_token The security token.
	 * @param string $transaction_id The affiliate transaction ID.
	 */
	public function send_cancellation( string $offer_id, string $security_token, string $transaction_id ): bool;
}
