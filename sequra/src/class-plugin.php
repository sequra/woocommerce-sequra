<?php
/**
 * The core plugin class.
 *
 * @package    SeQura/WC
 */

namespace SeQura\WC;

use SeQura\WC\Controllers\Interface_I18n_Controller;
use SeQura\WC\Controllers\Interface_Assets_Controller;
use SeQura\WC\Controllers\Interface_Settings_Controller;
use SeQura\WC\Controllers\Rest\REST_Controller;

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
	 * @param Interface_Bootstrap   $bootstrap SeQura Integration Core bootstrap.
	 * @param Interface_I18n_Controller   $i18n_controller I18n controller.
	 * @param Interface_Assets_Controller $assets_controller Assets controller.
	 * @param Interface_Settings_Controller $settings_controller Settings controller.
	 * @param WP_REST_Controller          $rest_settings_controller REST Settings controller.
	 * @param WP_REST_Controller          $rest_onboarding_controller REST Onboarding controller.
	 * @param WP_REST_Controller          $rest_payment_controller REST Payment controller.
	 */
	public function __construct(
		$data,
		$base_name,
		Interface_Bootstrap $bootstrap,
		Interface_I18n_Controller $i18n_controller,
		Interface_Assets_Controller $assets_controller,
		Interface_Settings_Controller $settings_controller,
		REST_Controller $rest_settings_controller,
		REST_Controller $rest_onboarding_controller,
		REST_Controller $rest_payment_controller
	) {
		$this->data      = $data;
		$this->base_name = $base_name;

		// I18n.
		add_action( 'plugins_loaded', array( $i18n_controller, 'load_text_domain' ) );

		// Assets hooks.
		add_action( 'admin_enqueue_scripts', array( $assets_controller, 'enqueue_admin' ) );
		add_action( 'wp_enqueue_scripts', array( $assets_controller, 'enqueue_front' ) );

		// Settings hooks.
		add_action( 'admin_menu', array( $settings_controller, 'register_page' ) );
		add_filter( "plugin_action_links_{$base_name}", array( $settings_controller, 'add_action_link' ), 10, 4 );
		add_filter( 'admin_footer_text', array( $settings_controller, 'remove_footer_admin' ) );


		// REST Controllers.
		add_action( 'rest_api_init', array( $rest_settings_controller, 'register_routes' ), 11, 1 );
		add_action( 'rest_api_init', array( $rest_onboarding_controller, 'register_routes' ) );
		add_action( 'rest_api_init', array( $rest_payment_controller, 'register_routes' ) );

		// TODO: Maybe this should be moved to plugin_loaded action.
		$bootstrap->do_init();
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

		if ( ! defined( 'WC_VERSION' ) || version_compare( WC_VERSION, $this->data['RequiresWC'], '<' ) ) {
			deactivate_plugins( $this->base_name );
			wp_die( esc_html( 'This plugin requires WooCommerce ' . $this->data['RequiresWC'] . ' or greater.' ) );
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
