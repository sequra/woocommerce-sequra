<?php
/**
 * Assets Controller interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers
 */

namespace SeQura\WC\Controllers;

/**
 * Define the assets related functionality
 */
interface Interface_Assets_Controller {

	/**
	 * Enqueue styles and scripts in WP-Admin
	 */
	public function enqueue_admin();

	/**
	 * Enqueue styles and scripts in Front-End
	 */
	public function enqueue_front();
}
