<?php
/**
 * I18n interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers
 */

namespace SeQura\WC\Controllers\Hooks\I18n;

/**
 * Define the internationalization functionality
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 */
interface Interface_I18n_Controller {

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_text_domain(): void;
}
