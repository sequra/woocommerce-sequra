<?php
/**
 * Extends the Configuration class.
 * Delegate to the ConfigurationManager instance to access the data in the database.
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Extension\Infrastructure\Configuration;

use SeQura\Core\Infrastructure\Configuration\Configuration as CoreConfiguration;
use SeQura\Core\Infrastructure\ServiceRegister;
use SeQura\WC\Services\Constants\Interface_Constants;

/**
 * Extends the Configuration class. Wrapper to ease the read and write of configuration values.
 */
class Configuration extends CoreConfiguration {

	private const CONF_DB_VERSION = 'dbVersion';

	/**
	 * Constants service
	 * 
	 * @var Interface_Constants
	 */
	private $constants;

	/**
	 * Constructor
	 */
	protected function __construct() {
		parent::__construct();
		/**
		 * Constants service
		 * 
		 * @var Interface_Constants $constants
		 */
		$constants       = ServiceRegister::getService( Interface_Constants::class );
		$this->constants = $constants;
	}

	/**
	 * Marketplace version.
	 *
	 * @var ?string
	 */
	private $marketplace_version;

	/**
	 * Retrieves integration name.
	 *
	 * @return string Integration name.
	 */
	public function getIntegrationName() {
		return $this->constants->get_integration_name();
	}

	/**
	 * Gets the current version of the module/integration.
	 */
	public function get_module_version(): string {
		return strval( $this->getConfigurationManager()->getConfigValue( 'version', '' ) );
	}

	/**
	 * Gets the current version of the module/integration.
	 *
	 * @param string $version The version number.
	 */
	public function set_module_version( $version ): void {
		$this->getConfigurationManager()->saveConfigValue( 'version', $version );
	}

	/**
	 * Returns async process starter url, always in http.
	 *
	 * @param string $guid Process identifier.
	 *
	 * @return string Formatted URL of async process starter endpoint.
	 */
	public function getAsyncProcessUrl( $guid ) {
		return ''; // Not used in this implementation.
	}

	/**
	 * Check if the current page is the settings page.
	 */
	public function is_settings_page(): bool {
		return is_admin() && isset( $_GET['page'] ) && $this->get_page() === $_GET['page']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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

	// /**
	// * Get password from connection settings.
	// */
	// public function get_password(): string {

	// try {
	// $config = AdminAPI::get()
	// ->connection( $this->get_store_id() )
	// ->getOnboardingData()
	// ->toArray();

	// return $config['password'] ?? '';
	// } catch ( Throwable $e ) {
	// return '';
	// }
	// }

	/**
	 * Saves dbVersion in integration database.
	 */
	public function save_db_version( string $db_version ): void {
		$this->saveConfigValue( self::CONF_DB_VERSION, $db_version );
	}

	/**
	 * Retrieves dbVersion from integration database.
	 */
	public function get_db_version(): string {
		return $this->getConfigValue( self::CONF_DB_VERSION, '' );
	}
}
