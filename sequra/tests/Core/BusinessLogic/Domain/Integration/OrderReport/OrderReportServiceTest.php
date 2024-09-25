<?php
/**
 * Tests for the Async_Process_Controller class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Services\Report;

use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Platform;
use WP_UnitTestCase;
use SeQura\WC\Core\Extension\Infrastructure\Configuration\Configuration;
use SeQura\WC\Services\Order\Interface_Order_Service;
use SeQura\WC\Core\Implementation\BusinessLogic\Domain\Integration\OrderReport\Order_Report_Service;
use SeQura\WC\Services\Cart\Interface_Cart_Service;
use SeQura\WC\Services\I18n\Interface_I18n;
use SeQura\WC\Services\Pricing\Interface_Pricing_Service;
use WC_Order;

class OrderReportServiceTest extends WP_UnitTestCase {

	private $configuration;
	private $order_service;
	private $pricing_service;
	private $cart_service;
	private $wc_data;
	private $env_data;
	private $integration_name;
	private $integration_version;
	private $i18n;
	private $order_ids;
	private $order_report_service;

	public function set_up(): void {        
		$this->integration_name    = 'WC';
		$this->integration_version = '99.99.99';
		$this->configuration       = $this->createMock( Configuration::class );
		$this->configuration->method( 'getIntegrationName' )->willReturn( $this->integration_name );
		$this->configuration->method( 'get_module_version' )->willReturn( $this->integration_version );
		
		$this->wc_data         = array(
			'Version' => '99.99.99',
		);
		$this->env_data        = array(
			'uname'       => 'Uname',
			'db_name'     => 'DB name',
			'db_version'  => '1.0.0',
			'php_version' => '99.99.0',
		);
		$this->pricing_service = $this->createMock( Interface_Pricing_Service::class );
		$this->cart_service    = $this->createMock( Interface_Cart_Service::class );
		$this->order_service   = $this->createMock( Interface_Order_Service::class );
		$this->i18n            = $this->createMock( Interface_I18n::class );

		$this->order_report_service = new Order_Report_Service(
			$this->configuration,
			$this->wc_data,
			$this->env_data,
			$this->pricing_service,
			$this->cart_service,
			$this->order_service,
			$this->i18n
		);
		
		// populate database with orders.
		$this->order_ids = $this->populate_db_with_orders();
	}

	public function tear_down() {
		// clean up database.
		$this->remove_orders_from_db( $this->order_ids );
	}

	public function testGetPlatform() {
		$this->assertEquals(
			$this->order_report_service->getPlatform(),
			new Platform(
				$this->integration_name,
				$this->wc_data['Version'],
				$this->env_data['uname'],
				$this->env_data['db_name'],
				$this->env_data['db_version'],
				$this->integration_version,
				$this->env_data['php_version']
			)
		);
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
}
