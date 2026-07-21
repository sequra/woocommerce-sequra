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
 * The plugin does not call third-party endpoints directly and never echoes the affiliate
 * credentials: the concrete client hands the order data to integration-core, which sources the
 * offer/token from the stored settings, resolves the endpoint from the merchant deployment and
 * emits the postback (routed server-side by timon's ALB ingress to Simba, and on to TUNE). See
 * QRD-7898.
 */
interface Interface_Affiliate_Postback_Client {

	/**
	 * Send the affiliate conversion postback.
	 *
	 * Returns true when the core handled the call without error (dispatched, or intentionally
	 * skipped because affiliate marketing is disabled for the store) and false on a transient
	 * failure the caller should retry.
	 *
	 * @param string $country         Order billing country, used to resolve the merchant/deployment.
	 * @param string $transaction_id  The affiliate transaction ID.
	 * @param float  $amount          The conversion amount.
	 * @param string $order_reference Shop order reference (sent to the affiliate network as adv_sub).
	 */
	public function send_conversion( string $country, string $transaction_id, float $amount, string $order_reference ): bool;

	/**
	 * Send the affiliate cancellation/rejection.
	 *
	 * Returns true when the core handled the call without error and false on a transient failure
	 * the caller should retry.
	 *
	 * @param string $country        Order billing country, used to resolve the merchant/deployment.
	 * @param string $transaction_id The affiliate transaction ID.
	 */
	public function send_cancellation( string $country, string $transaction_id ): bool;
}
