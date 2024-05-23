<?php
/**
 * REST Onboarding Controller
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers/Rest
 */

namespace SeQura\WC\Controllers\Rest;

use SeQura\Core\BusinessLogic\AdminAPI\AdminAPI;
use SeQura\Core\BusinessLogic\AdminAPI\Connection\Requests\ConnectionRequest;
use SeQura\Core\BusinessLogic\AdminAPI\Connection\Requests\OnboardingRequest;
use SeQura\WC\Services\Core\Configuration;

/**
 * REST Onboarding Controller
 */
class Onboarding_REST_Controller extends REST_Controller {

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
	 * @param Configuration $configuration The configuration.
	 */
	public function __construct( $rest_namespace, Configuration $configuration ) {
		$this->namespace     = $rest_namespace;
		$this->rest_base     = '/onboarding';
		$this->configuration = $configuration;
	}

	/**
	 * Register the API endpoints.
	 */
	public function register_routes() {

		$data_args = array(
			'environment'         => array(
				'required'          => true,
				'validate_callback' => array( $this, 'validate_environment' ),
			),
			'username'            => array(
				'required'          => true,
				'validate_callback' => array( $this, 'validate_not_empty_string' ),
			),
			'password'            => array(
				'required'          => true,
				'validate_callback' => array( $this, 'validate_not_empty_string' ),
			),
			'sendStatisticalData' => array(
				'default'           => false,
				'required'          => true,
				'validate_callback' => array( $this, 'validate_is_bool' ),
				'sanitize_callback' => array( $this, 'sanitize_bool' ),
			),
			'merchantId'          => array(
				'default'           => null,
				'required'          => false,
				'validate_callback' => array( $this, 'validate_not_empty_string' ),
			),
		);

		$this->register_get( 'data', 'get_connection_data' );
		$this->register_post( 'data', 'save_connection_data', $data_args );
		$this->register_post( 'data/validate', 'validate_connection_data', $data_args );
		$this->register_post( 'data/disconnect', 'disconnect' );

		$this->register_get( 'countries', 'get_countries' );
		$this->register_get( 'widgets', 'get_widgets' );
	}

	/**
	 * Get connection data.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_connection_data() {
		$data = AdminAPI::get()->connection( $this->configuration->get_store_id() )->getOnboardingData();
		return rest_ensure_response( $data->toArray() );
	}

	/**
	 * Validate connection data before saving.
	 * 
	 * @param WP_REST_Request $request The request.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function validate_connection_data( $request ) {
		$response = null;
		try {
			$response = AdminAPI::get()->connection( $this->configuration->get_store_id() )->isConnectionDataValid(
				new ConnectionRequest(
					$request->get_param( 'environment' ),
					$request->get_param( 'merchantId' ),
					$request->get_param( 'username' ),
					$request->get_param( 'password' )
				)
			);
			$response = $response->toArray();
		} catch ( \Throwable $e ) {
			// TODO: Log error.
			$response = new \WP_Error( 'error', $e->getMessage() );
		}
		return rest_ensure_response( $response );
	}

	/**
	 * Save connection data.
	 * 
	 * @throws \Exception
	 * @param WP_REST_Request $request The request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function save_connection_data( $request ) {
		$response = null;
		try {
			$response = AdminAPI::get()->connection( $this->configuration->get_store_id() )->saveOnboardingData(
				new OnboardingRequest(
					$request->get_param( 'environment' ),
					$request->get_param( 'username' ),
					$request->get_param( 'password' ),
					$request->get_param( 'sendStatisticalData' ),
					$request->get_param( 'merchantId' )
				)
			);

			$is_ok    = $response->isSuccessful();
			$response = $response->toArray();

			if ( ! $is_ok ) {
				throw new \Exception( $response['errorMessage'] );
			}
		} catch ( \Throwable $e ) {
			// TODO: Log error.
			$response = new \WP_Error( 'error', $e->getMessage() );
		}
		return rest_ensure_response( $response );
	}

	/**
	 * Disconnects integration from the shop.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function disconnect() {
		$response = null;
		try {
			$response = AdminAPI::get()->disconnect( $this->configuration->get_store_id() )->disconnect();
			$response = $response->toArray();
		} catch ( \Throwable $e ) {
			// TODO: Log error.
			$response = new \WP_Error( 'error', $e->getMessage() );
		}
		return rest_ensure_response( $response );
	}

	/**
	 * GET countries.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_countries() {
		$data = array(
			array(
				'countryCode' => 'ES',
				'merchantId'  => 'dummy_ps_mikel',
			),
			array(
				'countryCode' => 'FR',
				'merchantId'  => 'dummy_fr',
			),
		);
		return rest_ensure_response( $data );
	}

	/**
	 * GET widgets.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_widgets() {
		$data = array(
			'useWidgets'                            => true,
			'displayWidgetOnProductPage'            => true,
			'showInstallmentAmountInProductListing' => true,
			'showInstallmentAmountInCartPage'       => true,
			'assetsKey'                             => 'ADc3ZdOLh4',
			'miniWidgetSelector'                    => '',
			'widgetLabels'                          => array(
				'message'           => 'Desde %s/mes',
				'messageBelowLimit' => 'Fracciona a partir de %s',
			),
			'widgetStyles'                          => '{"alignment":"center","amount-font-bold":"true","amount-font-color":"#1C1C1C","amount-font-size":"15","background-color":"white","border-color":"#B1AEBA","border-radius":"","class":"","font-color":"#1C1C1C","link-font-color":"#1C1C1C","link-underline":"true","no-costs-claim":"","size":"M","starting-text":"only","type":"banner"}',
		);
		return rest_ensure_response( $data );
	}

	/**
	 * Validate if the parameter is not empty.
	 */
	public function validate_environment( $param, $request, $key ) {
		return in_array( $param, array( 'sandbox', 'production' ), true );
	}
}
