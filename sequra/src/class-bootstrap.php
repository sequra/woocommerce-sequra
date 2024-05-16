<?php
/**
 * Implementation for the core bootstrap class.
 *
 * @package    SeQura/WC
 */

namespace SeQura\WC;

use SeQura\Core\BusinessLogic\BootstrapComponent;
use SeQura\Core\Infrastructure\Configuration\Configuration;
use SeQura\Core\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use SeQura\Core\Infrastructure\ServiceRegister;
use SeQura\WC\Services\Core\Config_Service;

/**
 * Implementation for the core bootstrap class.
 */
class Bootstrap extends BootstrapComponent implements Interface_Bootstrap {

	/**
	 * Constructor.
	 */
	public function __construct() {
	}

	/**
	 * Initialize the bootstrap.
	 */
	public function do_init() {
		self::init();
	}

	/**
	 * Initializes services and utilities.
	 */
	protected static function initServices(): void {
		parent::initServices();

		// TODO: add sequra-core services implementations here...
		ServiceRegister::registerService(
			ShopLoggerAdapter::CLASS_NAME,
			static function () {
				return new Services\Core\Logger_Service();
			}
		);

		ServiceRegister::registerService(
			Configuration::CLASS_NAME,
			static function () {
				return Config_Service::getInstance();
			}
		);
	}

	/**
	 * Initializes repositories.
	 */
	protected static function initRepositories(): void {
		parent::initRepositories();

		// TODO: add sequra-core repositories implementations here...
	}
}
