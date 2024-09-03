<?php
/**
 * REST Payment Controller
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers/Rest
 */

namespace SeQura\WC\Controllers\Rest;

use SeQura\Core\BusinessLogic\AdminAPI\AdminAPI;
use SeQura\Core\Infrastructure\Logger\LogContextData;
use SeQura\WC\Services\Interface_Logger_Service;
use SeQura\WC\Services\Payment\Interface_Payment_Method_Service;
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
	 */
	public function register_routes(): void {
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
			$response = $this->payment_method_service->get_all_payment_methods(
				strval( $request->get_param( self::PARAM_STORE_ID ) ),
				strval( $request->get_param( self::PARAM_MERCHANT_ID ) )
			);
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			$response = new WP_Error( 'error', $e->getMessage() );
		}
		return rest_ensure_response( $response );
	}

	/**
	 * Get all payment methods.
	 * 
	 * @param WP_REST_Request $request The request.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_all_methods( WP_REST_Request $request ) {
		$response = array();
		try {
			$store_id = strval( $request->get_param( self::PARAM_STORE_ID ) );

			$countries = AdminAPI::get()
			->countryConfiguration( $store_id )
			->getCountryConfigurations()
			->toArray();

			foreach ( $countries as $country ) {
				if ( ! isset( $country['merchantId'] ) ) {
					$this->logger->log_debug( 'Merchant ID not found', __FUNCTION__, __CLASS__, array( new LogContextData( 'countryConfiguration', $country ) ) );
					continue;
				}
				
				$payment_methods = $this->payment_method_service->get_all_payment_methods( $store_id, strval( $country['merchantId'] ) );

				foreach ( $payment_methods as $payment_method ) {
					if ( ! isset( $country['countryCode'], $payment_method['product'], $payment_method['title'] ) ) {
						$this->logger->log_debug( 'Required properties not found', __FUNCTION__, __CLASS__, array( new LogContextData( 'countryConfiguration', $country ), new LogContextData( 'paymentMethod', $payment_method ) ) );
						continue;
					}

					$response[] = array(
						'countryCode'                 => $country['countryCode'],
						'product'                     => $payment_method['product'],
						'title'                       => $payment_method['title'],
						'campaign'                    => $payment_method['campaign'] ?? null,
						'supportsWidgets'             => $payment_method['supportsWidgets'],
						'supportsInstallmentPayments' => $payment_method['supportsInstallmentPayments'],
					);
				}
			}
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			$response = new WP_Error( 'error', $e->getMessage() );
		}
		return rest_ensure_response( $response );
	}
}
