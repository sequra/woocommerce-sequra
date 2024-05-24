<?php
/**
 * REST Payment Controller
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers/Rest
 */

namespace SeQura\WC\Controllers\Rest;

use SeQura\Core\BusinessLogic\AdminAPI\AdminAPI;
use SeQura\WC\Services\Core\Configuration;

/**
 * REST Payment Controller
 */
class Payment_REST_Controller extends REST_Controller {
	
	/**
	 * Configuration.
	 *
	 * @var Configuration
	 */
	private $configuration;
	
	/**
	 * Constructor.
	 *
	 * @param string $rest_namespace The namespace.
	 */
	public function __construct( $rest_namespace, Configuration $configuration ) {
		$this->namespace     = $rest_namespace;
		$this->rest_base     = '/payment';
		$this->configuration = $configuration;
	}

	/**
	 * Register the API endpoints.
	 */
	public function register_routes() {

		$data_methods = array(
			'merchantId' => array(
				'required'          => true,
				'validate_callback' => array( $this, 'validate_not_empty_string' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
		);

		$this->register_get( 'methods/(?P<merchantId>[\w]+)', 'get_methods', $data_methods );
	}

	/**
	 * GET methods.
	 * 
	 * @param WP_REST_Request $request The request.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_methods( $request ) {
		$response = null;
		try {
			$response = AdminAPI::get()
			->paymentMethods( $this->configuration->get_store_id() )
			->getPaymentMethods( $request->get_param( 'merchantId' ) )
			->toArray();
		} catch ( \Throwable $e ) {
			// TODO: Log error.
			$response = new \WP_Error( 'error', $e->getMessage() );
		}
		return rest_ensure_response( $response );
	}
}
