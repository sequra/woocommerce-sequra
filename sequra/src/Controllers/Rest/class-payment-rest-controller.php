<?php
/**
 * REST Payment Controller
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers/Rest
 */

namespace SeQura\WC\Controllers\Rest;

use SeQura\Core\BusinessLogic\AdminAPI\AdminAPI;
use SeQura\WC\Services\Interface_Logger_Service;
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
	 */
	public function __construct( $rest_namespace, Interface_Logger_Service $logger ) {
		parent::__construct( $logger );
		$this->namespace = $rest_namespace;
		$this->rest_base = '/payment';
	}

	/**
	 * Register the API endpoints.
	 */
	public function register_routes(): void {
		$this->logger->log_info( 'Hook executed', __FUNCTION__, __CLASS__ );
		$data_methods = array(
			self::PARAM_STORE_ID    => $this->get_arg_string(),
			self::PARAM_MERCHANT_ID => $this->get_arg_string(),
		);

		$store_id    = $this->url_param_pattern( self::PARAM_STORE_ID );
		$merchant_id = $this->url_param_pattern( self::PARAM_MERCHANT_ID );
		
		$this->register_get( "methods/{$store_id}/{$merchant_id}", 'get_methods', $data_methods );
	}

	/**
	 * Get payment methods.
	 * 
	 * @param WP_REST_Request $request The request.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_methods( WP_REST_Request $request ) {
		$response = null;
		try {
			$response = AdminAPI::get()
			->paymentMethods( strval( $request->get_param( self::PARAM_STORE_ID ) ) )
			->getPaymentMethods( strval( $request->get_param( self::PARAM_MERCHANT_ID ) ) )
			->toArray();
		} catch ( \Throwable $e ) {
			// TODO: Log error.
			$response = new WP_Error( 'error', $e->getMessage() );
		}
		return rest_ensure_response( $response );
	}
}
