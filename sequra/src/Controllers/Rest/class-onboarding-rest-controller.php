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
use SeQura\Core\BusinessLogic\AdminAPI\PromotionalWidgets\Requests\WidgetSettingsRequest;
use SeQura\WC\Services\Core\Configuration;
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
			'environment'         => array_merge(
				$this->get_arg_string(),
				array( 'enum' => array( 'sandbox', 'production' ) )
			),
			'username'            => $this->get_arg_string(),
			'password'            => $this->get_arg_string(),
			'sendStatisticalData' => $this->get_arg_bool(),
			'merchantId'          => $this->get_arg_string(),
		);

		$store_id_args = array( 'storeId' => $this->get_arg_string() );

		$widget_args = array(
			'storeId'                               => $this->get_arg_string(),
			'useWidgets'                            => $this->get_arg_bool(),
			'assetsKey'                             => $this->get_arg_string(),
			'displayWidgetOnProductPage'            => $this->get_arg_bool(),
			'showInstallmentAmountInProductListing' => $this->get_arg_bool(),
			'showInstallmentAmountInCartPage'       => $this->get_arg_bool(),
			'miniWidgetSelector'                    => $this->get_arg_string( true, '', '__return_true' ), // TODO: Add this field to form in the UI.
			'widgetStyles'                          => $this->get_arg_string(),
			'widgetLabels'                          => array(
				'default'           => array(
					'message'           => '',
					'messageBelowLimit' => '',
				),
				'required'          => false,
				'validate_callback' => array( $this, 'validate_widget_labels' ),
				'sanitize_callback' => array( $this, 'sanitize_widget_labels' ),
			),
		);

		$this->register_get( 'data', 'get_connection_data' );
		$this->register_post( 'data', 'save_connection_data', $data_args );
		$this->register_post( 'data/validate', 'validate_connection_data', $data_args );
		$this->register_post( 'data/disconnect', 'disconnect' );

		$this->register_get( 'widgets/(?P<storeId>[\w]+)', 'get_widgets', $store_id_args );
		$this->register_post( 'widgets/(?P<storeId>[\w]+)', 'save_widgets', $widget_args );
		
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
	 * @param WP_REST_Request $request The request.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_widgets( $request ) {
		$response = null;
		try {
			$response = AdminAPI::get()
			->widgetConfiguration( $request->get_param( 'storeId' ) )
			->getWidgetSettings()
			->toArray();

			if ( ! empty( $response ) ) {
				$response['widgetLabels']['message']           = ! empty( $response['widgetLabels']['messages'] ) ?
				reset( $response['widgetLabels']['messages'] ) : '';
				$response['widgetLabels']['messageBelowLimit'] = ! empty( $response['widgetLabels']['messagesBelowLimit'] ) ?
				reset( $response['widgetLabels']['messagesBelowLimit'] ) : '';
				$response['widgetStyles']                      = $response['widgetConfiguration'];
				unset( $response['widgetLabels']['messages'], $response['widgetLabels']['messagesBelowLimit'] );
				unset( $response['widgetConfiguration'] );
			}
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
	public function save_widgets( $request ) {
		$response = null;
		try {
			// $labels               = $request->get_param( 'widgetLabels' );
			// $messages             = $labels['message'] ? array( $storeConfig->getLocale() => $labels['message'] ) : array();
			// $messages_below_limit = $labels['messageBelowLimit'] ? array( $storeConfig->getLocale() => $labels['messageBelowLimit'] ) : array();

			$messages             = array();
			$messages_below_limit = array();

			$response = AdminAPI::get()
			->widgetConfiguration( $request->get_param( 'storeId' ) )
			->setWidgetSettings(
				new WidgetSettingsRequest(
					$request->get_param( 'useWidgets' ),
					$request->get_param( 'assetsKey' ),
					$request->get_param( 'displayWidgetOnProductPage' ),
					$request->get_param( 'showInstallmentAmountInProductListing' ),
					$request->get_param( 'showInstallmentAmountInCartPage' ),
					$request->get_param( 'miniWidgetSelector' ) ?? '',
					$request->get_param( 'widgetStyles' ),
					$messages,
					$messages_below_limit
				)
			)
			->toArray();
		} catch ( \Throwable $e ) {
			// TODO: Log error.
			$response = new \WP_Error( 'error', $e->getMessage() );
		}
		return rest_ensure_response( $response );
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

	/**
	 * Validate if widget label is valid.
	 */
	public function validate_widget_labels( $param, $request, $key ) {
		return is_array( $param ) 
		&& isset( $param['message'] )
		&& is_string( $param['message'] )
		&& isset( $param['messageBelowLimit'] )
		&& is_string( $param['messageBelowLimit'] );
	}

	/**
	 * Sanitize widget label.
	 */
	public function sanitize_widget_labels( $param ) {
		return array(
			'message'           => sanitize_text_field( $param['message'] ),
			'messageBelowLimit' => sanitize_text_field( $param['messageBelowLimit'] ),
		);
	}
}
