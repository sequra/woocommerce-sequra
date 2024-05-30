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
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST Onboarding Controller
 */
class Onboarding_REST_Controller extends REST_Controller {
	
	/**
	 * Constructor.
	 *
	 * @param string $rest_namespace The namespace.
	 */
	public function __construct( $rest_namespace ) {
		$this->namespace = $rest_namespace;
		$this->rest_base = '/onboarding';
	}

	/**
	 * Register the API endpoints.
	 */
	public function register_routes(): void {

		$store_id_args = array( self::PARAM_STORE_ID => $this->get_arg_string() );

		$data_args = array_merge(
			$store_id_args,
			array(
				'environment'         => array_merge(
					$this->get_arg_string(),
					array( 'enum' => array( 'sandbox', 'production' ) )
				),
				'username'            => $this->get_arg_string(),
				'password'            => $this->get_arg_string(),
				'sendStatisticalData' => $this->get_arg_bool(),
			)
		);
		
		$validate_data_args = array_merge(
			$data_args,
			array(
				self::PARAM_MERCHANT_ID => $this->get_arg_string(),
			)
		);

		$widget_args = array_merge(
			$store_id_args,
			array(
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
			)
		);

		$store_id = $this->url_param_pattern( self::PARAM_STORE_ID );

		$this->register_get( "data/{$store_id}", 'get_connection_data', $store_id_args );
		$this->register_post( "data/{$store_id}", 'save_connection_data', $data_args );
		$this->register_post( "data/validate/{$store_id}", 'validate_connection_data', $validate_data_args );
		$this->register_post( "data/disconnect/{$store_id}", 'disconnect', $store_id_args );

		$this->register_get( "widgets/{$store_id}", 'get_widgets', $store_id_args );
		$this->register_post( "widgets/{$store_id}", 'save_widgets', $widget_args );
		
		$this->register_get( "countries/selling/{$store_id}", 'get_selling_countries', $store_id_args );
		$this->register_get( "countries/{$store_id}", 'get_countries', $store_id_args );
		$this->register_post( "countries/{$store_id}", 'save_countries', $store_id_args );
	}

	/**
	 * Get connection data.
	 * 
	 * @param WP_REST_Request $request The request.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_connection_data( WP_REST_Request $request ) {
		$response = AdminAPI::get()
		->connection( strval( $request->get_param( self::PARAM_STORE_ID ) ) )
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
	public function validate_connection_data( WP_REST_Request $request ) {
		$response = null;
		try {
			$response = AdminAPI::get()
			->connection( strval( $request->get_param( self::PARAM_STORE_ID ) ) )
			->isConnectionDataValid(
				new ConnectionRequest(
					strval( $request->get_param( 'environment' ) ),
					strval( $request->get_param( 'merchantId' ) ),
					strval( $request->get_param( 'username' ) ),
					strval( $request->get_param( 'password' ) )
				)
			);
			$response = $response->toArray();
		} catch ( \Throwable $e ) {
			// TODO: Log error.
			$response = new WP_Error( 'error', $e->getMessage() );
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
	public function save_connection_data( WP_REST_Request $request ) {
		$response = null;
		try {
			$response = AdminAPI::get()
			->connection( strval( $request->get_param( self::PARAM_STORE_ID ) ) )
			->saveOnboardingData(
				new OnboardingRequest(
					strval( $request->get_param( 'environment' ) ),
					strval( $request->get_param( 'username' ) ),
					strval( $request->get_param( 'password' ) ),
					(bool) $request->get_param( 'sendStatisticalData' ),
					null === $request->get_param( 'merchantId' ) ? null : strval( $request->get_param( 'merchantId' ) )
				)
			);

			$is_ok    = $response->isSuccessful();
			$response = $response->toArray();

			if ( ! $is_ok ) {
				throw new \Exception( $response['errorMessage'] );
			}
		} catch ( \Throwable $e ) {
			// TODO: Log error.
			$response = new WP_Error( 'error', $e->getMessage() );
		}
		return rest_ensure_response( $response );
	}

	/**
	 * Disconnects integration from the shop.
	 *
	 * @param WP_REST_Request $request The request.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function disconnect( WP_REST_Request $request ) {
		$response = null;
		try {
			$response = AdminAPI::get()
			->disconnect( strval( $request->get_param( self::PARAM_STORE_ID ) ) )
			->disconnect()
			->toArray();
		} catch ( \Throwable $e ) {
			// TODO: Log error.
			$response = new WP_Error( 'error', $e->getMessage() );
		}
		return rest_ensure_response( $response );
	}

	/**
	 * GET selling countries.
	 * 
	 * @param WP_REST_Request $request The request.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_selling_countries( WP_REST_Request $request ) {
		$response = null;
		try {
			$response = AdminAPI::get()
			->countryConfiguration( strval( $request->get_param( self::PARAM_STORE_ID ) ) )
			->getSellingCountries()
			->toArray();
		} catch ( \Throwable $e ) {
			// TODO: Log error.
			$response = new WP_Error( 'error', $e->getMessage() );
		}
		return rest_ensure_response( $response );
	}

	/**
	 * GET countries.
	 * 
	 * @param WP_REST_Request $request The request.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_countries( WP_REST_Request $request ) {
		$response = null;
		try {
			$response = AdminAPI::get()
			->countryConfiguration( strval( $request->get_param( self::PARAM_STORE_ID ) ) )
			->getCountryConfigurations()
			->toArray();
		} catch ( \Throwable $e ) {
			// TODO: Log error.
			$response = new WP_Error( 'error', $e->getMessage() );
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
	public function save_countries( WP_REST_Request $request ) {
		if ( ! $this->validate_countries( $request ) ) {
			return new WP_REST_Response( 'Invalid data', 400 );
		}

		$response = null;
		try {
			$data = (array) json_decode( $request->get_body(), true );

			$response = AdminAPI::get()
			->countryConfiguration( strval( $request->get_param( self::PARAM_STORE_ID ) ) )
			->saveCountryConfigurations( new CountryConfigurationRequest( $data ) )
			->toArray();
		} catch ( \Throwable $e ) {
			// TODO: Log error.
			$response = new WP_Error( 'error', $e->getMessage() );
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
	public function get_widgets( WP_REST_Request $request ) {
		$response = null;
		try {
			$response = AdminAPI::get()
			->widgetConfiguration( strval( $request->get_param( self::PARAM_STORE_ID ) ) )
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
			$response = new WP_Error( 'error', $e->getMessage() );
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
	public function save_widgets( WP_REST_Request $request ) {
		$response = null;
		try {
			// TODO: Add support to CSS selector.
			// TODO: Add support to widget labels. See shopify example.
			//phpcs:disable Squiz.Commenting.InlineComment.InvalidEndChar
			// $labels               = $request->get_param( 'widgetLabels' );
			// $messages             = $labels['message'] ? array( $storeConfig->getLocale() => $labels['message'] ) : array();
			// $messages_below_limit = $labels['messageBelowLimit'] ? array( $storeConfig->getLocale() => $labels['messageBelowLimit'] ) : array();
			//phpcs:enable
			$messages             = array();
			$messages_below_limit = array();

			$response = AdminAPI::get()
			->widgetConfiguration( strval( $request->get_param( self::PARAM_STORE_ID ) ) )
			->setWidgetSettings(
				new WidgetSettingsRequest(
					(bool) $request->get_param( 'useWidgets' ),
					null === $request->get_param( 'assetsKey' ) ? null : strval( $request->get_param( 'assetsKey' ) ),
					(bool) $request->get_param( 'displayWidgetOnProductPage' ),
					(bool) $request->get_param( 'showInstallmentAmountInProductListing' ),
					(bool) $request->get_param( 'showInstallmentAmountInCartPage' ),
					strval( $request->get_param( 'miniWidgetSelector' ) ),
					strval( $request->get_param( 'widgetStyles' ) ),
					(array) $messages,
					(array) $messages_below_limit
				)
			)
			->toArray();
		} catch ( \Throwable $e ) {
			// TODO: Log error.
			$response = new WP_Error( 'error', $e->getMessage() );
		}
		return rest_ensure_response( $response );
	}

	/**
	 * Validate if countries payload is valid.
	 * 
	 * @param WP_REST_Request $request The request.
	 */
	public function validate_countries( WP_REST_Request $request ): bool {
		try {
			$data = json_decode( $request->get_body(), true );
			if ( ! is_array( $data ) ) {
				return false;
			}
			$allowed_countries = AdminAPI::get()
			->countryConfiguration( strval( $request->get_param( self::PARAM_STORE_ID ) ) )
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
	 * 
	 * @param mixed $param The parameter.
	 * @param WP_REST_Request $request The request.
	 * @param string $key The key.
	 */
	public function validate_widget_labels( $param, $request, $key ): bool {
		return is_array( $param ) 
		&& isset( $param['message'] )
		&& is_string( $param['message'] )
		&& isset( $param['messageBelowLimit'] )
		&& is_string( $param['messageBelowLimit'] );
	}

	/**
	 * Sanitize widget label.
	 * 
	 * @param mixed[] $param The parameter.
	 * @return mixed[]
	 */
	public function sanitize_widget_labels( $param ): array {
		return array(
			'message'           => sanitize_text_field( strval( $param['message'] ) ),
			'messageBelowLimit' => sanitize_text_field( strval( $param['messageBelowLimit'] ) ),
		);
	}
}
