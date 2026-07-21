<?php
/**
 * Affiliate postback client backed by integration-core.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services/Affiliate
 */

namespace SeQura\WC\Services\Affiliate;

use SeQura\Core\BusinessLogic\CheckoutAPI\Affiliate\AffiliateController;
use SeQura\Core\BusinessLogic\CheckoutAPI\Affiliate\Requests\SendCancellationRequest;
use SeQura\Core\BusinessLogic\CheckoutAPI\Affiliate\Requests\SendConversionRequest;
use SeQura\Core\BusinessLogic\CheckoutAPI\Affiliate\Responses\AffiliatePostbackResponse;
use SeQura\Core\BusinessLogic\Domain\Connection\Services\CredentialsService;
use SeQura\Core\BusinessLogic\Domain\Integration\Store\StoreIdProvider;
use SeQura\Core\BusinessLogic\Domain\Multistore\StoreContext;
use SeQura\WC\Services\Log\Interface_Logger_Service;
use Throwable;

/**
 * Sends the affiliate conversion/cancellation postbacks through integration-core.
 *
 * The plugin hands the order-specific data to the core affiliate controller, which sources the
 * offer/token from the stored affiliate settings (the plugin never echoes the credentials),
 * resolves the endpoint from the merchant deployment and emits the postback. The controller is
 * called directly rather than through the static CheckoutAPI facade so it can be unit-tested; the
 * store context and the error handling the facade's aspects would add are reproduced here.
 *
 * The merchant is resolved from the order country only. A store's merchants each map to a single
 * deployment target, so a wrong guess would send the postback to a target where it is not recorded;
 * when the country has no connected merchant the send is skipped rather than routed to an arbitrary
 * one.
 */
class Core_Affiliate_Postback_Client implements Interface_Affiliate_Postback_Client {

	/**
	 * Core affiliate checkout controller.
	 *
	 * @var AffiliateController
	 */
	private $affiliate_controller;

	/**
	 * Core credentials service, used to resolve the merchant for the deployment.
	 *
	 * @var CredentialsService
	 */
	private $credentials_service;

	/**
	 * Store id provider.
	 *
	 * @var StoreIdProvider
	 */
	private $store_id_provider;

	/**
	 * Logger service.
	 *
	 * @var Interface_Logger_Service
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @param AffiliateController       $affiliate_controller Core affiliate checkout controller.
	 * @param CredentialsService       $credentials_service  Core credentials service.
	 * @param StoreIdProvider          $store_id_provider    Store id provider.
	 * @param Interface_Logger_Service $logger               Logger service.
	 */
	public function __construct(
		AffiliateController $affiliate_controller,
		CredentialsService $credentials_service,
		StoreIdProvider $store_id_provider,
		Interface_Logger_Service $logger
	) {
		$this->affiliate_controller = $affiliate_controller;
		$this->credentials_service  = $credentials_service;
		$this->store_id_provider    = $store_id_provider;
		$this->logger               = $logger;
	}

	/**
	 * Send the affiliate conversion postback.
	 *
	 * @inheritDoc
	 */
	public function send_conversion( string $country, string $transaction_id, float $amount, string $order_reference ): string {
		return $this->dispatch(
			'conversion',
			$country,
			function ( string $merchant_id ) use ( $transaction_id, $amount, $order_reference ): AffiliatePostbackResponse {
				return $this->affiliate_controller->reportConversion(
					new SendConversionRequest( $merchant_id, $transaction_id, $amount, $order_reference )
				);
			}
		);
	}

	/**
	 * Send the affiliate cancellation postback.
	 *
	 * @inheritDoc
	 */
	public function send_cancellation( string $country, string $transaction_id ): string {
		return $this->dispatch(
			'cancellation',
			$country,
			function ( string $merchant_id ) use ( $transaction_id ): AffiliatePostbackResponse {
				return $this->affiliate_controller->reportCancellation(
					new SendCancellationRequest( $merchant_id, $transaction_id )
				);
			}
		);
	}

	/**
	 * Resolve the store context + merchant, run the postback, and map the outcome to a RESULT_*
	 * constant. Mirrors the CheckoutAPI facade aspects (store context + error handling): a thrown
	 * error becomes RESULT_FAILED (retry), a dispatched postback RESULT_SENT, and a no-op (disabled
	 * store or no merchant for the country) RESULT_SKIPPED.
	 *
	 * @param string                                       $kind    Postback kind, for logging.
	 * @param string                                       $country Order country used to resolve the merchant.
	 * @param callable(string): AffiliatePostbackResponse $report  Receives the resolved merchant id and performs the postback.
	 */
	private function dispatch( string $kind, string $country, callable $report ): string {
		$result = self::RESULT_FAILED;
		try {
			StoreContext::doWithStore(
				$this->store_id_provider->getCurrentStoreId(),
				function () use ( $kind, $country, $report, &$result ): void {
					$merchant_id = $this->resolve_merchant_id( $country );
					if ( '' === $merchant_id ) {
						$this->logger->log_error(
							"Affiliate {$kind} postback skipped: no connected merchant for country '{$country}'",
							__FUNCTION__,
							__CLASS__
						);
						$result = self::RESULT_SKIPPED;
						return;
					}
					$response = $report( $merchant_id );
					$result   = $response->isDispatched() ? self::RESULT_SENT : self::RESULT_SKIPPED;
				}
			);

			return $result;
		} catch ( Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			return self::RESULT_FAILED;
		}
	}

	/**
	 * Resolve the merchant id for the order country. Returns an empty string when the country is
	 * unknown or has no connected merchant, so the caller skips rather than routes to the wrong
	 * deployment target.
	 *
	 * @param string $country Order country (may be empty).
	 */
	private function resolve_merchant_id( string $country ): string {
		if ( '' === $country ) {
			return '';
		}
		$credentials = $this->credentials_service->getCredentialsByCountryCode( $country );
		return $credentials ? $credentials->getMerchantId() : '';
	}
}
