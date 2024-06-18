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
	 * Templates path.
	 *
	 * @var string
	 */
	protected $templates_path;

	/**
	 * Constructor.
	 */
	public function __construct( Interface_Logger_Service $logger, string $templates_path ) {
		$this->logger         = $logger;
		$this->templates_path = $templates_path;
	}
}
