<?php
/**
 * Store Integration REST Controller
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers/Rest
 */

namespace SeQura\WC\Controllers\Rest;

use Exception;
use SeQura\Core\BusinessLogic\AdminAPI\Response\ErrorResponse;
use SeQura\WC\Services\Log\Interface_Logger_Service;
use SeQura\Core\Infrastructure\Utility\RegexProvider;
use SeQura\WC\Core\Implementation\BusinessLogic\Domain\Integration\StoreIntegration\Interface_Store_Integration_Service;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Store Integration REST Controller
 */
class Store_Integration_REST_Controller extends REST_Controller {

	/**
	 * Store integration service
	 *
	 * @var Interface_Store_Integration_Service
	 */
	private $store_integration_service;
	
	/**
	 * Constructor.
	 *
	 * @param string            $rest_namespace The namespace.
	 * @param Interface_Logger_Service $logger         The logger service.
	 * @param RegexProvider $regex The regex provider.
	 * @param Interface_Store_Integration_Service $store_integration_service The store integration service.
	 */
	public function __construct( 
		$rest_namespace, 
		Interface_Logger_Service $logger,
		RegexProvider $regex,
		Interface_Store_Integration_Service $store_integration_service
	) {
		parent::__construct( $logger, $regex );
		$this->store_integration_service = $store_integration_service;
		$this->namespace                 = $rest_namespace;
		$this->rest_base                 = $this->store_integration_service->get_rest_base();
	}

	/**
	 * Register the API endpoints.
	 * 
	 * @return void
	 */
	public function register_routes() {
		$this->logger->log_debug( 'Hook executed', __FUNCTION__, __CLASS__ );
		$this->register_post( $this->store_integration_service->get_endpoint(), 'handle_post', array(), 'check_permissions' );
	}

	/**
	 * Check the request data to see if the it has permission to proceed.
	 */
	public function check_permissions(): bool {
		// TODO: implement permission checks.
		return true;
	}

	/**
	 * Handle POST request.
	 * 
	 * @throws \Exception
	 * @param WP_REST_Request $request The request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_post( WP_REST_Request $request ) {
		// todo: implement POST handling.
		return $this->build_response( new ErrorResponse( new Exception( 'Not implemented' ) ) );
	}
}
