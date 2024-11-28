<?php
/**
 * Assets Controller interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers
 */

namespace SeQura\WC\Controllers\Hooks\Asset;

/**
 * Define the assets related functionality
 */
interface Interface_Assets_Controller {

	/**
	 * Enqueue styles and scripts in WP-Admin
	 * 
	 * @return void
	 */
	public function enqueue_admin();

	/**
	 * Enqueue styles and scripts in Front-End
	 * 
	 * @return void
	 */
	public function enqueue_front();
}
