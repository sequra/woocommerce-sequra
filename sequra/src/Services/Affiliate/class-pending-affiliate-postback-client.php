<?php
/**
 * Affiliate postback client (pending server-side routing).
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services/Affiliate
 */

namespace SeQura\WC\Services\Affiliate;

use SeQura\WC\Services\Log\Interface_Logger_Service;

/**
 * Placeholder outbound client used until the conversion/cancellation routing lands in
 * integration-core (affiliate proxy, endpoint resolved from the deployment) and timon
 * (NGINX proxy). It does not send anything from the plugin; it logs and reports failure.
 *
 * TODO (QRD-7898): replace with the integration-core-backed client once that core version
 * is released and vendored.
 */
class Pending_Affiliate_Postback_Client implements Interface_Affiliate_Postback_Client {

	/**
	 * Logger service.
	 *
	 * @var Interface_Logger_Service
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @param Interface_Logger_Service $logger Logger service.
	 */
	public function __construct( Interface_Logger_Service $logger ) {
		$this->logger = $logger;
	}

	/**
	 * @inheritDoc
	 */
	public function send_conversion( string $offer_id, string $security_token, string $transaction_id, float $amount, int $order_id ): bool {
		$this->logger->log_info( 'Affiliate conversion postback skipped: server-side routing not available yet', __FUNCTION__, __CLASS__ );
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function send_cancellation( string $offer_id, string $security_token, string $transaction_id ): bool {
		$this->logger->log_info( 'Affiliate cancellation postback skipped: server-side routing not available yet', __FUNCTION__, __CLASS__ );
		return false;
	}
}
