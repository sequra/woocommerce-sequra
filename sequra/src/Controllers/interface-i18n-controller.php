<?php
/**
 * I18n interface
 *
 * @package    Sequra/WC
 * @subpackage Sequra/WC/Controllers
 */

namespace Sequra\WC\Controllers;

/**
 * Define the internationalization functionality
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 */
interface Interface_I18n_Controller {

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_text_domain();
}
