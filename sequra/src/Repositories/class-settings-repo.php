<?php
/**
 * Settings
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Repositories
 */

namespace SeQura\WC\Repositories;

use SeQura\Core\BusinessLogic\AdminAPI\AdminAPI;

/**
 * Settings
 */
class Settings_Repo implements Interface_Settings_Repo {

	/**
	 * Get general settings.
	 *
	 * @param int|null $blog_id The blog ID.
	 * 
	 * @return mixed
	 */
	public function get_general_settings( $blog_id = null ) {
		if ( null === $blog_id ) {
			$blog_id = get_current_blog_id();
		}
		$data = AdminAPI::get()->generalSettings( $blog_id )->getGeneralSettings();
		return $data;
	}
}
