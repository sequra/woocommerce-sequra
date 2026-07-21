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
 * store context and the error-to-boolean handling the facade's aspects would add are reproduced
 * here.
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
	public function send_conversion( string $country, string $transaction_id, float $amount, string $order_reference ): bool {
		return $this->dispatch(
			'conversion',
			$country,
			function ( string $merchant_id ) use ( $transaction_id, $amount, $order_reference ): bool {
				return $this->affiliate_controller->reportConversion(
					new SendConversionRequest( $merchant_id, $transaction_id, $amount, $order_reference )
				)->isSuccessful();
			}
		);
	}

	/**
	 * Send the affiliate cancellation postback.
	 *
	 * @inheritDoc
	 */
	public function send_cancellation( string $country, string $transaction_id ): bool {
		return $this->dispatch(
			'cancellation',
			$country,
			function ( string $merchant_id ) use ( $transaction_id ): bool {
				return $this->affiliate_controller->reportCancellation(
					new SendCancellationRequest( $merchant_id, $transaction_id )
				)->isSuccessful();
			}
		);
	}

	/**
	 * Resolve the store context + merchant, run the postback, and turn any failure into false so the
	 * caller can retry. Mirrors the CheckoutAPI facade aspects (store context + error handling).
	 *
	 * @param string   $kind    Postback kind, for logging.
	 * @param string   $country Order billing country used to resolve the merchant.
	 * @param callable $report  Receives the resolved merchant id and performs the postback.
	 */
	private function dispatch( string $kind, string $country, callable $report ): bool {
		try {
			return (bool) StoreContext::doWithStore(
				$this->store_id_provider->getCurrentStoreId(),
				function () use ( $kind, $country, $report ): bool {
					$merchant_id = $this->resolve_merchant_id( $country );
					if ( '' === $merchant_id ) {
						$this->logger->log_error(
							"Affiliate {$kind} postback skipped: no connected merchant for the store",
							__FUNCTION__,
							__CLASS__
						);
						return false;
					}
					return (bool) $report( $merchant_id );
				}
			);
		} catch ( Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			return false;
		}
	}

	/**
	 * Resolve a merchant id for the current store: prefer the order's country, then fall back to any
	 * connected merchant. All of a store's merchants resolve to the same deployment, so any of them
	 * routes the postback to the right seQura backend. Returns an empty string when the store has no
	 * credentials.
	 *
	 * @param string $country Order billing country (may be empty).
	 */
	private function resolve_merchant_id( string $country ): string {
		if ( '' !== $country ) {
			$credentials = $this->credentials_service->getCredentialsByCountryCode( $country );
			if ( $credentials ) {
				return $credentials->getMerchantId();
			}
		}
		$all = $this->credentials_service->getCredentials();
		return array() === $all ? '' : $all[0]->getMerchantId();
	}
}
