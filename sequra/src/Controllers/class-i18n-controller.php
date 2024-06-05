<?php
/**
 * I18n Controller
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers
 */

namespace SeQura\WC\Controllers;

use SeQura\WC\Services\Interface_Logger_Service;

/**
 * Define the internationalization functionality
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 */
class I18n_Controller extends Controller implements Interface_I18n_Controller {

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
	 * @param Interface_Logger_Service $logger The logger service.
	 */
	public function __construct( $text_domain, $domain_path, Interface_Logger_Service $logger ) {
		parent::__construct( $logger );
		$this->text_domain = $text_domain;
		$this->domain_path = trailingslashit( $domain_path );
	}

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_text_domain(): void {
		$this->logger->log_info( 'Hook executed', __FUNCTION__, __CLASS__ );
		load_plugin_textdomain( $this->text_domain, false, $this->domain_path );
	}
}
