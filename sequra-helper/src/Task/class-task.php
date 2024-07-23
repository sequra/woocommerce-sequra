<?php
/**
 * Task class
 * 
 * @package SeQura/Helper
 */

namespace SeQura\Helper\Task;

// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/**
 * Task class
 */
class Task {

	/**
	 * Execute the task
	 * 
	 * @throws \Exception If the task fails
	 */
	public function execute( array $args = array() ): void {
		throw new \Exception( 'Task not implemented', 500 );
	}

	/**
	 * Get the table name for the seQura order table
	 */
	protected function get_sequra_order_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'sequra_order';
	}

	/**
	 * Get the table name for the seQura entity table
	 */
	protected function get_sequra_entity_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'sequra_entity';
	}

	/**
	 * Recreate tables in the database
	 */
	protected function recreate_tables_in_database(): void {
		global $wpdb;
		$table_name      = $this->get_sequra_entity_table_name();
		$charset_collate = $wpdb->collate;
		
		$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
		
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta(
			"CREATE TABLE IF NOT EXISTS $table_name (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `type` VARCHAR(255),
            `index_1` VARCHAR(127),
            `index_2` VARCHAR(127),
            `index_3` VARCHAR(127),
            `index_4` VARCHAR(127),
            `index_5` VARCHAR(127),
            `index_6` VARCHAR(127),
            `index_7` VARCHAR(127),
            `data` LONGTEXT,
            PRIMARY KEY  (id)
            ) $charset_collate" 
		);
	}
}
