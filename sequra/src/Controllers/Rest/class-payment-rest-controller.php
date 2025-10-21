<?php
/**
 * REST Payment Controller
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers/Rest
 */

namespace SeQura\WC\Controllers\Rest;

use SeQura\Core\BusinessLogic\AdminAPI\AdminAPI;
use SeQura\WC\Services\Log\Interface_Logger_Service;
use SeQura\Core\BusinessLogic\AdminAPI\PaymentMethods\Requests\GetFormattedPaymentMethodsRequest;
use SeQura\Core\BusinessLogic\AdminAPI\PaymentMethods\Requests\GetPaymentMethodsRequest;
use SeQura\Core\BusinessLogic\AdminAPI\Response\Response;
use SeQura\Core\Infrastructure\Utility\RegexProvider;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST Payment Controller
 */
class Payment_REST_Controller extends REST_Controller {

	/**
	 * Constructor.
	 *
	 * @param string $rest_namespace The namespace.
	 * @param Interface_Logger_Service $logger         The logger service.
	 * @param RegexProvider $regex The regex provider.
	 */
	public function __construct( 
		$rest_namespace, 
		Interface_Logger_Service $logger,
		RegexProvider $regex
	) {
		parent::__construct( $logger, $regex );
		$this->namespace = $rest_namespace;
		$this->rest_base = '/payment';
	}

	/**
	 * Register the API endpoints.
	 * 
	 * @return void
	 */
	public function register_routes() {
		$this->logger->log_debug( 'Hook executed', __FUNCTION__, __CLASS__ );
		$data_store_id = array( self::PARAM_STORE_ID => $this->get_arg_string() );
		$data_methods  = array_merge(
			$data_store_id,
			array( self::PARAM_MERCHANT_ID => $this->get_arg_string() )
		);

		$store_id    = $this->url_param_pattern( self::PARAM_STORE_ID );
		$merchant_id = $this->url_param_pattern( self::PARAM_MERCHANT_ID );
		
		$this->register_get( "methods/{$store_id}/{$merchant_id}", 'get_methods', $data_methods );
		$this->register_get( "methods/{$store_id}", 'get_all_methods', $data_store_id );
	}

	/**
	 * Get payment methods.
	 * 
	 * @param WP_REST_Request $request The request.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_methods( WP_REST_Request $request ) {
		/**
		 * Response
		 *
		 * @var Response $response */
		$response = AdminAPI::get()
		->paymentMethods( strval( $request->get_param( self::PARAM_STORE_ID ) ) )
		->getPaymentMethods(
			new GetPaymentMethodsRequest( strval( $request->get_param( self::PARAM_MERCHANT_ID ) ), true )
		);
		return $this->build_response( $response );
	}

	/**
	 * Get all payment methods.
	 * 
	 * @param WP_REST_Request $request The request.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_all_methods( WP_REST_Request $request ) {
		/**
		 * Response
		 *
		 * @var Response $response */
		$response = AdminAPI::get()
		->paymentMethods( strval( $request->get_param( self::PARAM_STORE_ID ) ) )
		->getAllAvailablePaymentMethods(
			new GetFormattedPaymentMethodsRequest( true )
		);
		return $this->build_response( $response );
	}
}
