<?php
/**
 * Implementation for the settings controller.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers
 */

namespace SeQura\WC\Controllers\Hooks\Settings;

use SeQura\WC\Controllers\Controller;
use SeQura\WC\Core\Extension\Infrastructure\Configuration\Configuration;
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
	 * Plugin basename.
	 */
	private $plugin_basename;

	/**
	 * Constructor.
	 */
	public function __construct( 
		string $templates_path, 
		Configuration $configuration, 
		Interface_Logger_Service $logger,
		string $plugin_basename
	) {
		parent::__construct( $logger, $templates_path );
		$this->configuration   = $configuration;
		$this->plugin_basename = $plugin_basename;
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
	 * Get the settings page URL.
	 */
	public function get_settings_page_url( ?string $url = null ): string {
		return admin_url( 'admin.php?page=' . $this->configuration->get_page() );
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
			'href' => $this->get_settings_page_url(),
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

	/**
	 * Show row meta on the plugin screen.
	 *
	 * @param array $links Plugin Row Meta.
	 * @param string $file  Plugin Base file.
	 */
	public function add_plugin_row_meta( $links, $file ): array {
		if ( $this->plugin_basename === $file ) {
			$row_meta = array(
				'docs'    => sprintf(
					'<a href="%s" aria-label="%s" target="_blank">%s</a>',
					esc_url(
						/**
						 * Filters the URL of the plugin documentation.
						 *
						 * @since 2.0.0
						 */
						apply_filters( 'sequrapayment_docs_url', 'https://sequra.atlassian.net/wiki/spaces/DOC/pages/2247524378/WOOCOMMERCE' )
					),
					esc_attr__( 'View WooCommerce documentation', 'sequra' ),
					esc_html__( 'Docs', 'woocommerce' )
				),
				'apidocs' => sprintf(
					'<a href="%s" aria-label="%s" target="_blank">%s</a>',
					esc_url(
						/**
						 * Filters the URL of the plugin API documentation.
						 *
						 * @since 2.0.0
						 */
						apply_filters( 'sequrapayment_apidocs_url', 'https://docs.sequrapi.com/' )
					),
					esc_attr__( 'View WooCommerce API docs', 'sequra' ),
					esc_html__( 'API docs', 'sequra' )
				),
				'support' => sprintf(
					'<a href="%s" aria-label="%s" target="_blank">%s</a>',
					esc_url(
						/**
						 * Filters the URL of the plugin support.
						 *
						 * @since 2.0.0
						 */
						apply_filters( 'sequrapayment_support_url', 'https://sequra.atlassian.net/servicedesk/customer/portal/5/group/-1' )
					),
					esc_attr__( 'Support', 'sequra' ),
					esc_html__( 'Support', 'sequra' )
				),
			);

			return array_merge( $links, $row_meta );
		}

		return (array) $links;
	}
}
