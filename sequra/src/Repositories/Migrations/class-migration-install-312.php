<?php
/**
 * Post install migration for version 3.1.2 of the plugin.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Repositories
 */

namespace SeQura\WC\Repositories\Migrations;

use Exception;
use Throwable;
use SeQura\WC\Core\Extension\Infrastructure\Configuration\Configuration;
use SeQura\WC\Repositories\Repository;

/**
 * Post install migration for version 3.1.2 of the plugin.
 */
class Migration_Install_312 extends Migration {

	/**
	 * Hook name.
	 * 
	 * @var string
	 */
	private $hook_name;

	/**
	 * Entity repository.
	 * 
	 * @var Repository
	 */
	private $entity_repository;

	/**
	 * Queue repository.
	 * 
	 * @var Repository
	 */
	private $queue_repository;

	/**
	 * Constructor
	 *
	 * @param \wpdb $wpdb Database instance.
	 * @param string $hook_name Hook name.
	 */
	public function __construct( 
		\wpdb $wpdb, 
		Configuration $configuration,
		$hook_name,
		Repository $entity_repository,
		Repository $queue_repository
	 ) {
		parent::__construct( $wpdb, $configuration );
		$this->hook_name     = $hook_name;
		$this->entity_repository = $entity_repository;
		$this->queue_repository   = $queue_repository;
	}

	/**
	 * Get the plugin version when the changes were made.
	 */
	public function get_version(): string {
		return '3.1.2';
	}

	/**
	 * Run the migration.
	 *
	 * @throws Throwable|Critical_Migration_Exception
	 */
	public function run(): void {
		// Index tables with almost no data or no data at all (sequra_entity, sequra_queue).
		$repos = array(
			$this->entity_repository,
			$this->queue_repository,
		);
		foreach ( $repos as $repo ) {
			foreach ( $repo->get_required_indexes() as $index ) {
				if(!$repo->add_index( $index )){
					throw new Exception( 'Failed to add index ' . $index->name . ' to table ' . $repo->get_table_name() );
				}
			}
		}
		
		// Schedule indexing of the sequra_order table.
		$args = array( $this->hook_name );
		if ( ! \wp_next_scheduled( $this->hook_name, $args ) ) {
			\wp_schedule_event( time(), 'hourly', $this->hook_name, $args );
		}
	}
}
