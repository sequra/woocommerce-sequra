<?php
/**
 * Settings Controller interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers
 */

namespace SeQura\WC\Controllers;

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
	 * Removes the WP footer message
	 */
	public function remove_footer_admin( string $text ): string;
}
