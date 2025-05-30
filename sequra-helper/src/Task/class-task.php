<?php
/**
 * Task class
 * 
 * @package SeQura/Helper
 */

namespace SeQura\Helper\Task;

// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, Generic.CodeAnalysis.UnusedFunctionParameter.Found

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
	 * Get the table name for the seQura queue table
	 */
	protected function get_sequra_queue_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'sequra_queue';
	}

	/**
	 * Drop tables in the database
	 * 
	 * @throws \Exception If the task fails
	 */
	protected function drop_tables_in_database( $tables = array() ): void {
		if ( empty( $tables ) ) {
			return;
		}

		global $wpdb;
		
		foreach ( $tables as $table ) {
			if ( ! $wpdb->query( "DROP TABLE IF EXISTS $table" ) ) {
				throw new \Exception( "Failed to drop table $table", 500 ); //phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			}
		}
	}

	/**
	 * Recreate tables in the database
	 */
	protected function recreate_entity_table_in_database(): void {
		$table_name = $this->get_sequra_entity_table_name();
		$this->drop_tables_in_database( array( $table_name ) );

		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		
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

		// Insert version to prevent migration from running.
		$wpdb->insert(
			$table_name,
			array(
				'id'      => 7,
				'type'    => 'Configuration',
				'index_1' => 'version',
				'index_2' => '',
				'index_3' => null,
				'index_4' => null,
				'index_5' => null,
				'index_6' => null,
				'index_7' => null,
				'data'    => '{"class_name":"SeQura\\\\Core\\\\Infrastructure\\\\Configuration\\\\ConfigEntity","id":null,"name":"version","value":"3.0.0","context":""}',
			)
		);
	}

	/**
	 * Response with an error message
	 */
	public function http_error_response( string $message, int $error_code ): void {
		header( 'Content-Type: application/json' );
		wp_send_json_error( array( 'message' => $message ), $error_code );
	}

	/**
	 * Response with an error message
	 */
	public function http_success_response(): void {
		header( 'Content-Type: application/json' );
		wp_send_json_success( array( 'message' => 'Task executed' ) );
	}
}
