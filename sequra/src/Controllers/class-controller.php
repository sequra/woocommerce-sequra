<?php
/**
 * Controller
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers
 */

namespace SeQura\WC\Controllers;

use SeQura\WC\Services\Interface_Logger_Service;

/**
 * Controller
 */
abstract class Controller {

	/**
	 * Logger service.
	 *
	 * @var Interface_Logger_Service
	 */
	protected $logger;

	/**
	 * Constructor.
	 *
	 * @param Interface_Logger_Service $logger         The logger service.
	 */
	public function __construct( Interface_Logger_Service $logger ) {
		$this->logger = $logger;
	}
}
