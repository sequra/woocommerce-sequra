<?php
/**
 * Settings
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Repositories
 */

namespace SeQura\WC\Repositories;

/**
 * Class Base_Repository
 */
class SeQura_Order_Repository extends Repository {

	/**
	 * Returns unprefixed table name.
	 */
	protected function get_unprefixed_table_name(): string {
		return 'sequra_order';
	}

	/**
	 * Get the index column name that stores the store ID.
	 * 
	 * @return string Index column name or empty string if not applicable.
	 */
	protected function get_store_id_index_column(): string {
		return '';
	}
}
