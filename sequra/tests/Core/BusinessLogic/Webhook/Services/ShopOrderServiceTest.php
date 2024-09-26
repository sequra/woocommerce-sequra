<?php
/**
 * Tests for the ShopOrderService class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Core\BusinessLogic\Webhook\Services;

use SeQura\Core\BusinessLogic\Domain\Order\RepositoryContracts\SeQuraOrderRepositoryInterface;
use SeQura\WC\Core\Implementation\BusinessLogic\Webhook\Services\Shop_Order_Service;
use SeQura\WC\Services\Interface_Logger_Service;
use SeQura\WC\Tests\Fixtures\Store;
use WP_UnitTestCase;

require_once __DIR__ . '/../../../../Fixtures/Store.php';

class ShopOrderServiceTest extends WP_UnitTestCase {

	private $sq_order_repo;
	private $shop_order_service;
	private $logger;

	/**
	 * Store instance.
	 * @var Store
	 */
	private $store;

	public function set_up(): void {        
		$this->sq_order_repo = $this->createMock( SeQuraOrderRepositoryInterface::class );
		$this->logger        = $this->createMock( Interface_Logger_Service::class );

		$this->shop_order_service = new Shop_Order_Service( $this->sq_order_repo, $this->logger );

		$this->store = new Store();
		$this->store->set_up();
	}

	public function tear_down() {
		// clean up database.
		$this->store->tear_down();
	}
	/**
	 * @dataProvider dataProvider_statisticsOrderIds
	 */
	public function testGetStatisticsOrderIds( $page, $limit, $index ) {
		$result   = $this->shop_order_service->getStatisticsOrderIds( $page, $limit );
		$expected = $this->result_statisticsOrderIds( $index );
		$this->assertTrue( count( $expected ) === count( array_intersect( $expected, $result ) ) );
	}

	/**
	 * @dataProvider dataProvider_reportOrderIds
	 */
	public function testGetReportOrderIds( $page, $limit, $expected ) {
		$result = $this->shop_order_service->getReportOrderIds( $page, $limit );
		$this->assertEquals( $result, $expected );
	}

	public function dataProvider_reportOrderIds() {
		return array(
			array( 0, -1, array() ),
			array( 0, 100, array() ),
			array( 1, 500, array() ),
		);
	}

	private function result_statisticsOrderIds( $index ) {
		$orders = $this->store->get_orders();
		return array(
			array( $orders[0]->get_id(), $orders[2]->get_id() ),
			array( $orders[0]->get_id() ),
			array(),
		)[ $index ];
	}

	public function dataProvider_statisticsOrderIds() {
		return array(
			array( 0, -1, 0 ),
			array( 0, 1, 1 ),
			array( 1, 500, 2 ),
		);
	}
}
