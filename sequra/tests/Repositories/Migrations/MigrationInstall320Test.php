<?php
/**
 * Tests for the Migration_Install_320 class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Repositories\Migrations;

use SeQura\WC\Repositories\Migrations\Migration_Install_320;
use SeQura\WC\Dto\Table_Index;
use SeQura\WC\Repositories\Entity_Repository;
use SeQura\WC\Repositories\Queue_Item_Repository;
use WP_UnitTestCase;

class MigrationInstall320Test extends WP_UnitTestCase {

	private $migration;
	/** @var \SeQura\WC\Repositories\Entity_Repository&\PHPUnit\Framework\MockObject\MockObject */
	private $entity_repository;
	/** @var \SeQura\WC\Repositories\Queue_Item_Repository&\PHPUnit\Framework\MockObject\MockObject */
	private $queue_repository;
	private $hook_name;
	
	public function set_up() {
		$this->hook_name = 'migration_install_320_test_hook';
		/** @var \wpdb&\PHPUnit\Framework\MockObject\MockObject */
		$_wpdb                   = $this->createMock( \wpdb::class );
		$this->entity_repository = $this->createMock( Entity_Repository::class );
		$this->queue_repository  = $this->createMock( Queue_Item_Repository::class );

		$this->migration = new Migration_Install_320(
			$_wpdb, 
			$this->hook_name, 
			$this->entity_repository, 
			$this->queue_repository
		);
	}

	public function tear_down() {
		\wp_clear_scheduled_hook( $this->hook_name, array( $this->hook_name ) );
	}

	/**
	 * @dataProvider dataProvider_Run_cannotAddIndex
	 */
	public function testRun_cannotAddIndex_ExceptionIsThrownAndJobIsNotScheduled( $ok_repos, $ko_repo ) {
		foreach ( $ok_repos as $i => $attr ) {
			$index = new Table_Index( 'migration_install_320_test_index_ok_' . $i, array() );

			$repo = $this->$attr;
			$repo->expects( $this->once() )
				->method( 'add_index' )
				->with( $index )
				->willReturn( true );

			$repo->expects( $this->once() )
				->method( 'get_required_indexes' )
				->willReturn( array( $index ) );
		}
		
		$index      = new Table_Index( 'migration_install_320_test_index_ko', array() );
		$table_name = 'migration_install_320_test_table_ko';
		$message    = 'Failed to add index ' . $index->name . ' to table ' . $table_name;
		$repo       = $this->$ko_repo;
		$repo->expects( $this->once() )
			->method( 'add_index' )
			->with( $index )
			->willReturn( false );

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( $message );
		
		$repo->expects( $this->once() )
			->method( 'get_required_indexes' )
			->willReturn( array( $index ) );
		
		$repo->expects( $this->once() )
			->method( 'get_table_name' )
			->willReturn( $table_name );

		$this->migration->run();

		$this->assertFalse( wp_next_scheduled( $this->hook_name, array( $this->hook_name ) ) );
	}

	public function dataProvider_Run_cannotAddIndex() {
		return array(
			array( array( 'entity_repository' ), 'queue_repository' ),
			array( array(), 'entity_repository' ),
		);
	}

	public function testRun_happyPath_IndexesExistsAndJobIsScheduled() {
		foreach ( array( $this->entity_repository, $this->queue_repository ) as $i => $repo ) {
			$index_name = 'migration_install_320_test_index_ok_' . $i;

			$repo->expects( $this->once() )
				->method( 'add_index' )
				->with( $index_name )
				->willReturn( true );

			$repo->expects( $this->once() )
				->method( 'get_required_indexes' )
				->willReturn( array( $index_name ) );
		}

		$this->migration->run();
		
		$this->assertIsInt( wp_next_scheduled( $this->hook_name, array( $this->hook_name ) ) );
	}
}
