<?php
/**
 * The core plugin class.
 *
 * @package    Sequra/WC
 */

namespace Sequra\WC;

use Sequra\WC\Controllers\Interface_I18n_Controller;
use Sequra\WC\Controllers\Interface_Assets_Controller;

/**
 * The core plugin class.
 */
class Plugin {

	/**
	 * The plugin data.
	 * 
	 * @var array
	 */
	private $data;

	/**
	 * The plugin base name.
	 * 
	 * @var string
	 */
	private $base_name;

	/**
	 * Construct the plugin. Bind hooks with controllers.
	 *
	 * @param array                       $data            The plugin data.
	 * @param string                      $base_name       The plugin base name.
	 * @param Interface_I18n_Controller   $i18n_controller I18n controller.
	 * @param Interface_Assets_Controller $assets_controller Assets controller.
	 */
	public function __construct(
		$data,
		$base_name,
		Interface_I18n_Controller $i18n_controller,
		Interface_Assets_Controller $assets_controller
	) {
		$this->data      = $data;
		$this->base_name = $base_name;

		// I18n.
		add_action( 'plugins_loaded', array( $i18n_controller, 'load_text_domain' ) );

		// Assets hooks.
		add_action( 'admin_enqueue_scripts', array( $assets_controller, 'enqueue_admin' ) );
		add_action( 'wp_enqueue_scripts', array( $assets_controller, 'enqueue_front' ) );
	}

	/**
	 * Handle activation of the plugin.
	 */
	public function activate() {
		if ( version_compare( PHP_VERSION, $this->data['RequiresPHP'], '<' ) ) {
			deactivate_plugins( $this->base_name );
			wp_die( esc_html( 'This plugin requires PHP ' . $this->data['RequiresPHP'] . ' or greater.' ) );
		}

		global $wp_version;
		if ( version_compare( $wp_version, $this->data['RequiresWP'], '<' ) ) {
			deactivate_plugins( $this->base_name );
			wp_die( esc_html( 'This plugin requires WordPress ' . $this->data['RequiresWP'] . ' or greater.' ) );
		}

		// TODO: Do something on activation.
	}

	/**
	 * Handle deactivation of the plugin.
	 */
	public function deactivate() {
		// TODO: Do something on deactivation.
	}
}
