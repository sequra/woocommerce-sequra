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

	public function testSelect_MigrationInCourse_DataIsRetrievedFromTheCorrectTable() {
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
	 * @dataProvider dataProvider_Delete_MigrationInCourse
	 */
	public function testDelete_MigrationInCourse_DataIsDeletedFromBothTables( $id ) {
		// Setup.
		$original_content = $this->order_table->get_all( false );
		$migrated_content = array();
		$legacy_content   = array();
		
		foreach ( $original_content as $i => $value ) {
			if ( $id !== (int) $value['id'] ) {
				if ( 0 === $i ) {
					$migrated_content[] = $value;
				} else {
					$legacy_content[] = $value;
				}
			}
		}

		$this->repository->prepare_tables_for_migration();
		$this->repository->migrate_next_row();

		$entity = new SeQuraOrder();
		$entity->setId( $id );
		
		// Execute.
		$result = $this->repository->delete( $entity );

		// Assert.
		$this->assertTrue( $result );
		$legacy_table_content = $this->order_table->get_all( true );
		
		$this->assertEquals( $legacy_content, $legacy_table_content );
		$table_content = $this->order_table->get_all( false );
		$this->assertEquals( $migrated_content, $table_content );
	}

	public function testInsert_MigrationInCourse_DataIsInsertedInTable() {
		// Setup.
		$this->repository->prepare_tables_for_migration();
		$this->repository->migrate_next_row();

		$entity        = $this->entity_instance();
		$entity_as_row = $this->entity_to_row( $entity );
		$expected_id   = 4;
		
		// Execute.
		$result = $this->repository->save( $entity );

		// Assert.
		$this->assertEquals( $expected_id, $result );
		$content             = $this->order_table->get_all( false );
		$row                 = end( $content );
		$entity_as_row['id'] = (string) $expected_id; // Ensure ID is a string as expected in the database.
		$this->assertEquals( $row, $entity_as_row );
	}

	public function testUpdate_MigrationInCourse_DataIsUpdatedInTable() {
		// Setup.
		$this->repository->prepare_tables_for_migration();
		$this->repository->migrate_next_row();

		$expected_id   = 2;
		$entity        = $this->entity_instance( $expected_id );
		$entity_as_row = $this->entity_to_row( $entity );
		
		// Execute.
		$result = $this->repository->save( $entity );

		// Assert.
		$this->assertEquals( $expected_id, $result );
		$content = $this->order_table->get_all( false );
		$row     = end( $content );
		$this->assertEquals( $row, $entity_as_row );
	}

	public function testCount_MigrationInCourse_ValueIsEqualsToSumOfBothTableTotals() {
		// Setup.
		$this->repository->prepare_tables_for_migration();
		$this->repository->migrate_next_row();
		
		// Execute.
		$result = $this->repository->count();

		// Assert.
		$this->assertEquals( 3, $result );
	}

	/**
	 * Data provider for testDelete_MigrationInCourse_DataIsDeletedFromBothTables.
	 * 
	 * @return array
	 */
	public function dataProvider_Delete_MigrationInCourse() {
		return array(
			array( 1 ), // Delete first row.
			array( 2 ), // Delete second row.
		);
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
			'id'      => $entity->getId() ? (string) $entity->getId() : null,
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

	/**
	 * Creates an instance of SeQuraOrder with sample data.
	 * 
	 * @param int|null $id The ID of the order. If null, a new ID will be generated.
	 * @return SeQuraOrder An instance of SeQuraOrder with sample data.
	 */
	private function entity_instance( $id = null ) {
		return SeQuraOrder::fromArray(
			array(
				'id'                 => $id,
				'reference'          => 'test_reference',
				'cartId'             => 'test_cart_id',
				'orderRef1'          => 'test_order_ref_1',
				'merchant'           => array(
					'id' => 'test_merchant_id',
				),
				'merchantReference'  => array(
					'orderRef1' => 'test_merchant_order_ref_1',
					'orderRef2' => 'test_merchant_order_ref_2',
				),
				'merchant_reference' => array(
					'orderRef1' => 'test_merchant_order_ref_1',
					'orderRef2' => 'test_merchant_order_ref_2',
				),
				'shippedCart'        => array(
					'currency'          => 'EUR',
					'gift'              => false,
					'orderTotalWithTax' => 10000,
					'cartRef'           => 'test_cart_ref',
					'createdAt'         => '2023-10-01T12:00:00',
					'updatedAt'         => '2023-10-01T12:00:00',
					'items'             => array(),
				),
				'shipped_cart'       => array(
					'currency'          => 'EUR',
					'gift'              => false,
					'orderTotalWithTax' => 10000,
					'cartRef'           => 'test_cart_ref',
					'createdAt'         => '2023-10-01T12:00:00',
					'updatedAt'         => '2023-10-01T12:00:00',
					'items'             => array(),
				),
				'unshippedCart'      => array(),
				'unshipped_cart'     => array(),
				'state'              => 'test_state',
				'deliveryMethod'     => array(
					'name'         => 'Test Delivery Method',
					'days'         => 3,
					'provider'     => 'Test Provider',
					'homeDelivery' => null,
				),
				'delivery_method'    => array(
					'name'         => 'Test Delivery Method',
					'days'         => 3,
					'provider'     => 'Test Provider',
					'homeDelivery' => null,
				),
				'deliveryAddress'    => array(
					'givenNames'   => 'Mengano',
					'surnames'     => 'De Tal',
					'company'      => 'Test Company',
					'addressLine1' => 'Test Address Line 1',
					'addressLine2' => 'Test Address Line 2',
					'postalCode'   => '28001',
					'city'         => 'Test City',
					'countryCode'  => 'ES',
					'phone'        => '123456789',
					'mobilePhone'  => '987654321',
					'state'        => 'Test State',
					'extra'        => 'Test Extra',
					'vatNumber'    => 'ES12345678A',
				),
				'delivery_address'   => array(
					'givenNames'   => 'Mengano',
					'surnames'     => 'De Tal',
					'company'      => 'Test Company',
					'addressLine1' => 'Test Address Line 1',
					'addressLine2' => 'Test Address Line 2',
					'postalCode'   => '28001',
					'city'         => 'Test City',
					'countryCode'  => 'ES',
					'phone'        => '123456789',
					'mobilePhone'  => '987654321',
					'state'        => 'Test State',
					'extra'        => 'Test Extra',
					'vatNumber'    => 'ES12345678A',
				),
				'invoiceAddress'     => array(
					'givenNames'   => 'Mengano',
					'surnames'     => 'De Tal',
					'company'      => 'Test Company',
					'addressLine1' => 'Test Address Line 1',
					'addressLine2' => 'Test Address Line 2',
					'postalCode'   => '28001',
					'city'         => 'Test City',
					'countryCode'  => 'ES',
					'phone'        => '123456789',
					'mobilePhone'  => '987654321',
					'state'        => 'Test State',
					'extra'        => 'Test Extra',
					'vatNumber'    => 'ES12345678A',
				),
				'invoice_address'    => array(
					'givenNames'   => 'Mengano',
					'surnames'     => 'De Tal',
					'company'      => 'Test Company',
					'addressLine1' => 'Test Address Line 1',
					'addressLine2' => 'Test Address Line 2',
					'postalCode'   => '28001',
					'city'         => 'Test City',
					'countryCode'  => 'ES',
					'phone'        => '123456789',
					'mobilePhone'  => '987654321',
					'state'        => 'Test State',
					'extra'        => 'Test Extra',
					'vatNumber'    => 'ES12345678A',
				),
				'customer'           => array(
					'given_names'     => 'Mengano',
					'surnames'        => 'De Tal',
					'email'           => 'test@email.com',
					'logged_in'       => true,
					'language_code'   => 'en',
					'ip_number'       => '127.0.0.1',
					'user_agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3',
					'ref'             => 1,
					'company'         => 'Test Company',
					'vat_number'      => 'ES12345678A',
					'previous_orders' => array(),
				),
				'platform'           => array(
					'name'           => 'Test Platform',
					'version'        => '1.0.0',
					'plugin_version' => '2.0.0',
					'uname'          => 'Linux',
					'db_name'        => 'mysql',
					'db_version'     => '5.7.0',
					'php_version'    => '7.4.0',
				),
				'gui'                => array(
					'layout' => 'desktop',
				),
				'paymentMethod'      => null,
				'payment_method'     => null,
			)
		);
	}
}
