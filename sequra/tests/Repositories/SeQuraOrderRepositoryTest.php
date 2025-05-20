<?php
/**
 * Tests for the Async_Process_Controller class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Controllers\Hooks\Process;

require_once __DIR__ . '/../Fixtures/SeQuraOrderTable.php';

use SeQura\Core\Infrastructure\ServiceRegister;
use SeQura\WC\Repositories\SeQura_Order_Repository;
use SeQura\WC\Tests\Fixtures\SeQuraOrderTable;
use WP_UnitTestCase;

class SeQuraOrderRepositoryTest extends WP_UnitTestCase {

	private $repository;
	private $order_table;
	
	public function set_up() {
		ServiceRegister::registerService(
			\wpdb::class, 
			function () {
				global $wpdb;
				return $wpdb;
			}
		);
		$this->order_table = new SeQuraOrderTable();
		$this->order_table->fill_with_sample_data();
		$this->repository = new SeQura_Order_Repository();
	}

	public function tear_down() {
		$this->order_table->reset();
	}

	public function testDeleteOldAndInvalid_happyPath_RightDataIsDeleted() {
		// Setup.
		$expected_ids = array( '1', '3' );

		// Execute.
		$this->repository->delete_old_and_invalid();
		$actual_ids = $this->order_table->get_ids();

		// Assert.
		$this->assertEquals( count( $expected_ids ), count( array_intersect( $actual_ids, $expected_ids ) ) );
	}
}
