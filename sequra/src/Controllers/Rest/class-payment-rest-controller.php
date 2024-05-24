<?php
/**
 * REST Payment Controller
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers/Rest
 */

namespace SeQura\WC\Controllers\Rest;

use SeQura\Core\BusinessLogic\AdminAPI\AdminAPI;

/**
 * REST Payment Controller
 */
class Payment_REST_Controller extends REST_Controller {

	/**
	 * Constructor.
	 *
	 * @param string $rest_namespace The namespace.
	 */
	public function __construct( $rest_namespace ) {
		$this->namespace = $rest_namespace;
		$this->rest_base = '/payment';
	}

	/**
	 * Register the API endpoints.
	 */
	public function register_routes() {

		$data_methods = array(
			self::PARAM_STORE_ID    => $this->get_arg_string(),
			self::PARAM_MERCHANT_ID => $this->get_arg_string(),
		);

		$this->register_get( 'methods/(?P<' . self::PARAM_STORE_ID . '>[\w]+)/(?P<' . self::PARAM_MERCHANT_ID . '>[\w]+)', 'get_methods', $data_methods );
	}

	/**
	 * Get payment methods.
	 * 
	 * @param WP_REST_Request $request The request.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_methods( $request ) {
		$response = null;
		try {
			$response = AdminAPI::get()
			->paymentMethods( $request->get_param( self::PARAM_STORE_ID ) )
			->getPaymentMethods( $request->get_param( self::PARAM_MERCHANT_ID ) )
			->toArray();
		} catch ( \Throwable $e ) {
			// TODO: Log error.
			$response = new \WP_Error( 'error', $e->getMessage() );
		}
		return rest_ensure_response( $response );
	}
}
