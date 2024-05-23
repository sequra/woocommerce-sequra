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
use SeQura\Core\BusinessLogic\AdminAPI\CountryConfiguration\Requests\CountryConfigurationRequest;
use SeQura\WC\Services\Core\Configuration;
use WP_HTTP_Response;
use WP_REST_Response;

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

		$this->register_get( 'widgets', 'get_widgets' );
		
		$this->register_get( 'countries/selling', 'get_selling_countries' );
		$this->register_get( 'countries', 'get_countries' );
		$this->register_post( 'countries', 'save_countries' );
	}

	/**
	 * Get connection data.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_connection_data() {
		$response = AdminAPI::get()
		->connection( $this->configuration->get_store_id() )
		->getOnboardingData()
		->toArray();
		return rest_ensure_response( $response );
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
			$response = AdminAPI::get()
			->connection( $this->configuration->get_store_id() )
			->isConnectionDataValid(
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
			$response = AdminAPI::get()
			->connection( $this->configuration->get_store_id() )
			->saveOnboardingData(
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
			$response = AdminAPI::get()
			->disconnect( $this->configuration->get_store_id() )
			->disconnect()
			->toArray();
		} catch ( \Throwable $e ) {
			// TODO: Log error.
			$response = new \WP_Error( 'error', $e->getMessage() );
		}
		return rest_ensure_response( $response );
	}

	/**
	 * GET selling countries.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_selling_countries() {
		$response = null;
		try {
			$response = AdminAPI::get()
			->countryConfiguration( $this->configuration->get_store_id() )
			->getSellingCountries()
			->toArray();
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
		$response = null;
		try {
			$response = AdminAPI::get()
			->countryConfiguration( $this->configuration->get_store_id() )
			->getCountryConfigurations()
			->toArray();
		} catch ( \Throwable $e ) {
			// TODO: Log error.
			$response = new \WP_Error( 'error', $e->getMessage() );
		}
		return rest_ensure_response( $response );
	}

	/**
	 * Save countries.
	 * 
	 * @throws \Exception
	 * @param WP_REST_Request $request The request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function save_countries( $request ) {
		if ( ! $this->validate_countries( $request ) ) {
			return new WP_REST_Response( 'Invalid data', 400 );
		}

		$response = null;
		try {
			$data = json_decode( $request->get_body(), true );

			$response = AdminAPI::get()
			->countryConfiguration( $this->configuration->get_store_id() )
			->saveCountryConfigurations( new CountryConfigurationRequest( $data ) )
			->toArray();
		} catch ( \Throwable $e ) {
			// TODO: Log error.
			$response = new \WP_Error( 'error', $e->getMessage() );
		}
		return rest_ensure_response( $response );
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

	/**
	 * Validate if countries payload is valid.
	 * 
	 * @param WP_REST_Request $request The request.
	 */
	public function validate_countries( $request ): bool {
		try {
			$data = json_decode( $request->get_body(), true );
			if ( ! is_array( $data ) ) {
				return false;
			}
			$allowed_countries = AdminAPI::get()
			->countryConfiguration( $this->configuration->get_store_id() )
			->getSellingCountries()
			->toArray();

			$allowed_countries = array_column( $allowed_countries, 'code' );

			foreach ( $data as $country ) {
				if ( ! isset( $country['countryCode'] ) 
				|| ! isset( $country['merchantId'] ) 
				|| ! is_string( $country['countryCode'] ) 
				|| ! is_string( $country['merchantId'] )
				|| ! in_array( $country['countryCode'], $allowed_countries, true )
				) {
					return false;
				}
			}
		} catch ( \Throwable $e ) {
			return false;
		}
		return true;
	}
}
