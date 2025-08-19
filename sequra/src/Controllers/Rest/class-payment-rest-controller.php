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
use SeQura\WC\Services\Payment\Interface_Payment_Method_Service;
use SeQura\Core\BusinessLogic\AdminAPI\PaymentMethods\Requests\GetFormattedPaymentMethodsRequest;
use SeQura\Core\BusinessLogic\AdminAPI\PaymentMethods\Requests\GetPaymentMethodsRequest;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST Payment Controller
 */
class Payment_REST_Controller extends REST_Controller {

	/**
	 * Payment method service
	 * 
	 * @var Interface_Payment_Method_Service
	 */
	private $payment_method_service;

	/**
	 * Constructor.
	 *
	 * @param string $rest_namespace The namespace.
	 * @param Interface_Logger_Service $logger         The logger service.
	 * @param Interface_Payment_Method_Service $payment_method_service The payment method service.
	 */
	public function __construct( $rest_namespace, Interface_Logger_Service $logger, Interface_Payment_Method_Service $payment_method_service ) {
		parent::__construct( $logger );
		$this->namespace              = $rest_namespace;
		$this->rest_base              = '/payment';
		$this->payment_method_service = $payment_method_service;
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
		$response = null;
		try {
			$response = AdminAPI::get()
			->paymentMethods( strval( $request->get_param( self::PARAM_STORE_ID ) ) )
			->getPaymentMethods(
				new GetPaymentMethodsRequest( strval( $request->get_param( self::PARAM_MERCHANT_ID ) ), true )
			)
			->toArray();
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			$response = new WP_Error( 'error', $e->getMessage() );
		}
		return \rest_ensure_response( $response );
	}

	/**
	 * Get all payment methods.
	 * 
	 * @param WP_REST_Request $request The request.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_all_methods( WP_REST_Request $request ) {
		$response = null;
		try {
			$response = AdminAPI::get()
			->paymentMethods( strval( $request->get_param( self::PARAM_STORE_ID ) ) )
			->getAllAvailablePaymentMethods(
				new GetFormattedPaymentMethodsRequest( true )
			)
			->toArray();
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			$response = new WP_Error( 'error', $e->getMessage() );
		}
		return \rest_ensure_response( $response );
	}
}
