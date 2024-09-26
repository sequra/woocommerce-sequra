<?php
/**
 * Tests for the Async_Process_Controller class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Core\BusinessLogic\Domain\Integration\OrderReport;

require_once __DIR__ . '/../../../../../Fixtures/Store.php';

use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Address;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Cart;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Customer;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\DeliveryMethod;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\MerchantReference;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Platform;
use SeQura\Core\BusinessLogic\Domain\OrderReport\Models\OrderReport;
use SeQura\Core\BusinessLogic\Domain\OrderReport\Models\OrderStatistics;
use WP_UnitTestCase;
use SeQura\WC\Core\Extension\Infrastructure\Configuration\Configuration;
use SeQura\WC\Services\Order\Interface_Order_Service;
use SeQura\WC\Core\Implementation\BusinessLogic\Domain\Integration\OrderReport\Order_Report_Service;
use SeQura\WC\Services\Cart\Interface_Cart_Service;
use SeQura\WC\Services\I18n\Interface_I18n;
use SeQura\WC\Services\Pricing\Interface_Pricing_Service;
use SeQura\WC\Tests\Fixtures\Store;
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
	private $order_report_service;

	/**
	 * Store instance.
	 * @var Store
	 */
	private $store;

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

		$this->store = new Store();
		$this->store->set_up();
	}

	public function tear_down() {
		// clean up database.
		$this->store->tear_down();
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

	public function testGetOrderReports() {
		// Setup.
		
		/**
		 * Order
		 * @var WC_Order
		 */
		$order     = $this->store->get_orders()[0];
		$order_ids = array(
			0, // Bad order.
			$order->get_id(),
		);

		$this->order_service->expects( $this->once() )
		->method( 'get_cart_info' )
		->with( $order )
		->willReturn( null );

		$delivery_method = new DeliveryMethod( 'delivery_method' );
		$this->order_service->expects( $this->once() )
		->method( 'get_delivery_method' )
		->with( $order )
		->willReturn( $delivery_method );

		$this->i18n->expects( $this->once() )
		->method( 'get_lang' )
		->willReturn( 'es' );

		$customer = new Customer( 
			'test@sequra.es',
			'es',
			'127.0.0.1',
			'User Agent'
		);
		$this->order_service->expects( $this->once() )
		->method( 'get_customer' )
		->with( $order, 'es' )
		->willReturn( $customer );

		$address = new Address(
			'Company',
			'Address line 1',
			'Address line 2',
			'08001',
			'Barcelona',
			'ES'
		);
		$this->order_service->expects( $this->exactly( 2 ) )
		->method( 'get_address' )
		->withConsecutive( 
			array( $order, true ),
			array( $order, false )
		)
		->willReturnOnConsecutiveCalls( $address, $address );

		$this->cart_service->expects( $this->once() )
		->method( 'get_items' )
		->with( $order )
		->willReturn( array() );
		
		$this->cart_service->expects( $this->once() )
		->method( 'get_handling_items' )
		->with( $order )
		->willReturn( array() );

		$this->cart_service->expects( $this->once() )
		->method( 'get_discount_items' )
		->with( $order )
		->willReturn( array() );

		// Execute.
		$order_reports = $this->order_report_service->getOrderReports( $order_ids );
		
		// Assert.
		$this->assertCount( 1, $order_reports );
		$this->assertEquals(
			$order_reports[0],
			new OrderReport(
				'delivered',
				new MerchantReference( $order->get_id() ),
				new Cart(
					$order->get_currency( 'edit' ),
					false,
					array(),
					null,
					null,
					$order->get_date_completed()->format( 'Y-m-d H:i:s' )
				),
				$delivery_method,
				$customer,
				null,
				null,
				null,
				$address,
				$address
			) 
		);
	}

	public function testGetOrderStatistics() {
		// Setup.
		
		/**
		 * Order
		 * @var WC_Order
		 */
		$order     = $this->store->get_orders()[0];
		$order_ids = array(
			0, // Bad order.
			$order->get_id(),
		);

		$cents = (int) ( $order->get_total( 'edit' ) * 100 );
		$this->pricing_service->expects( $this->once() )
		->method( 'to_cents' )
		->with( $order->get_total( 'edit' ) )
		->willReturn( $cents );

		// Execute.
		$order_reports = $this->order_report_service->getOrderStatistics( $order_ids );
		
		// Assert.
		$this->assertCount( 1, $order_reports );
		$this->assertEquals(
			$order_reports[0],
			new OrderStatistics(
				$order->get_date_created()->format( 'Y-m-d' ),
				$order->get_currency( 'edit' ),
				$cents,
				new MerchantReference( $order->get_id() ),
				'SQ',
				$order->get_billing_country(),
				null,
				'shipped',
				$order->get_status(),
				null
			) 
		);
	}
}
