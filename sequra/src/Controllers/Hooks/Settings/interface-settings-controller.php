<?php
/**
 * Settings Controller interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers
 */

namespace SeQura\WC\Controllers\Hooks\Settings;

/**
 * Settings Controller interface
 */
interface Interface_Settings_Controller {

	/**
	 * Register the settings page.
	 */
	public function register_page(): void;

	/**
	 * Render the settings page.
	 */
	public function render_page(): void;

	/**
	 * Add action links to the plugin settings page.
	 *
	 * @param string[] $actions The actions.
	 * @param string   $plugin_file The plugin file.
	 * @param string   $plugin_data The plugin data.
	 * @param string   $context The context.
	 * @return string[]
	 */
	public function add_action_link( $actions, $plugin_file, $plugin_data, $context ): array;

	/**
	 * Get the settings page URL.
	 */
	public function get_settings_page_url( ?string $url = null ): string;

	/**
	 * Removes the WP footer message
	 */
	public function remove_footer_admin( string $text ): string;

	/**
	 * Show row meta on the plugin screen.
	 *
	 * @param array $links Plugin Row Meta.
	 * @param string $file  Plugin Base file.
	 */
	public function add_plugin_row_meta( $links, $file ): array;
}
