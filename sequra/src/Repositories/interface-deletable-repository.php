<?php
/**
 * Define methods for delete data from the repository.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Repositories
 */

namespace SeQura\WC\Repositories;

/**
 * Define methods for delete data from the repository.
 */
interface Interface_Deletable_Repository {

	/**
	 * Delete all the entities.
	 */
	public function delete_all(): bool;

	/**
	 * Remove entities that are older than a certain date or that are invalid.
	 * This performs a cleanup of the repository data.
	 */
	public function delete_old_and_invalid();
}
