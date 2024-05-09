<?php
/**
 * I18n Controller
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
class I18n_Controller implements Interface_I18n_Controller {

	/**
	 * The relative path to translation files from wp-content/plugins
	 * 
	 * @var string
	 */
	private $domain_path;

	/**
	 * The text domain of the plugin.
	 * 
	 * @var string
	 */
	private $text_domain;

	/**
	 * Constructor
	 * 
	 * @param string $text_domain The text domain of the plugin.
	 * @param string $domain_path The relative path to translation files from wp-content/plugins.
	 */
	public function __construct( $text_domain, $domain_path ) {
		$this->text_domain = $text_domain;
		$this->domain_path = trailingslashit( $domain_path );
	}

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_text_domain() {
		return load_plugin_textdomain( $this->text_domain, false, $this->domain_path );
	}
}
