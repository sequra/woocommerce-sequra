<?php
/**
 * Tests for the Async_Process_Controller class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Core\BusinessLogic\Domain\Integration\OrderReport;

use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Address;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Cart;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Customer;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\DeliveryMethod;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\MerchantReference;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Platform;
use SeQura\Core\BusinessLogic\Domain\OrderReport\Models\OrderReport;
use SeQura\Core\BusinessLogic\Domain\OrderReport\Models\OrderStatistics;
use WP_UnitTestCase;
use SeQura\WC\Services\Order\Interface_Order_Service;
use SeQura\WC\Core\Implementation\BusinessLogic\Domain\Integration\OrderReport\Order_Report_Service;
use SeQura\WC\Services\Cart\Interface_Cart_Service;
use SeQura\WC\Services\I18n\Interface_I18n;
use SeQura\WC\Services\Order\Builder\Interface_Order_Address_Builder;
use SeQura\WC\Services\Order\Interface_Order_Customer_Builder;
use SeQura\WC\Services\Order\Interface_Order_Delivery_Method_Builder;
use SeQura\WC\Services\Platform\Interface_Platform_Provider;
use SeQura\WC\Services\Pricing\Interface_Pricing_Service;
use SeQura\WC\Tests\Fixtures\Store;
use WC_Order;

class OrderReportServiceTest extends WP_UnitTestCase {

	/** @var \SeQura\WC\Services\Order\Interface_Order_Service&\PHPUnit\Framework\MockObject\MockObject */
	private $order_service;
	/** @var \SeQura\WC\Services\Pricing\Interface_Pricing_Service&\PHPUnit\Framework\MockObject\MockObject */
	private $pricing_service;
	/** @var \SeQura\WC\Services\Cart\Interface_Cart_Service&\PHPUnit\Framework\MockObject\MockObject */
	private $cart_service;
	private $wc_data;
	private $env_data;
	private $integration_name;
	private $integration_version;
	/** @var \SeQura\WC\Services\I18n\Interface_I18n&\PHPUnit\Framework\MockObject\MockObject */
	private $i18n;
	/** @var \SeQura\WC\Core\Implementation\BusinessLogic\Domain\Integration\OrderReport\Order_Report_Service */
	private $order_report_service;
	/** @var Platform */
	private $platform;

	/** @var \SeQura\WC\Services\Platform\Interface_Platform_Provider&\PHPUnit\Framework\MockObject\MockObject */
	private $platform_provider;

	/** @var \SeQura\WC\Services\Order\Interface_Order_Delivery_Method_Builder&\PHPUnit\Framework\MockObject\MockObject */
	private $delivery_method_builder;

	/** @var \SeQura\WC\Services\Order\Builder\Interface_Order_Address_Builder&\PHPUnit\Framework\MockObject\MockObject */
	private $address_builder;

	/** @var \SeQura\WC\Services\Order\Interface_Order_Customer_Builder&\PHPUnit\Framework\MockObject\MockObject */
	private $customer_builder;

	/**
	 * Store instance.
	 * @var Store
	 */
	private $store;

	public function set_up(): void {        
		$this->integration_name    = 'WC';
		$this->integration_version = '99.99.99';

		$this->wc_data  = array(
			'Version' => '99.99.99',
		);
		$this->env_data = array(
			'uname'       => 'Uname',
			'db_name'     => 'DB name',
			'db_version'  => '1.0.0',
			'php_version' => '99.99.0',
		);

		$this->platform = new Platform(
			$this->integration_name,
			$this->wc_data['Version'],
			$this->env_data['uname'],
			$this->env_data['db_name'],
			$this->env_data['db_version'],
			$this->integration_version,
			$this->env_data['php_version']
		);

		$this->pricing_service         = $this->createMock( Interface_Pricing_Service::class );
		$this->cart_service            = $this->createMock( Interface_Cart_Service::class );
		$this->order_service           = $this->createMock( Interface_Order_Service::class );
		$this->i18n                    = $this->createMock( Interface_I18n::class );
		$this->platform_provider       = $this->createMock( Interface_Platform_Provider::class );
		$this->delivery_method_builder = $this->createMock( Interface_Order_Delivery_Method_Builder::class );
		$this->address_builder         = $this->createMock( Interface_Order_Address_Builder::class );
		$this->customer_builder        = $this->createMock( Interface_Order_Customer_Builder::class );

		$this->order_report_service = new Order_Report_Service(
			$this->platform_provider,
			$this->pricing_service,
			$this->cart_service,
			$this->order_service,
			$this->i18n,
			$this->delivery_method_builder,
			$this->address_builder,
			$this->customer_builder
		);

		$this->store = new Store();
		$this->store->set_up();
	}

	public function tear_down() {
		// clean up database.
		$this->store->tear_down();
	}

	public function testGetPlatform() {
		$this->platform_provider
		->expects( $this->once() )
		->method( 'get' )
		->willReturn( $this->platform );

		$this->assertEquals( $this->order_report_service->getPlatform(), $this->platform );
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
		$this->delivery_method_builder->expects( $this->once() )
		->method( 'build' )
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
		$this->customer_builder->expects( $this->once() )
		->method( 'build' )
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
		
		$invoked_count = $this->exactly( 2 );
		$this->address_builder->expects( $invoked_count )
		->method( 'build' )
		->willReturnCallback(
			function ( $order, $billing ) use ( $address, $invoked_count ) {
				$this->assertEquals( $invoked_count->getInvocationCount() === 1, $billing );
				return $address;
			}
		);

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
					$this->order_service->get_order_completion_date( $order )
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
