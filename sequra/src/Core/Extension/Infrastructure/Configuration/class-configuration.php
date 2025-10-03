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

	/**
	 * Retrieves integration name.
	 *
	 * @return string Integration name.
	 */
	public function getIntegrationName() {
		/**
		 * Constants
		 * 
		 * @var Interface_Constants $constants
		 */
		$constants = ServiceRegister::getService(Interface_Constants::class);
		return $constants->get_integration_name();
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
}
