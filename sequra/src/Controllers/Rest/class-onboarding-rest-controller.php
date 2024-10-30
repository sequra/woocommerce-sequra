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
use SeQura\WC\Core\Extension\BusinessLogic\AdminAPI\PromotionalWidgets\Requests\Widget_Settings_Request;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\PromotionalWidgets\Models\Mini_Widget;
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
	private const PARAM_CUSTOM_LOCATION_SEL_FOR_TARGET             = 'selForTarget';
	private const PARAM_CUSTOM_LOCATION_WIDGET_STYLES              = 'widgetStyles';
	private const PARAM_CUSTOM_LOCATION_DISPLAY_WIDGET             = 'displayWidget';
	private const PARAM_CUSTOM_LOCATION_COUNTRY                    = 'country';
	private const PARAM_CUSTOM_LOCATION_PRODUCT                    = 'product';
	private const PARAM_CUSTOM_LOCATION_CAMPAIGN                   = 'campaign';

	// Cart widget config.
	private const PARAM_CART_SEL_FOR_PRICE            = 'selForCartPrice';
	private const PARAM_CART_SEL_FOR_DEFAULT_LOCATION = 'selForCartLocation';
	// Product listing widget config.
	private const PARAM_LISTING_SEL_FOR_PRICE    = 'selForListingPrice';
	private const PARAM_LISTING_SEL_FOR_LOCATION = 'selForListingLocation';

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
				self::PARAM_ASSETS_KEY                     => $this->get_arg_string( true, '', array( $this, 'validate_assets_key' ) ),
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

				self::PARAM_CART_SEL_FOR_PRICE             => $this->get_arg_string( true, null, array( $this, 'validate_required_cart_widget_selector' ) ),
				self::PARAM_CART_SEL_FOR_DEFAULT_LOCATION  => $this->get_arg_string( true, null, array( $this, 'validate_required_cart_widget_selector' ) ),

				self::PARAM_LISTING_SEL_FOR_PRICE          => $this->get_arg_string( true, null, array( $this, 'validate_required_listing_widget_selector' ) ),
				self::PARAM_LISTING_SEL_FOR_LOCATION       => $this->get_arg_string( true, null, array( $this, 'validate_required_listing_widget_selector' ) ),
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
		$response = null;
		try {
			$response = AdminAPI::get()
			->connection( strval( $request->get_param( self::PARAM_STORE_ID ) ) )
			->getOnboardingData()
			->toArray();
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			$response = new WP_Error( 'error', $e->getMessage() );
		}
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
	 * Check if the assets key is valid.
	 * 
	 * @param mixed $param The parameter.
	 * @param WP_REST_Request $request The request.
	 * @param string $key The key.
	 */
	public function validate_assets_key( $param, $request, $key ): bool {
		return is_string( $param ) 
		&& ( ! boolval( $request->get_param( self::PARAM_USE_WIDGETS ) ) || '' !== trim( $param ) );
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
			//phpcs:disable Squiz.Commenting.InlineComment.InvalidEndChar
			// $labels               = $request->get_param( 'widgetLabels' );
			// $messages             = $labels['message'] ? array( $storeConfig->getLocale() => $labels['message'] ) : array();
			// $messages_below_limit = $labels['messageBelowLimit'] ? array( $storeConfig->getLocale() => $labels['messageBelowLimit'] ) : array();
			//phpcs:enable
			$messages             = array();
			$messages_below_limit = array();

			$store_id = strval( $request->get_param( self::PARAM_STORE_ID ) );

			/**
			 * Filter the cart mini widgets.
			 * 
			 * @since 3.0.0
			 * @var Mini_Widget[] $cart_mini_widgets The cart mini widgets.
			 */
			$cart_mini_widgets = apply_filters( 'sequra_widget_settings_cart_mini_widgets', array(), $store_id );
			if ( ! is_array( $cart_mini_widgets ) ) {
				$this->logger->log_debug( 'Invalid cart mini widgets. ' . Mini_Widget::class . '[] is expected', __FUNCTION__, __CLASS__ );
				$cart_mini_widgets = array();
			}
			foreach ( $cart_mini_widgets as $mini_widget ) {
				if ( ! $mini_widget instanceof Mini_Widget ) {
					$this->logger->log_debug( 'Invalid cart mini widgets. ' . Mini_Widget::class . '[] is expected', __FUNCTION__, __CLASS__ );
					$cart_mini_widgets = array();
					break;
				}
			}

			/**
			 * Filter the product listing mini widgets.
			 * 
			 * @since 3.0.0
			 * @var Mini_Widget[] $listing_mini_widgets The product listing mini widgets.
			 */
			$listing_mini_widgets = apply_filters( 'sequra_widget_settings_product_listing_mini_widgets', array(), $store_id );
			if ( ! is_array( $listing_mini_widgets ) ) {
				$this->logger->log_debug( 'Invalid product listing mini widgets. ' . Mini_Widget::class . '[] is expected', __FUNCTION__, __CLASS__ );
				$listing_mini_widgets = array();
			}
			foreach ( $listing_mini_widgets as $mini_widget ) {
				if ( ! $mini_widget instanceof Mini_Widget ) {
					$this->logger->log_debug( 'Invalid product listing mini widgets. ' . Mini_Widget::class . '[] is expected', __FUNCTION__, __CLASS__ );
					$listing_mini_widgets = array();
					break;
				}
			}

			$response = AdminAPI::get()
			->widgetConfiguration( $store_id )
			->setWidgetSettings(
				new Widget_Settings_Request(
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
					(array) $request->get_param( self::PARAM_CUSTOM_LOCATIONS ),
					strval( $request->get_param( self::PARAM_CART_SEL_FOR_PRICE ) ),
					strval( $request->get_param( self::PARAM_CART_SEL_FOR_DEFAULT_LOCATION ) ),
					$cart_mini_widgets,
					strval( $request->get_param( self::PARAM_LISTING_SEL_FOR_PRICE ) ),
					strval( $request->get_param( self::PARAM_LISTING_SEL_FOR_LOCATION ) ),
					$listing_mini_widgets
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
	 * Validate if required selector is valid.
	 * 
	 * @param mixed $param The parameter.
	 */
	private function validate_required_selector( string $dependant_param_key, $param, WP_REST_Request $request, string $key ): bool {
		
		$use_widgets = (bool) $request->get_param( $dependant_param_key );
		if ( ! $use_widgets ) {
			return null === $param || is_string( $param );
		}
		
		return null !== $param 
		&& is_string( $param )
		&& '' !== trim( $param );
	}

	/**
	 * Validate if required widget selector is valid.
	 * 
	 * @param mixed $param The param.
	 */
	public function validate_required_widget_selector( $param, WP_REST_Request $request, string $key ): bool {
		return $this->validate_required_selector( self::PARAM_USE_WIDGETS, $param, $request, $key );
	}

	/**
	 * Validate if required cart widget selector is valid.
	 * 
	 * @param mixed $param The param.
	 */
	public function validate_required_cart_widget_selector( $param, WP_REST_Request $request, string $key ): bool {
		return $this->validate_required_selector( self::PARAM_SHOW_INSTALLMENT_AMOUNT_IN_CART_PAGE, $param, $request, $key );
	}

	/**
	 * Validate if required listing widget selector is valid.
	 * 
	 * @param mixed $param The param.
	 */
	public function validate_required_listing_widget_selector( $param, WP_REST_Request $request, string $key ): bool {
		return $this->validate_required_selector( self::PARAM_SHOW_INSTALLMENT_AMOUNT_IN_PRODUCT_LISTING, $param, $request, $key );
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
			if ( ! isset( 
				$location[ self::PARAM_CUSTOM_LOCATION_SEL_FOR_TARGET ], 
				$location[ self::PARAM_CUSTOM_LOCATION_PRODUCT ], 
				$location[ self::PARAM_CUSTOM_LOCATION_COUNTRY ],  
				$location[ self::PARAM_CUSTOM_LOCATION_WIDGET_STYLES ], 
				$location[ self::PARAM_CUSTOM_LOCATION_DISPLAY_WIDGET ] 
			)
				|| ! is_string( $location[ self::PARAM_CUSTOM_LOCATION_SEL_FOR_TARGET ] )
				|| ! is_string( $location[ self::PARAM_CUSTOM_LOCATION_PRODUCT ] )
				|| ! is_string( $location[ self::PARAM_CUSTOM_LOCATION_COUNTRY ] )
				|| ! is_string( $location[ self::PARAM_CUSTOM_LOCATION_WIDGET_STYLES ] )
				|| ! is_bool( $location[ self::PARAM_CUSTOM_LOCATION_DISPLAY_WIDGET ] )
				|| empty( $location[ self::PARAM_CUSTOM_LOCATION_PRODUCT ] )
				|| empty( $location[ self::PARAM_CUSTOM_LOCATION_COUNTRY ] )
				) {
				return false;
			}

			// check if exists another location with the same country title and product.
			$country  = $location[ self::PARAM_CUSTOM_LOCATION_COUNTRY ];
			$product  = $location[ self::PARAM_CUSTOM_LOCATION_PRODUCT ];
			$campaign = $location[ self::PARAM_CUSTOM_LOCATION_CAMPAIGN ] ?? null;
			$found    = array_filter(
				$param,
				function ( $loc ) use ( $country, $product, $campaign ) {
					return isset( $loc[ self::PARAM_CUSTOM_LOCATION_PRODUCT ], $loc[ self::PARAM_CUSTOM_LOCATION_COUNTRY ] )
					&& $loc[ self::PARAM_CUSTOM_LOCATION_COUNTRY ] === $country 
					&& $loc[ self::PARAM_CUSTOM_LOCATION_PRODUCT ] === $product
					&& ( $loc[ self::PARAM_CUSTOM_LOCATION_CAMPAIGN ] ?? null ) === $campaign;
				}
			);
			if ( count( $found ) > 1 ) {
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
				self::PARAM_CUSTOM_LOCATION_SEL_FOR_TARGET => sanitize_text_field( strval( $location[ self::PARAM_CUSTOM_LOCATION_SEL_FOR_TARGET ] ) ),
				self::PARAM_CUSTOM_LOCATION_PRODUCT        => sanitize_text_field( strval( $location[ self::PARAM_CUSTOM_LOCATION_PRODUCT ] ) ),
				self::PARAM_CUSTOM_LOCATION_COUNTRY        => sanitize_text_field( strval( $location[ self::PARAM_CUSTOM_LOCATION_COUNTRY ] ) ),
				self::PARAM_CUSTOM_LOCATION_CAMPAIGN       => isset( $location[ self::PARAM_CUSTOM_LOCATION_CAMPAIGN ] ) ? sanitize_text_field( strval( $location[ self::PARAM_CUSTOM_LOCATION_CAMPAIGN ] ) ) : null,
				self::PARAM_CUSTOM_LOCATION_WIDGET_STYLES  => sanitize_text_field( strval( $location[ self::PARAM_CUSTOM_LOCATION_WIDGET_STYLES ] ) ),
				self::PARAM_CUSTOM_LOCATION_DISPLAY_WIDGET => (bool) $location[ self::PARAM_CUSTOM_LOCATION_DISPLAY_WIDGET ],
			);
		}
		return $param;
	}
}
