<?php
/**
 * Repository for generic entities.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Repositories
 */

namespace SeQura\WC\Repositories;

/**
 * Repository for generic entities.
 */
class Entity_Repository extends Repository {

	/**
	 * Returns unprefixed table name.
	 */
	protected function get_unprefixed_table_name(): string {
		return 'sequra_entity';
	}
}
