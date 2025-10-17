<?php
/**
 * Settings Service Interface
 * 
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Service;

/**
 * Settings Service Interface
 */
interface Interface_Settings_Service {

	/**
	 * Check if the current page is the settings page.
	 */
	public function is_settings_page(): bool;

	/**
	 * Get the configuration page slug.
	 */
	public function get_page(): string;

	/**
	 * Get the configuration page parent slug.
	 */
	public function get_parent_page(): string;
}
