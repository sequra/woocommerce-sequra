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
	 * 
	 * @return void
	 */
	public function register_page();

	/**
	 * Render the settings page.
	 * 
	 * @return void
	 */
	public function render_page();

	/**
	 * Add action links to the plugin settings page.
	 *
	 * @param string[] $actions The actions.
	 * @param string   $plugin_file The plugin file.
	 * @param string   $plugin_data The plugin data.
	 * @param string   $context The context.
	 * @return string[]
	 */
	public function add_action_link( $actions, $plugin_file, $plugin_data, $context );

	/**
	 * Get the settings page URL.
	 * 
	 * @param string|null $url The URL.
	 * @return string
	 */
	public function get_settings_page_url( $url = null );

	/**
	 * Removes the WP footer message
	 * 
	 * @param string $text The footer text.
	 * @return string
	 */
	public function remove_footer_admin( $text );

	/**
	 * Show row meta on the plugin screen.
	 *
	 * @param array $links Plugin Row Meta.
	 * @param string $file  Plugin Base file.
	 * @return string[]
	 */
	public function add_plugin_row_meta( $links, $file );
}
