<?php
/**
 * Define methods for indexing data from the repository.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Repositories
 */

namespace SeQura\WC\Repositories;

use SeQura\WC\Dto\Table_Index;

/**
 * Define methods for indexing data from the repository.
 */
interface Interface_Indexable_Repository {

	/**
	 * Get the index name for the type_index_1 index.
	 * 
	 * @return Table_Index
	 */
	public function get_index_type_index_1();

	/**
	 * Get the index name for the type_index_2 index.
	 * 
	 * @return Table_Index
	 */
	public function get_index_type_index_2();

	/**
	 * Get the index name for the type_index_3 index.
	 * 
	 * @return Table_Index
	 */
	public function get_index_type_index_3();

	/**
	 * Get the index name for the index_3 index.
	 * 
	 * @return Table_Index
	 */
	public function get_index_index_3();

	/**
	 * Add an index to the table.
	 * 
	 * @param Table_Index $index The index.
	 */
	public function add_index( $index );

	/**
	 * Check if the index exists.
	 * 
	 * @param Table_Index $index The index to check.
	 * @return bool True if the index exists, false otherwise.
	 */
	public function does_index_exists( $index );

	/**
	 * Check if the table is currently busy and cannot be indexed.
	 * 
	 * @return bool
	 */
	public function is_busy();
}
