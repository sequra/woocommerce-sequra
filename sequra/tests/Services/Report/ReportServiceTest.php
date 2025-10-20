<?php
/**
 * Tests for the Async_Process_Controller class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Services\Report;

use SeQura\Core\BusinessLogic\Domain\CountryConfiguration\Models\CountryConfiguration;
use WP_UnitTestCase;
use SeQura\WC\Services\Report\Report_Service;
use SeQura\Core\BusinessLogic\Webhook\Services\ShopOrderService;
use SeQura\Core\BusinessLogic\Domain\StatisticalData\RepositoryContracts\StatisticalDataRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\CountryConfiguration\RepositoryContracts\CountryConfigurationRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\OrderReport\Service\OrderReportService;
use SeQura\WC\Services\Order\Interface_Order_Service;
use SeQura\Core\BusinessLogic\Domain\StatisticalData\Models\StatisticalData;
use SeQura\Core\Infrastructure\ServiceRegister;
use WC_Order;

class ReportServiceTest extends WP_UnitTestCase {

	/** @var \SeQura\Core\BusinessLogic\Webhook\Services\ShopOrderService&\PHPUnit\Framework\MockObject\MockObject */
	private $shop_order_service;
	/** @var \SeQura\Core\BusinessLogic\Domain\StatisticalData\RepositoryContracts\StatisticalDataRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject */
	private $statistical_data_repository;
	/** @var \SeQura\Core\BusinessLogic\Domain\CountryConfiguration\RepositoryContracts\CountryConfigurationRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject */
	private $country_configuration_repository;
	/** @var \SeQura\WC\Services\Order\Interface_Order_Service&\PHPUnit\Framework\MockObject\MockObject */
	private $order_service;
	private $report_service;
	private $order_report_service;
	private $order_ids;

	public function set_up(): void {
		$this->shop_order_service               = $this->createMock( ShopOrderService::class );
		$this->statistical_data_repository      = $this->createMock( StatisticalDataRepositoryInterface::class );
		$this->country_configuration_repository = $this->createMock( CountryConfigurationRepositoryInterface::class );
		$this->order_service                    = $this->createMock( Interface_Order_Service::class );
		$order_report_service                   = $this->createMock( OrderReportService::class );
		$order_report_service->method( 'sendReport' )->willReturn( true );
		$this->order_report_service = $order_report_service;

		ServiceRegister::registerService(
			OrderReportService::class,
			function () use ( $order_report_service ) {
				return $order_report_service;
			}
		);

		$this->report_service = new Report_Service(
			$this->shop_order_service,
			$this->statistical_data_repository,
			$this->country_configuration_repository,
			$this->order_service
		);
		
		// populate database with orders.
		$this->order_ids = $this->populate_db_with_orders();
	}

	public function tear_down() {
		// clean up database.
		$this->remove_orders_from_db( $this->order_ids );
	}

	private function populate_db_with_orders(): array {
		$order_ids = array();
		for ( $i = 0; $i < 3; $i++ ) { 
			$order       = new WC_Order();
			$order_ids[] = $order->save();
		}
		return $order_ids;
	}

	private function remove_orders_from_db( array $order_ids ): void {
		foreach ( $order_ids as $order_id ) {
			wc_get_order( $order_id )->delete( true );
		}
	}

	private function setup_for_send_delivery_report( array $report_order_ids, array $statistics_order_ids, ?string $merchant_id, bool $isSendStatisticalData ) {
		$countryConfiguration = null;
		if ( null !== $merchant_id ) {
			$countryConfiguration = $this->createMock( CountryConfiguration::class );
			$countryConfiguration->method( 'getMerchantId' )->willReturn( $merchant_id );
		}
		$this->country_configuration_repository->method( 'getCountryConfiguration' )->willReturn( array( $countryConfiguration ) );

		$this->shop_order_service->expects( $this->once() )
		->method( 'getReportOrderIds' )
		->with( 0, -1 )
		->willReturn( $report_order_ids );
			
		if ( $isSendStatisticalData ) {
			$this->shop_order_service->expects( $this->once() )
			->method( 'getStatisticsOrderIds' )
			->with( 0, -1 )
			->willReturn( $statistics_order_ids );
		}
		
		$statistical_data = $this->createMock( StatisticalData::class );
		$statistical_data->method( 'isSendStatisticalData' )->willReturn( $isSendStatisticalData );    
		
		$this->statistical_data_repository->expects( $this->once() )
		->method( 'getStatisticalData' )
		->willReturn( $statistical_data );
	}

	public function testSendDeliveryReport_noStatisticsOrderIds_doNotSendReport() {
		// Setup .
		$this->setup_for_send_delivery_report( 
			array(), 
			array(), 
			'dummy', 
			true
		);

		$this->order_report_service->expects( $this->never() )->method( 'sendReport' );
		$this->order_service->expects( $this->never() )->method( 'set_as_sent_to_sequra' );

		// Execute .
		$this->report_service->send_delivery_report();
	}

	public function testSendDeliveryReport_sendStatisticalDataIsDisabled_doNotSendReport() {
		// Setup.
		$this->setup_for_send_delivery_report( 
			array(), 
			$this->order_ids, 
			'dummy', 
			false
		);

		$this->order_report_service->expects( $this->never() )->method( 'sendReport' );
		$this->order_service->expects( $this->never() )->method( 'set_as_sent_to_sequra' );

		// Execute.
		$this->report_service->send_delivery_report();
	}

	public function testSendDeliveryReport_noMerchantId_doNotSendReport() {
		// Setup.
		$this->setup_for_send_delivery_report( 
			array(), 
			array(), 
			null, 
			true
		);

		$this->order_report_service->expects( $this->never() )->method( 'sendReport' );
		$this->order_service->expects( $this->never() )->method( 'set_as_sent_to_sequra' );

		// Execute.
		$this->report_service->send_delivery_report();
	}

	public function testSendDeliveryReport_availableStatisticalData_sendReport() {
		// Setup.
		$this->setup_for_send_delivery_report( 
			$this->order_ids, 
			$this->order_ids, 
			'dummy', 
			true
		);

		$this->order_report_service->expects( $this->once() )
		->method( 'sendReport' );

		$this->order_service->expects( $this->exactly( 3 ) )
		->method( 'set_as_sent_to_sequra' )
		->withConsecutive( 
			array( $this->equalTo( wc_get_order( $this->order_ids[0] ) ) ),
			array( $this->equalTo( wc_get_order( $this->order_ids[1] ) ) ),
			array( $this->equalTo( wc_get_order( $this->order_ids[2] ) ) )
		);

		// Execute.
		$this->report_service->send_delivery_report();
	}
}
