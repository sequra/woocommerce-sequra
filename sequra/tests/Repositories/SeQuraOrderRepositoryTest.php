<?php
/**
 * Tests for the Async_Process_Controller class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Controllers\Hooks\Process;

require_once __DIR__ . '/../Fixtures/SeQuraOrderTable.php';

use SeQura\Core\BusinessLogic\Domain\Order\Models\SeQuraOrder;
use SeQura\Core\Infrastructure\ORM\Entity;
use SeQura\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use SeQura\Core\Infrastructure\ORM\Utility\IndexHelper;
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
		$this->repository->setEntityClass( SeQuraOrder::class );
	}

	public function tear_down() {
		$this->order_table->remove_table( true ); // Remove legacy table if exists.
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

	public function testPrepareTablesForMigration_happyPath_TablesArePrepared() {
		// Setup.
		$original_table_content = $this->order_table->get_all( false );

		// Execute.
		$result = $this->repository->prepare_tables_for_migration();

		// Assert.
		$this->assertTrue( $result );
		
		$table_content = $this->order_table->get_all( false );
		$this->assertTrue( is_array( $table_content ) && empty( $table_content ) );

		$legacy_table_content = $this->order_table->get_all( true );
		$this->assertEquals( $original_table_content, $legacy_table_content );

		$this->assertEquals( 4, $this->order_table->get_next_id_value( true ) ); // Verify if auto-increment is set correctly.
	}

	public function testMigrateNextRow_happyPath_DataIsMigrated() {
		// Setup.
		$original_table_content = $this->order_table->get_all( false );

		// Execute.
		$this->repository->prepare_tables_for_migration();
		$this->repository->migrate_next_row();

		// Assert.
		$legacy_table_content = $this->order_table->get_all( true );
		$this->assertEquals( array_slice( $original_table_content, 1 ), $legacy_table_content );
		
		$table_content = $this->order_table->get_all( false );
		$this->assertEquals( array( $original_table_content[0] ), $table_content );
	}

	public function testMigrateNextRow_selectData_DataIsRetrieved() {
		// Setup.
		$original_table_content = $this->order_table->get_all( false );

		$this->repository->prepare_tables_for_migration();
		$this->repository->migrate_next_row();

		$filter_legacy_table = new QueryFilter();
		$filter_legacy_table->where( 'id', '=', (int) $original_table_content[1]['id'] );
		$filter_legacy_table->setLimit( 1 );
		
		$filter_table = new QueryFilter();
		$filter_table->where( 'id', '=', (int) $original_table_content[0]['id'] );
		$filter_table->setLimit( 1 );
		
		// Execute.
		$entity_in_legacy_table = $this->repository->select( $filter_legacy_table )[0];
		$entity_in_table        = $this->repository->select( $filter_table )[0];

		// Assert.
		$row_in_legacy_table = $this->order_table->get_all( true )[0];
		$this->assertEquals( $row_in_legacy_table, $this->entity_to_row( $entity_in_legacy_table ) );
		$row_in_table = $this->order_table->get_all( false )[0];
		$this->assertEquals( $row_in_table, $this->entity_to_row( $entity_in_table ) );
	}

	/**
	 * Transforms an Entity object into a row array suitable for database storage.
	 * 
	 * @param Entity $entity The Entity object to transform.
	 * @return array The row array representation of the Entity.
	 */
	private function entity_to_row( Entity $entity ) {
		$indexes      = IndexHelper::transformFieldsToIndexes( $entity );
		$storage_item = array(
			'id'      => $entity->getId() ? $entity->getId() : null,
			'type'    => $entity->getConfig()->getType(),
			'index_1' => null,
			'index_2' => null,
			'index_3' => null,
			'index_4' => null,
			'index_5' => null,
			'index_6' => null,
			'index_7' => null,
			'data'    => \wp_json_encode( $entity->toArray() ),
		);

		foreach ( $indexes as $index => $value ) {
			$storage_item[ 'index_' . $index ] = $value;
		}

		return $storage_item;
	}
}
