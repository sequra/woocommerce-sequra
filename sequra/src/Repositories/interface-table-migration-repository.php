<?php
/**
 * Define methods migrate data between tables without downtime.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Repositories
 */

namespace SeQura\WC\Repositories;

use SeQura\WC\Dto\Table_Index;

/**
 * Define methods migrate data between tables without downtime.
 */
interface Interface_Table_Migration_Repository {

	/**
	 * Add an index to the table. Use with caution, only if the amount of data is small.
	 * 
	 * @param Table_Index $index The index.
	 * @return bool True if the index was added or already exists, false otherwise.
	 */
	public function add_index( $index );

	/**
	 * Check if the index exists.
	 * 
	 * @param Table_Index $index The index to check.
	 * @return bool True if the index exists, false otherwise.
	 */
	public function index_exists( $index );

	/**
	 * Get a list of indexes that are required for the table.
	 * 
	 * @return Table_Index[] The list of indexes.
	 */
	public function get_required_indexes();

	/**
	 * Get the name that is set to the original table during the migration.
	 * 
	 * @return string The name of the old table.
	 */
	public function get_legacy_table_name();

	/**
	 * Check if the migration process is complete.
	 * 
	 * @return bool True if the migration process is complete, false otherwise.
	 */
	public function is_migration_complete();

	/**
	 * Execute the migration process.
	 */
	public function migrate_next_row();

	/**
	 * Make sure that the required tables for the migration are created.
	 * 
	 * @return bool
	 */
	public function prepare_tables_for_migration();

	/**
	 * Evaluates if the legacy table should be removed and if so, removes it.
	 * 
	 * @return bool True if the legacy table was removed or did not exist, false otherwise.
	 */
	public function maybe_remove_legacy_table();

	/**
	 * Create the table if it doesn't exist.
	 * 
	 * @return bool True if the table was created successfully, false otherwise.
	 */
	public function create_table();

	/**
	 * Check if table exists in the database.
	 * 
	 * @param boolean $legacy If true, check for legacy table.
	 */
	public function table_exists( $legacy = false ): bool;

	/**
	 * Returns full table name.
	 */
	public function get_table_name(): string;
}
