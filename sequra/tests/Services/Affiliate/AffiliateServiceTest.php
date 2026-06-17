<?php
/**
 * Tests for the Affiliate_Service class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Services\Affiliate;

use SeQura\Core\BusinessLogic\Domain\Order\OrderStates;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\OrderStatusSettings\Services\Order_Status_Settings_Service;
use SeQura\WC\Services\Affiliate\Affiliate_Service;
use SeQura\WC\Services\Affiliate\Interface_Affiliate_Config_Provider;
use SeQura\WC\Services\Affiliate\Interface_Affiliate_Postback_Client;
use SeQura\WC\Services\Affiliate\Interface_Affiliate_Service;
use SeQura\WC\Services\Log\Interface_Logger_Service;
use WC_Order;
use WP_UnitTestCase;

class AffiliateServiceTest extends WP_UnitTestCase {

	private $config;
	private $order_status_settings;
	private $postback_client;
	private $logger;
	private $service;

	public function set_up(): void {
		parent::set_up();

		$this->config                = $this->createMock( Interface_Affiliate_Config_Provider::class );
		$this->order_status_settings = $this->createMock( Order_Status_Settings_Service::class );
		$this->postback_client       = $this->createMock( Interface_Affiliate_Postback_Client::class );
		$this->logger                = $this->createMock( Interface_Logger_Service::class );

		$this->config->method( 'is_enabled' )->willReturn( true );
		$this->config->method( 'get_settings' )->willReturn(
			array(
				'enabled'        => true,
				'offer_id'       => '4',
				'security_token' => 'tok123',
			)
		);

		$this->service = new Affiliate_Service(
			$this->config,
			$this->order_status_settings,
			$this->postback_client,
			$this->logger
		);
	}

	/**
	 * Build a mocked order with the given affiliate meta.
	 *
	 * @param string $transaction_id  Attributed transaction id.
	 * @param string $postback_status Current postback status meta.
	 * @param string $wc_status       Current WooCommerce order status (without the wc- prefix).
	 * @param int    $attempts        Current postback attempt count meta.
	 */
	private function make_order( string $transaction_id, string $postback_status, string $wc_status = 'completed', int $attempts = 0 ): WC_Order {
		$meta  = array(
			'_sq_affiliate_transaction_id'    => $transaction_id,
			'_sq_affiliate_postback_status'   => $postback_status,
			'_sq_affiliate_postback_attempts' => $attempts,
		);
		$order = $this->createMock( WC_Order::class );
		$order->method( 'get_meta' )->willReturnCallback(
			static function ( $key ) use ( $meta ) {
				return $meta[ $key ] ?? '';
			}
		);
		$order->method( 'get_id' )->willReturn( 85 );
		$order->method( 'get_status' )->willReturn( $wc_status );
		$order->method( 'get_subtotal' )->willReturn( 99.99 );

		return $order;
	}

	public function testDispatchConversionSendsPostbackWhenAttributedAndNotSent(): void {
		$this->order_status_settings->method( 'map_status_from_shop_to_sequra' )
			->willReturn( OrderStates::STATE_APPROVED );

		$this->postback_client->expects( $this->once() )
			->method( 'send_conversion' )
			->with( '4', 'tok123', 'ABC123', 99.99, 85 )
			->willReturn( true );

		$this->service->dispatch( $this->make_order( 'ABC123', 'pending' ), 'conversion' );
	}

	public function testDispatchConversionSkippedWhenOrderNoLongerApproved(): void {
		// The cancellation cron ran first (order is now cancelled); the conversion cron must not
		// report a conversion for an order that is no longer in the approved state.
		$this->order_status_settings->method( 'map_status_from_shop_to_sequra' )
			->willReturn( OrderStates::STATE_CANCELLED );

		$this->postback_client->expects( $this->never() )->method( 'send_conversion' );

		$this->service->dispatch( $this->make_order( 'ABC123', 'pending', 'cancelled' ), 'conversion' );
	}

	public function testDispatchConversionReschedulesOnTransientFailure(): void {
		$this->order_status_settings->method( 'map_status_from_shop_to_sequra' )
			->willReturn( OrderStates::STATE_APPROVED );
		$this->postback_client->method( 'send_conversion' )->willReturn( false );

		$this->service->dispatch( $this->make_order( 'ABC123', 'pending' ), 'conversion' );

		$this->assertNotFalse(
			wp_next_scheduled( Interface_Affiliate_Service::DISPATCH_HOOK, array( 85, 'conversion' ) )
		);
	}

	public function testDispatchConversionGivesUpAfterMaxAttempts(): void {
		$this->order_status_settings->method( 'map_status_from_shop_to_sequra' )
			->willReturn( OrderStates::STATE_APPROVED );
		$this->postback_client->method( 'send_conversion' )->willReturn( false );

		// Two prior attempts: this dispatch reaches the cap, so it must mark the postback failed
		// and stop re-scheduling.
		$order = $this->make_order( 'ABC123', 'pending', 'completed', 2 );
		$order->expects( $this->once() )
			->method( 'update_meta_data' )
			->with( '_sq_affiliate_postback_status', 'failed' );

		$this->service->dispatch( $order, 'conversion' );

		$this->assertFalse(
			wp_next_scheduled( Interface_Affiliate_Service::DISPATCH_HOOK, array( 85, 'conversion' ) )
		);
	}

	public function testDispatchDoesNothingWhenDisabled(): void {
		$config = $this->createMock( Interface_Affiliate_Config_Provider::class );
		$config->method( 'is_enabled' )->willReturn( false );
		$service = new Affiliate_Service( $config, $this->order_status_settings, $this->postback_client, $this->logger );

		$this->postback_client->expects( $this->never() )->method( 'send_conversion' );

		$service->dispatch( $this->make_order( 'ABC123', 'pending' ), 'conversion' );
	}

	public function testDispatchConversionSkippedWhenAlreadySent(): void {
		$this->postback_client->expects( $this->never() )->method( 'send_conversion' );

		$this->service->dispatch( $this->make_order( 'ABC123', 'sent' ), 'conversion' );
	}

	public function testDispatchCancellationSendsWhenAlreadyConverted(): void {
		$this->postback_client->expects( $this->once() )
			->method( 'send_cancellation' )
			->with( '4', 'tok123', 'ABC123' )
			->willReturn( true );

		$this->service->dispatch( $this->make_order( 'ABC123', 'sent' ), 'cancellation' );
	}

	public function testHandleStatusChangeEnqueuesConversionWhenApproved(): void {
		$this->order_status_settings->method( 'map_status_from_shop_to_sequra' )
			->willReturn( OrderStates::STATE_APPROVED );

		$this->service->handle_status_change( $this->make_order( 'ABC123', 'pending' ), 'completed' );

		$this->assertNotFalse(
			wp_next_scheduled( Interface_Affiliate_Service::DISPATCH_HOOK, array( 85, 'conversion' ) )
		);
	}
}
