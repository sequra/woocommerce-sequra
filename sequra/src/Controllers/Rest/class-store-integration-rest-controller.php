<?php
/**
 * Store Integration REST Controller
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers/Rest
 */

namespace SeQura\WC\Controllers\Rest;

use SeQura\Core\BusinessLogic\ConfigurationWebhookAPI\ConfigurationWebhookAPI;
use SeQura\WC\Services\Log\Interface_Logger_Service;
use SeQura\Core\Infrastructure\Utility\RegexProvider;
use SeQura\WC\Core\Implementation\BusinessLogic\Domain\Integration\StoreIntegration\Interface_Store_Integration_Service;
use SeQura\Core\BusinessLogic\Domain\Multistore\StoreContext;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Store Integration REST Controller
 */
class Store_Integration_REST_Controller extends REST_Controller {

	protected const PARAM_SIGNATURE = 'signature';

	/**
	 * Store integration service
	 *
	 * @var Interface_Store_Integration_Service
	 */
	private $store_integration_service;

	/**
	 * Store context
	 *
	 * @var StoreContext
	 */
	private $store_context;
	
	/**
	 * Constructor.
	 *
	 * @param string            $rest_namespace The namespace.
	 * @param Interface_Logger_Service $logger         The logger service.
	 * @param RegexProvider $regex The regex provider.
	 * @param Interface_Store_Integration_Service $store_integration_service The store integration service.
	 * @param StoreContext $store_context The store context.
	 */
	public function __construct( 
		$rest_namespace, 
		Interface_Logger_Service $logger,
		RegexProvider $regex,
		Interface_Store_Integration_Service $store_integration_service,
		StoreContext $store_context
	) {
		parent::__construct( $logger, $regex );
		$this->store_integration_service = $store_integration_service;
		$this->namespace                 = $rest_namespace;
		$this->rest_base                 = $this->store_integration_service->get_rest_base();
		$this->store_context             = $store_context;
	}

	/**
	 * Register the API endpoints.
	 * 
	 * @return void
	 */
	public function register_routes() {
		$this->logger->log_debug( 'Hook executed', __FUNCTION__, __CLASS__ );
		$args = array( 
			self::PARAM_STORE_ID  => $this->get_arg_string(),
			self::PARAM_SIGNATURE => $this->get_arg_string(),
		);
		$this->register_post( $this->store_integration_service->get_endpoint(), 'handle_post', $args, 'check_permissions' );
	}

	/**
	 * Check the request data to see if it has permission to proceed.
	 */
	public function check_permissions(): bool {
		// The signature is checked in the Integration Core, no need to check it here.
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
		$signature = $request->get_param( 'signature' );
		$payload   = $request->get_json_params();
		$response  = ConfigurationWebhookAPI::configurationHandler( $this->store_context->getStoreId() )
		->handleRequest( $signature, $payload );
		return $this->build_response( $response );
	}
}
