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
use SeQura\WC\Services\Interface_Logger_Service;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST Onboarding Controller
 */
class Onboarding_REST_Controller extends REST_Controller {
	
	private const PARAM_ENVIRONMENT                                = 'environment';
	private const PARAM_USERNAME                                   = 'username';
	private const PARAM_PASSWORD                                   = 'password';
	private const PARAM_SEND_STATISTICAL_DATA                      = 'sendStatisticalData';
	private const PARAM_USE_WIDGETS                                = 'useWidgets';
	private const PARAM_ASSETS_KEY                                 = 'assetsKey';
	private const PARAM_DISPLAY_WIDGET_ON_PRODUCT_PAGE             = 'displayWidgetOnProductPage';
	private const PARAM_SHOW_INSTALLMENT_AMOUNT_IN_PRODUCT_LISTING = 'showInstallmentAmountInProductListing';
	private const PARAM_SHOW_INSTALLMENT_AMOUNT_IN_CART_PAGE       = 'showInstallmentAmountInCartPage';
	private const PARAM_MINI_WIDGET_SELECTOR                       = 'miniWidgetSelector';
	private const PARAM_WIDGET_STYLES                              = 'widgetStyles';
	private const PARAM_WIDGET_LABELS                              = 'widgetLabels';
	private const PARAM_SEL_FOR_PRICE                              = 'selForPrice';
	private const PARAM_SEL_FOR_ALT_PRICE                          = 'selForAltPrice';
	private const PARAM_SEL_FOR_ALT_PRICE_TRIGGER                  = 'selForAltPriceTrigger';
	private const PARAM_SEL_FOR_DEFAULT_LOCATION                   = 'selForDefaultLocation';
	private const PARAM_CUSTOM_LOCATIONS                           = 'customLocations';

	/**
	 * Constructor.
	 *
	 * @param string $rest_namespace The namespace.
	 * @param Interface_Logger_Service $logger The logger service.
	 */
	public function __construct( $rest_namespace, Interface_Logger_Service $logger ) {
		parent::__construct( $logger );
		$this->namespace = $rest_namespace;
		$this->rest_base = '/onboarding';
	}

	/**
	 * Register the API endpoints.
	 */
	public function register_routes(): void {
		$this->logger->log_info( 'Hook executed', __FUNCTION__, __CLASS__ );
		$store_id_args = array( self::PARAM_STORE_ID => $this->get_arg_string() );

		$data_args = array_merge(
			$store_id_args,
			array(
				self::PARAM_ENVIRONMENT           => array_merge(
					$this->get_arg_string(),
					array( 'enum' => array( 'sandbox', 'production' ) )
				),
				self::PARAM_USERNAME              => $this->get_arg_string(),
				self::PARAM_PASSWORD              => $this->get_arg_string(),
				self::PARAM_SEND_STATISTICAL_DATA => $this->get_arg_bool(),
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
				self::PARAM_USE_WIDGETS                    => $this->get_arg_bool(),
				self::PARAM_ASSETS_KEY                     => $this->get_arg_string(),
				self::PARAM_DISPLAY_WIDGET_ON_PRODUCT_PAGE => $this->get_arg_bool(),
				self::PARAM_SHOW_INSTALLMENT_AMOUNT_IN_PRODUCT_LISTING => $this->get_arg_bool(),
				self::PARAM_SHOW_INSTALLMENT_AMOUNT_IN_CART_PAGE => $this->get_arg_bool(),
				self::PARAM_MINI_WIDGET_SELECTOR           => $this->get_arg_string( true, '', '__return_true' ), // TODO: Add this field to form in the UI.
				self::PARAM_WIDGET_STYLES                  => $this->get_arg_string(),
				self::PARAM_WIDGET_LABELS                  => array(
					'default'           => array(
						'message'           => '',
						'messageBelowLimit' => '',
					),
					'required'          => false,
					'validate_callback' => array( $this, 'validate_widget_labels' ),
					'sanitize_callback' => array( $this, 'sanitize_widget_labels' ),
				),
				self::PARAM_SEL_FOR_PRICE                  => $this->get_arg_string( true, null, array( $this, 'validate_required_widget_selector' ) ),
				self::PARAM_SEL_FOR_ALT_PRICE              => $this->get_arg_string( true, null, array( $this, 'validate_optional_widget_selector' ) ),
				self::PARAM_SEL_FOR_ALT_PRICE_TRIGGER      => $this->get_arg_string( true, null, array( $this, 'validate_optional_widget_selector' ) ),
				self::PARAM_SEL_FOR_DEFAULT_LOCATION       => $this->get_arg_string( true, null, array( $this, 'validate_required_widget_selector' ) ),
				self::PARAM_CUSTOM_LOCATIONS               => $this->get_arg_widget_location_list(),

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
					strval( $request->get_param( self::PARAM_ENVIRONMENT ) ),
					strval( $request->get_param( self::PARAM_MERCHANT_ID ) ),
					strval( $request->get_param( self::PARAM_USERNAME ) ),
					strval( $request->get_param( self::PARAM_PASSWORD ) )
				)
			);
			$response = $response->toArray();
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
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
					strval( $request->get_param( self::PARAM_ENVIRONMENT ) ),
					strval( $request->get_param( self::PARAM_USERNAME ) ),
					strval( $request->get_param( self::PARAM_PASSWORD ) ),
					(bool) $request->get_param( self::PARAM_SEND_STATISTICAL_DATA ),
					null === $request->get_param( self::PARAM_MERCHANT_ID ) ? null : strval( $request->get_param( self::PARAM_MERCHANT_ID ) )
				)
			);

			$is_ok    = $response->isSuccessful();
			$response = $response->toArray();

			if ( ! $is_ok ) {
				throw new \Exception( $response['errorMessage'] );
			}
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
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
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
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
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
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
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
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
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
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
				$response[ self::PARAM_WIDGET_LABELS ]['message']           = ! empty( $response[ self::PARAM_WIDGET_LABELS ]['messages'] ) ?
				reset( $response[ self::PARAM_WIDGET_LABELS ]['messages'] ) : '';
				$response[ self::PARAM_WIDGET_LABELS ]['messageBelowLimit'] = ! empty( $response[ self::PARAM_WIDGET_LABELS ]['messagesBelowLimit'] ) ?
				reset( $response[ self::PARAM_WIDGET_LABELS ]['messagesBelowLimit'] ) : '';
				$response[ self::PARAM_WIDGET_STYLES ]                      = $response['widgetConfiguration'];
				unset( $response[ self::PARAM_WIDGET_LABELS ]['messages'], $response[ self::PARAM_WIDGET_LABELS ]['messagesBelowLimit'] );
				unset( $response['widgetConfiguration'] );
			}
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
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
					(bool) $request->get_param( self::PARAM_USE_WIDGETS ),
					null === $request->get_param( self::PARAM_ASSETS_KEY ) ? null : strval( $request->get_param( self::PARAM_ASSETS_KEY ) ),
					(bool) $request->get_param( self::PARAM_DISPLAY_WIDGET_ON_PRODUCT_PAGE ),
					(bool) $request->get_param( self::PARAM_SHOW_INSTALLMENT_AMOUNT_IN_PRODUCT_LISTING ),
					(bool) $request->get_param( self::PARAM_SHOW_INSTALLMENT_AMOUNT_IN_CART_PAGE ),
					strval( $request->get_param( self::PARAM_MINI_WIDGET_SELECTOR ) ),
					strval( $request->get_param( self::PARAM_WIDGET_STYLES ) ),
					(array) $messages,
					(array) $messages_below_limit,
					strval( $request->get_param( self::PARAM_SEL_FOR_PRICE ) ),
					strval( $request->get_param( self::PARAM_SEL_FOR_ALT_PRICE ) ),
					strval( $request->get_param( self::PARAM_SEL_FOR_ALT_PRICE_TRIGGER ) ),
					strval( $request->get_param( self::PARAM_SEL_FOR_DEFAULT_LOCATION ) ),
					(array) $request->get_param( self::PARAM_CUSTOM_LOCATIONS )
				)
			)
			->toArray();
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
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
	 * Validate if required widget selector is valid.
	 * 
	 * @param mixed $param The parameter.
	 * @param WP_REST_Request $request The request.
	 * @param string $key The key.
	 */
	public function validate_required_widget_selector( $param, $request, $key ): bool {
		
		$use_widgets = (bool) $request->get_param( self::PARAM_USE_WIDGETS );
		if ( ! $use_widgets ) {
			return null === $param || is_string( $param );
		}
		
		return null !== $param 
		&& is_string( $param )
		&& '' !== trim( $param );
	}

	/**
	 * Validate if optional widget selector is valid.
	 * 
	 * @param mixed $param The parameter.
	 * @param WP_REST_Request $request The request.
	 * @param string $key The key.
	 */
	public function validate_optional_widget_selector( $param, $request, $key ): bool {
		return null === $param || is_string( $param );
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

	/**
	 * Get argument structure for the widget location list.
	 * 
	 * @param bool $required      If the argument is required.
	 * @param mixed $default_value The default value. Null will be ignored.
	 * @param callable $validate The validate callback. Leave null to use the default.
	 * @param callable $sanitize The sanitize callback. Leave null to use the default.
	 * @return mixed[]
	 */
	protected function get_arg_widget_location_list( $required = true, $default_value = array(), $validate = null, $sanitize = null ): array {
		return array_merge(
			$this->get_arg( $required, $default_value ),
			array(
				'validate_callback' => null === $validate ? array( $this, 'validate_widget_location_list' ) : $validate,
				'sanitize_callback' => null === $sanitize ? array( $this, 'sanitize_widget_location_list' ) : $sanitize,
			)
		);
	}

	/**
	 * Validate if widget location list is valid.
	 * 
	 * @param mixed $param The parameter.
	 * @param WP_REST_Request $request The request.
	 * @param string $key The key.
	 */
	public function validate_widget_location_list( $param, $request, $key ): bool {
		if ( ! is_array( $param ) ) {
			return false;
		}
		foreach ( $param as $location ) {
			if ( ! isset( $location['selForTarget'], $location['product'], $location['country'] )
				|| ! is_string( $location['selForTarget'] )
				|| ! is_string( $location['product'] )
				|| ! is_string( $location['country'] )
				|| empty( $location['selForTarget'] )
				|| empty( $location['product'] )
				|| empty( $location['country'] )
				) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Sanitize widget location list.
	 * 
	 * @param array<int, array<string, string>> $param The parameter.
	 * @return array<int, array<string, string>>
	 */
	public function sanitize_widget_location_list( $param ): array {
		foreach ( $param as &$location ) {
			$location = array(
				'selForTarget' => sanitize_text_field( strval( $location['selForTarget'] ) ),
				'product'      => sanitize_text_field( strval( $location['product'] ) ),
				'country'      => sanitize_text_field( strval( $location['country'] ) ),
			);
		}
		return $param;
	}
}
