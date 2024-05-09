<?php
/**
 * Settings
 *
 * @package    Sequra/WC
 * @subpackage Sequra/WC/Services
 */

namespace Sequra\WC\Services;

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
	 * Constructor
	 */
	public function __construct() {
		$this->preferences = null;
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
}
