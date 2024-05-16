<?php
/**
 * Implementation for the settings controller.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers
 */

namespace SeQura\WC\Controllers;

use SeQura\Core\BusinessLogic\AdminAPI\AdminAPI;
use SeQura\Core\Infrastructure\Configuration\Configuration;
use SeQura\Core\Infrastructure\ServiceRegister;
use SeQura\WC\Services\Interface_Settings;

/**
 * Implementation for the settings controller.
 */
class Settings_Controller implements Interface_Settings_Controller {

	private const MENU_SLUG   = 'sequra';
	private const PARENT_SLUG = 'options-general.php';

	/**
	 * The templates path.
	 *
	 * @var string
	 */
	private $templates_path;

	/**
	 * The settings.
	 *
	 * @var Interface_Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param string $templates_path The templates path.
	 */
	public function __construct( string $templates_path, Interface_Settings $settings ) {
		$this->templates_path = $templates_path;
		$this->settings       = $settings;
	}

	/**
	 * Register the settings page.
	 */
	public function register_page(): void {

		\add_submenu_page(
			self::PARENT_SLUG,
			__( 'seQura', 'sequra' ),
			__( 'seQura', 'sequra' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_page' )
		);

		// Additionally remove WP version footer text if we are in the settings page.
		if ( $this->settings->is_settings_page() ) {
			remove_filter( 'update_footer', 'core_update_footer' );
		}
	}

	/**
	 * Render the settings page.
	 */
	public function render_page(): void {
		\wc_get_template( 'admin/settings_page.php', array(), '', $this->templates_path );
	}

	/**
	 * Add action links to the plugin settings page.
	 *
	 * @param string[] $actions The actions.
	 * @param string   $plugin_file The plugin file.
	 * @param string   $plugin_data The plugin data.
	 * @param string   $context The context.
	 * @return string[]
	 */
	public function add_action_link( $actions, $plugin_file, $plugin_data, $context ): array {
		$args = array(
			'href' => admin_url( self::PARENT_SLUG . '?page=' . self::MENU_SLUG ),
			'text' => esc_attr__( 'Settings', 'sequra' ),
		);
		\ob_start();
		\wc_get_template( 'admin/action_link.php', $args, '', $this->templates_path );
		$actions['settings'] = \ob_get_clean();
		return $actions;
	}

	/**
	 * Removes the WP footer message
	 */
	public function remove_footer_admin( $text ): string {
		if ( ! $this->settings->is_settings_page() ) {
			return $text;
		}
		return '';
	}
}
