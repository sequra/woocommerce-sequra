<?php
/**
 * Implementation for the core bootstrap class.
 *
 * @package    SeQura/WC
 */

namespace SeQura\WC;

use SeQura\Core\BusinessLogic\BootstrapComponent;

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
	}

	/**
	 * Initializes repositories.
	 */
	protected static function initRepositories(): void {
		parent::initRepositories();

		// TODO: add sequra-core repositories implementations here...
	}
}
