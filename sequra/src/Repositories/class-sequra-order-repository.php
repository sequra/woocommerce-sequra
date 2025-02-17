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

	/**
	 * Remove entities that are older than a certain date or that are invalid.
	 * This performs a cleanup of the repository data.
	 */
	public function delete_old_and_invalid() {
		$this->db->query(
			"DELETE FROM {$this->get_table_name()} 
		WHERE (`index_3` IS NULL OR `index_3` = '') 
		AND STR_TO_DATE(LEFT(JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.unshipped_cart.updated_at')), 19), '%Y-%m-%dT%H:%i:%s') <= CURDATE() - INTERVAL 1 DAY" 
		);
	}
}
