<?php
/**
 * Settings
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services;

use SeQura\WC\Repositories\Interface_Settings_Repo;

/**
 * Settings
 */
class Settings implements Interface_Settings {
	
	private const OPTION_NAME = 'sequra';
	// Preferences keys.
	private const DEBUG = 'debug';

	/**
	 * The preferences
	 *
	 * @var      array
	 */
	private $preferences;

	/**
	 * The settings repo
	 *
	 * @var      Interface_Settings_Repo
	 */
	private $settings_repo;

	/**
	 * Constructor
	 */
	public function __construct( Interface_Settings_Repo $settings_repo ) {
		$this->preferences   = null;
		$this->settings_repo = $settings_repo;
	}

	/**
	 * Get preferences. Also sets defaults if not set.
	 *
	 * @return array Array of preferences.
	 */
	public function all() {
		if ( null === $this->preferences ) {
			$this->preferences = get_option( self::OPTION_NAME, array() );
			$need_update       = false;
			foreach ( $this->defaults() as $key => $value ) {
				if ( isset( $this->preferences[ $key ] ) ) {
					continue;
				}
				$this->preferences[ $key ] = $value;
				if ( ! $need_update ) {
					$need_update = true;
				}
			}
			if ( $need_update ) {
				update_option( self::OPTION_NAME, $this->preferences );
			}
		}
		return $this->preferences;
	}

	/**
	 * Get preference by key
	 *
	 * @param string $key Preference key.
	 *
	 * @return mixed Preference value. Null if not found.
	 */
	public function get( $key ) {
		return $this->all()[ $key ] ?? null;
	}

	/**
	 * Get default preferences
	 *
	 * @return array Array of default preferences.
	 */
	public function defaults() {
		return array(
			self::DEBUG => 'no',
		);
	}

	/**
	 * Check if a value is true.
	 *
	 * @param mixed $value The value to check.
	 *
	 * @return bool
	 */
	private function is_true( $value ) {
		return 'yes' === $value;
	}

	/**
	 * Check if debug mode is enabled.
	 *
	 * @return bool
	 */
	public function is_debug_enabled() {
		return $this->is_true( $this->get( self::DEBUG ) );
	}

	/**
	 * Get general settings.
	 *
	 * @param int|null $blog_id The blog ID.
	 * 
	 * @return mixed
	 */
	public function get_general_settings( $blog_id = null ) {
		return $this->settings_repo->get_general_settings( $blog_id );
	}

	/**
	 * Check if the current page is the settings page.
	 */
	public function is_settings_page(): bool {
		global $pagenow;
		return 'options-general.php' === $pagenow && isset( $_GET['page'] ) && 'sequra' === $_GET['page']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
}
