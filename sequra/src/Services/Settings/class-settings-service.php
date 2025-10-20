<?php
/**
 * Settings Service Class
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Service;

/**
 * Settings Service Class
 */
class Settings_Service implements Interface_Settings_Service {

	/**
	 * Check if the current page is the settings page.
	 */
	public function is_settings_page(): bool {
		return is_admin() && isset( $_GET['page'] ) && $this->get_page() === sanitize_text_field( $_GET['page'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Get the configuration page slug.
	 */
	public function get_page(): string {
		return 'sequra';
	}

	/**
	 * Get the configuration page parent slug.
	 */
	public function get_parent_page(): string {
		return 'woocommerce';
	}
}
