<?php
/**
 * Tests for the Core_Affiliate_Postback_Client class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Services\Affiliate;

use RuntimeException;
use SeQura\Core\BusinessLogic\CheckoutAPI\Affiliate\AffiliateController;
use SeQura\Core\BusinessLogic\CheckoutAPI\Affiliate\Requests\SendCancellationRequest;
use SeQura\Core\BusinessLogic\CheckoutAPI\Affiliate\Requests\SendConversionRequest;
use SeQura\Core\BusinessLogic\CheckoutAPI\Affiliate\Responses\AffiliatePostbackResponse;
use SeQura\Core\BusinessLogic\Domain\Connection\Models\Credentials;
use SeQura\Core\BusinessLogic\Domain\Connection\Services\CredentialsService;
use SeQura\Core\BusinessLogic\Domain\Integration\Store\StoreIdProvider;
use SeQura\WC\Services\Affiliate\Core_Affiliate_Postback_Client;
use SeQura\WC\Services\Affiliate\Interface_Affiliate_Postback_Client;
use SeQura\WC\Services\Log\Interface_Logger_Service;
use WP_UnitTestCase;

class CoreAffiliatePostbackClientTest extends WP_UnitTestCase {

	/**
	 * @var AffiliateController&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $affiliate_controller;

	/**
	 * @var CredentialsService&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $credentials_service;

	/**
	 * @var StoreIdProvider&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $store_id_provider;

	/**
	 * @var Interface_Logger_Service&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $logger;

	/**
	 * @var Core_Affiliate_Postback_Client
	 */
	private $client;

	public function set_up(): void {
		parent::set_up();
		$this->affiliate_controller = $this->createMock( AffiliateController::class );
		$this->credentials_service  = $this->createMock( CredentialsService::class );
		$this->store_id_provider    = $this->createMock( StoreIdProvider::class );
		$this->logger               = $this->createMock( Interface_Logger_Service::class );

		$this->store_id_provider->method( 'getCurrentStoreId' )->willReturn( '1' );

		$this->client = new Core_Affiliate_Postback_Client(
			$this->affiliate_controller,
			$this->credentials_service,
			$this->store_id_provider,
			$this->logger
		);
	}

	/**
	 * Build a credentials mock returning the given merchant id.
	 *
	 * @param string $merchant_id Merchant id the credentials expose.
	 */
	private function credentials_with_merchant( string $merchant_id ): Credentials {
		$credentials = $this->createMock( Credentials::class );
		$credentials->method( 'getMerchantId' )->willReturn( $merchant_id );
		return $credentials;
	}

	public function testSendConversionResolvesMerchantByCountryAndReportsSent(): void {
		$this->credentials_service->method( 'getCredentialsByCountryCode' )
			->with( 'ES' )
			->willReturn( $this->credentials_with_merchant( 'merchant_es' ) );

		$this->affiliate_controller->expects( $this->once() )
			->method( 'reportConversion' )
			->with(
				$this->callback(
					static function ( SendConversionRequest $request ): bool {
						return 'merchant_es' === $request->getMerchantId()
							&& 'TX1' === $request->getTransactionId()
							&& 12.5 === $request->getAmount()
							&& '85' === $request->getOrderReference();
					}
				)
			)
			->willReturn( new AffiliatePostbackResponse( true ) );

		$this->assertSame(
			Interface_Affiliate_Postback_Client::RESULT_SENT,
			$this->client->send_conversion( 'ES', 'TX1', 12.5, '85' )
		);
	}

	public function testSendConversionSkipsWhenCountryHasNoCredentials(): void {
		$this->credentials_service->method( 'getCredentialsByCountryCode' )->willReturn( null );

		$this->affiliate_controller->expects( $this->never() )->method( 'reportConversion' );
		$this->logger->expects( $this->once() )->method( 'log_error' );

		$this->assertSame(
			Interface_Affiliate_Postback_Client::RESULT_SKIPPED,
			$this->client->send_conversion( 'ES', 'TX1', 12.5, '85' )
		);
	}

	public function testSendConversionSkipsWhenCountryIsEmpty(): void {
		$this->credentials_service->expects( $this->never() )->method( 'getCredentialsByCountryCode' );
		$this->affiliate_controller->expects( $this->never() )->method( 'reportConversion' );

		$this->assertSame(
			Interface_Affiliate_Postback_Client::RESULT_SKIPPED,
			$this->client->send_conversion( '', 'TX1', 12.5, '85' )
		);
	}

	public function testSendConversionSkipsWhenStoreIsDisabled(): void {
		// A disabled store returns a successful-but-not-dispatched response: nothing went out, so the
		// caller must not mark it sent (isDispatched(), not isSuccessful()).
		$this->credentials_service->method( 'getCredentialsByCountryCode' )
			->willReturn( $this->credentials_with_merchant( 'merchant_es' ) );
		$this->affiliate_controller->method( 'reportConversion' )
			->willReturn( new AffiliatePostbackResponse( false ) );

		$this->assertSame(
			Interface_Affiliate_Postback_Client::RESULT_SKIPPED,
			$this->client->send_conversion( 'ES', 'TX1', 12.5, '85' )
		);
	}

	public function testSendConversionFailsAndLogsOnControllerError(): void {
		$this->credentials_service->method( 'getCredentialsByCountryCode' )
			->willReturn( $this->credentials_with_merchant( 'merchant_es' ) );
		$this->affiliate_controller->method( 'reportConversion' )
			->willThrowException( new RuntimeException( 'network down' ) );

		$this->logger->expects( $this->once() )->method( 'log_throwable' );

		$this->assertSame(
			Interface_Affiliate_Postback_Client::RESULT_FAILED,
			$this->client->send_conversion( 'ES', 'TX1', 12.5, '85' )
		);
	}

	public function testSendCancellationResolvesMerchantAndReportsSent(): void {
		$this->credentials_service->method( 'getCredentialsByCountryCode' )
			->with( 'ES' )
			->willReturn( $this->credentials_with_merchant( 'merchant_es' ) );

		$this->affiliate_controller->expects( $this->once() )
			->method( 'reportCancellation' )
			->with(
				$this->callback(
					static function ( SendCancellationRequest $request ): bool {
						return 'merchant_es' === $request->getMerchantId()
							&& 'TX1' === $request->getTransactionId();
					}
				)
			)
			->willReturn( new AffiliatePostbackResponse( true ) );

		$this->assertSame(
			Interface_Affiliate_Postback_Client::RESULT_SENT,
			$this->client->send_cancellation( 'ES', 'TX1' )
		);
	}
}
