<?php
/**
 * Settings interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Repositories
 */

namespace SeQura\WC\Repositories;

/**
 * Settings interface
 */
interface Interface_Settings_Repo {

	/**
	 * Get general settings.
	 *
	 * @param int|null $blog_id The blog ID.
	 * 
	 * @return mixed
	 */
	public function get_general_settings( $blog_id = null );
}
