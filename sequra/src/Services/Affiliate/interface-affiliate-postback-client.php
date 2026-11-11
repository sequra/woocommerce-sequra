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
 *
 * Each send reports one of three outcomes so the caller can tell a real failure (retry) apart from
 * a deliberate no-op (nothing to send):
 * - `RESULT_SENT`: the postback was dispatched and accepted.
 * - `RESULT_SKIPPED`: nothing was dispatched and it is not an error (affiliate disabled for the
 *   store, or no connected merchant to route the country to); do not retry or mark it sent.
 * - `RESULT_FAILED`: a transient failure the caller should retry.
 */
interface Interface_Affiliate_Postback_Client {

	const RESULT_SENT    = 'sent';
	const RESULT_SKIPPED = 'skipped';
	const RESULT_FAILED  = 'failed';

	/**
	 * Send the affiliate conversion postback.
	 *
	 * @param string $country         Order country, used to resolve the merchant/deployment.
	 * @param string $transaction_id  The affiliate transaction ID.
	 * @param float  $amount          The conversion amount.
	 * @param string $order_reference Shop order reference (sent to the affiliate network as adv_sub).
	 * @return string One of the `RESULT_*` constants.
	 */
	public function send_conversion( string $country, string $transaction_id, float $amount, string $order_reference ): string;

	/**
	 * Send the affiliate cancellation/rejection.
	 *
	 * @param string $country        Order country, used to resolve the merchant/deployment.
	 * @param string $transaction_id The affiliate transaction ID.
	 * @return string One of the `RESULT_*` constants.
	 */
	public function send_cancellation( string $country, string $transaction_id ): string;
}
