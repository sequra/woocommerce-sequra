<?php
/**
 * Implementation for the settings controller.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers
 */

namespace SeQura\WC\Controllers\Hooks\Settings;

use SeQura\WC\Controllers\Controller;
use SeQura\WC\Services\Core\Configuration;
use SeQura\WC\Services\Interface_Logger_Service;

/**
 * Implementation for the settings controller.
 */
class Settings_Controller extends Controller implements Interface_Settings_Controller {

	/**
	 * Configuration service.
	 *
	 * @var Configuration
	 */
	private $configuration;

	/**
	 * Constructor.
	 */
	public function __construct( 
		string $templates_path, 
		Configuration $configuration, 
		Interface_Logger_Service $logger
	) {
		parent::__construct( $logger, $templates_path );
		$this->configuration = $configuration;
	}

	/**
	 * Register the settings page.
	 */
	public function register_page(): void {
		$this->logger->log_info( 'Hook executed', __FUNCTION__, __CLASS__ );
		add_submenu_page(
			$this->configuration->get_parent_page(),
			__( 'seQura', 'sequra' ),
			__( 'seQura', 'sequra' ),
			'manage_options',
			$this->configuration->get_page(),
			array( $this, 'render_page' )
		);

		// Additionally remove WP version footer text if we are in the settings page.
		if ( $this->configuration->is_settings_page() ) {
			remove_filter( 'update_footer', 'core_update_footer' );
		}
	}

	/**
	 * Render the settings page.
	 */
	public function render_page(): void {
		$this->logger->log_info( 'Callback executed', __FUNCTION__, __CLASS__ );
		wc_get_template( 'admin/settings_page.php', array(), '', $this->templates_path );
	}

	/**
	 * Add action links to the plugin settings page.
	 *
	 * @param string[] $actions The actions.
	 * @param string   $plugin_file The plugin file.
	 * @param string   $plugin_data The plugin data.
	 * @param string   $context The context.
	 * @return mixed[]
	 */
	public function add_action_link( $actions, $plugin_file, $plugin_data, $context ): array {
		$this->logger->log_info( 'Hook executed', __FUNCTION__, __CLASS__ );
		$args = array(
			'href' => admin_url( $this->configuration->get_parent_page() . '?page=' . $this->configuration->get_page() ),
			'text' => esc_attr__( 'Settings', 'sequra' ),
		);
		ob_start();
		wc_get_template( 'admin/action_link.php', $args, '', $this->templates_path );
		$actions['settings'] = ob_get_clean();
		return $actions;
	}

	/**
	 * Removes the WP footer message
	 */
	public function remove_footer_admin( string $text ): string {
		$this->logger->log_info( 'Hook executed', __FUNCTION__, __CLASS__ );
		if ( ! $this->configuration->is_settings_page() ) {
			return $text;
		}
		return '';
	}
}
