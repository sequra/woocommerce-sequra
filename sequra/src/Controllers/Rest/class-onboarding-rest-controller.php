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
use SeQura\Core\BusinessLogic\AdminAPI\Disconnect\Requests\DisconnectRequest;
use SeQura\Core\BusinessLogic\AdminAPI\PromotionalWidgets\Requests\WidgetSettingsRequest;
use SeQura\WC\Services\Log\Interface_Logger_Service;
use SeQura\Core\Infrastructure\Utility\RegexProvider;
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
	private const PARAM_DEPLOYMENT_ID                              = 'deploymentId';
	private const PARAM_DEPLOYMENT                                 = 'deployment';
	private const PARAM_CONNECTION_DATA                            = 'connectionData';
	private const PARAM_SEND_STATISTICAL_DATA                      = 'sendStatisticalData';
	private const PARAM_DISPLAY_WIDGET_ON_PRODUCT_PAGE             = 'displayWidgetOnProductPage';
	private const PARAM_SHOW_INSTALLMENT_AMOUNT_IN_PRODUCT_LISTING = 'showInstallmentAmountInProductListing';
	private const PARAM_SHOW_INSTALLMENT_AMOUNT_IN_CART_PAGE       = 'showInstallmentAmountInCartPage';
	private const PARAM_WIDGET_STYLES                              = 'widgetStyles';
	private const PARAM_CUSTOM_LOCATIONS                           = 'customLocations';
	private const PARAM_CUSTOM_LOCATION_SEL_FOR_TARGET             = 'selForTarget';
	private const PARAM_CUSTOM_LOCATION_WIDGET_STYLES              = self::PARAM_WIDGET_STYLES;
	private const PARAM_CUSTOM_LOCATION_DISPLAY_WIDGET             = 'displayWidget';
	private const PARAM_CUSTOM_LOCATION_PRODUCT                    = 'product';
	private const PARAM_CUSTOM_LOCATION_CAMPAIGN                   = 'campaign';

	private const PARAM_SEL_FOR_PRICE             = 'productPriceSelector';
	private const PARAM_SEL_FOR_ALT_PRICE         = 'altProductPriceSelector';
	private const PARAM_SEL_FOR_ALT_PRICE_TRIGGER = 'altProductPriceTriggerSelector';
	private const PARAM_SEL_FOR_DEFAULT_LOCATION  = 'defaultProductLocationSelector';
	// Cart widget config.
	private const PARAM_CART_SEL_FOR_PRICE            = 'cartPriceSelector';
	private const PARAM_CART_SEL_FOR_DEFAULT_LOCATION = 'cartLocationSelector';
	private const PARAM_CART_WIDGET_ON_PAGE           = 'widgetOnCartPage';
	// Product listing widget config.
	private const PARAM_LISTING_SEL_FOR_PRICE    = 'listingPriceSelector';
	private const PARAM_LISTING_SEL_FOR_LOCATION = 'listingLocationSelector';
	private const PARAM_LISTING_WIDGET_ON_PAGE   = 'widgetOnListingPage';

	private const PARAM_IS_FULL_DISCONNECT = 'isFullDisconnect';

	/**
	 * Constructor.
	 *
	 * @param string $rest_namespace The namespace.
	 * @param Interface_Logger_Service $logger The logger service.
	 * @param RegexProvider $regex The regex provider.
	 */
	public function __construct( 
		$rest_namespace, 
		Interface_Logger_Service $logger,
		RegexProvider $regex 
	) {
		parent::__construct( $logger, $regex );
		$this->namespace = $rest_namespace;
		$this->rest_base = '/onboarding';
	}

	/**
	 * Register the API endpoints.
	 * 
	 * @return void
	 */
	public function register_routes() {
		$this->logger->log_debug( 'Hook executed', __FUNCTION__, __CLASS__ );
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

		$disconnect_args = array_merge(
			$store_id_args,
			array(
				self::PARAM_IS_FULL_DISCONNECT => $this->get_arg_bool(),
				self::PARAM_DEPLOYMENT_ID      => $this->get_arg_string(),
			)
		);
		
		$validate_data_args = array_merge(
			$data_args,
			array(
				self::PARAM_MERCHANT_ID => $this->get_arg_string( false ),
			)
		);

		$widget_args = array_merge(
			$store_id_args,
			array(
				self::PARAM_DISPLAY_WIDGET_ON_PRODUCT_PAGE => $this->get_arg_bool(),
				self::PARAM_SHOW_INSTALLMENT_AMOUNT_IN_PRODUCT_LISTING => $this->get_arg_bool(),
				self::PARAM_SHOW_INSTALLMENT_AMOUNT_IN_CART_PAGE => $this->get_arg_bool(),
				self::PARAM_WIDGET_STYLES                  => $this->get_arg_string(),
				self::PARAM_SEL_FOR_PRICE                  => $this->get_arg_string( true, '', array( $this, 'validate_required_widget_selector' ) ),
				self::PARAM_SEL_FOR_ALT_PRICE              => $this->get_arg_string( true, '', array( $this, 'validate_optional_widget_selector' ) ),
				self::PARAM_SEL_FOR_ALT_PRICE_TRIGGER      => $this->get_arg_string( true, '', array( $this, 'validate_optional_widget_selector' ) ),
				self::PARAM_SEL_FOR_DEFAULT_LOCATION       => $this->get_arg_string( true, '', array( $this, 'validate_required_widget_selector' ) ),
				
				self::PARAM_CUSTOM_LOCATIONS               => $this->get_arg_widget_location_list(),

				self::PARAM_CART_SEL_FOR_PRICE             => $this->get_arg_string( true, '', array( $this, 'validate_required_cart_widget_selector' ) ),
				self::PARAM_CART_SEL_FOR_DEFAULT_LOCATION  => $this->get_arg_string( true, '', array( $this, 'validate_required_cart_widget_selector' ) ),
				self::PARAM_CART_WIDGET_ON_PAGE            => $this->get_arg_string( true ),

				self::PARAM_LISTING_SEL_FOR_PRICE          => $this->get_arg_string( true, '', array( $this, 'validate_required_listing_widget_selector' ) ),
				self::PARAM_LISTING_SEL_FOR_LOCATION       => $this->get_arg_string( true, '', array( $this, 'validate_required_listing_widget_selector' ) ),
				self::PARAM_LISTING_WIDGET_ON_PAGE         => $this->get_arg_string( true ),
			)
		);

		$store_id = $this->url_param_pattern( self::PARAM_STORE_ID );

		$this->register_get( "data/{$store_id}", 'get_connection_data', $store_id_args );
		$this->register_post( "data/validate/{$store_id}", 'validate_connection_data', $validate_data_args );
		$this->register_post( "data/disconnect/{$store_id}", 'disconnect', $disconnect_args );
		$this->register_post( "data/connect/{$store_id}", 'connect', $store_id_args );
		
		$this->register_get( "widgets/{$store_id}", 'get_widgets', $store_id_args );
		$this->register_post( "widgets/{$store_id}", 'save_widgets', $widget_args );
		
		$this->register_get( "countries/selling/{$store_id}", 'get_selling_countries', $store_id_args );
		$this->register_get( "countries/{$store_id}", 'get_countries', $store_id_args );
		$this->register_post( "countries/{$store_id}", 'save_countries', $store_id_args );

		$this->register_get( "deployments/{$store_id}", 'get_deployments', $store_id_args );
		$this->register_get( "deployments/not-connected/{$store_id}", 'get_not_connected_deployments', $store_id_args );
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
		return \rest_ensure_response( $response );
	}

	/**
	 * Get deployments.
	 * 
	 * @param WP_REST_Request $request The request.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_deployments( WP_REST_Request $request ) {
		$response = null;
		try {
			$response = AdminAPI::get()
			->deployments( strval( $request->get_param( self::PARAM_STORE_ID ) ) )
			->getAllDeployments()
			->toArray();
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			$response = new WP_Error( 'error', $e->getMessage() );
		}
		return \rest_ensure_response( $response );
	}

	/**
	 * Get deployments.
	 * 
	 * @param WP_REST_Request $request The request.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_not_connected_deployments( WP_REST_Request $request ) {
		$response = null;
		try {
			$response = AdminAPI::get()
			->deployments( strval( $request->get_param( self::PARAM_STORE_ID ) ) )
			->getNotConnectedDeployments()
			->toArray();
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			$response = new WP_Error( 'error', $e->getMessage() );
		}
		return \rest_ensure_response( $response );
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
					strval( $request->get_param( self::PARAM_PASSWORD ) ),
					strval( $request->get_param( self::PARAM_DEPLOYMENT_ID ) )
				)
			)
			->toArray();
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			$response = new WP_Error( 'error', $e->getMessage() );
		}
		return \rest_ensure_response( $response );
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
			->disconnect(
				new DisconnectRequest(
					strval( $request->get_param( self::PARAM_DEPLOYMENT_ID ) ),
					(bool) $request->get_param( self::PARAM_IS_FULL_DISCONNECT )
				)
			)
			->toArray();
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			$response = new WP_Error( 'error', $e->getMessage() );
		}
		return \rest_ensure_response( $response );
	}

	/**
	 * Disconnects integration from the shop.
	 *
	 * @param WP_REST_Request $request The request.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function connect( WP_REST_Request $request ) {
		$response = null;
		try {
			/**
			 * Connection Data
			 * 
			 * @var array<array{merchantId?: string,
			 *     username?: string,
			 *     password?: string,
			 *     deployment?: string}> $connection_data_array
			 */
			$connection_data_array = $request->get_param( self::PARAM_CONNECTION_DATA ) ?? array();

			$connection_requests = array();
			foreach ( $connection_data_array as $conn_data ) {
				$connection_requests[] = new ConnectionRequest(
					strval( $request->get_param( self::PARAM_ENVIRONMENT ) ),
					strval( $conn_data[ self::PARAM_MERCHANT_ID ] ?? '' ),
					strval( $conn_data[ self::PARAM_USERNAME ] ?? '' ),
					strval( $conn_data[ self::PARAM_PASSWORD ] ?? '' ),
					strval( $conn_data[ self::PARAM_DEPLOYMENT ] ?? '' )
				);
			}

			$response = AdminAPI::get()
			->connection( strval( $request->get_param( self::PARAM_STORE_ID ) ) )
			->connect(
				new OnboardingRequest(
					$connection_requests,
					(bool) $request->get_param( self::PARAM_SEND_STATISTICAL_DATA ),
				)
			)
			->toArray();
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			$response = new WP_Error( 'error', $e->getMessage() );
		}
		return \rest_ensure_response( $response );
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
		return \rest_ensure_response( $response );
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
		return \rest_ensure_response( $response );
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
			/**
			 * Data
			 *
			 * @var array<int, array<string, string>> $data The data.
			 */
			$data = (array) json_decode( $request->get_body(), true );

			$response = AdminAPI::get()
			->countryConfiguration( strval( $request->get_param( self::PARAM_STORE_ID ) ) )
			->saveCountryConfigurations( new CountryConfigurationRequest( $data ) )
			->toArray();
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			$response = new WP_Error( 'error', $e->getMessage() );
		}
		return \rest_ensure_response( $response );
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
				$response[ self::PARAM_WIDGET_STYLES ] = $response['widgetConfiguration'];
				unset( $response['widgetConfiguration'] );
			}
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			$response = new WP_Error( 'error', $e->getMessage() );
		}
		return \rest_ensure_response( $response );
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
			$store_id = strval( $request->get_param( self::PARAM_STORE_ID ) );
			$response = AdminAPI::get()
			->widgetConfiguration( $store_id )
			->setWidgetSettings(
				new WidgetSettingsRequest(
					(bool) $request->get_param( self::PARAM_DISPLAY_WIDGET_ON_PRODUCT_PAGE ),
					(bool) $request->get_param( self::PARAM_SHOW_INSTALLMENT_AMOUNT_IN_PRODUCT_LISTING ),
					(bool) $request->get_param( self::PARAM_SHOW_INSTALLMENT_AMOUNT_IN_CART_PAGE ),
					strval( $request->get_param( self::PARAM_WIDGET_STYLES ) ),
					strval( $request->get_param( self::PARAM_SEL_FOR_PRICE ) ),
					strval( $request->get_param( self::PARAM_SEL_FOR_DEFAULT_LOCATION ) ),
					strval( $request->get_param( self::PARAM_CART_SEL_FOR_PRICE ) ),
					strval( $request->get_param( self::PARAM_CART_SEL_FOR_DEFAULT_LOCATION ) ),
					strval( $request->get_param( self::PARAM_CART_WIDGET_ON_PAGE ) ),
					strval( $request->get_param( self::PARAM_LISTING_WIDGET_ON_PAGE ) ),
					strval( $request->get_param( self::PARAM_LISTING_SEL_FOR_PRICE ) ),
					strval( $request->get_param( self::PARAM_LISTING_SEL_FOR_LOCATION ) ),
					strval( $request->get_param( self::PARAM_SEL_FOR_ALT_PRICE ) ),
					strval( $request->get_param( self::PARAM_SEL_FOR_ALT_PRICE_TRIGGER ) ),
					(array) $request->get_param( self::PARAM_CUSTOM_LOCATIONS )
				)
			);
			$response = $response->toArray();
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			$response = new WP_Error( 'error', $e->getMessage() );
		}
		return \rest_ensure_response( $response );
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
		return $this->validate_required_selector( self::PARAM_DISPLAY_WIDGET_ON_PRODUCT_PAGE, $param, $request, $key );
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
			'message'           => \sanitize_text_field( strval( $param['message'] ) ),
			'messageBelowLimit' => \sanitize_text_field( strval( $param['messageBelowLimit'] ) ),
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
				$location[ self::PARAM_CUSTOM_LOCATION_WIDGET_STYLES ], 
				$location[ self::PARAM_CUSTOM_LOCATION_DISPLAY_WIDGET ] 
			)
				|| ! is_string( $location[ self::PARAM_CUSTOM_LOCATION_SEL_FOR_TARGET ] )
				|| ! is_string( $location[ self::PARAM_CUSTOM_LOCATION_PRODUCT ] )
				|| ! is_string( $location[ self::PARAM_CUSTOM_LOCATION_WIDGET_STYLES ] )
				|| ! is_bool( $location[ self::PARAM_CUSTOM_LOCATION_DISPLAY_WIDGET ] )
				|| empty( $location[ self::PARAM_CUSTOM_LOCATION_PRODUCT ] )
				) {
				return false;
			}

			// check if exists another location with the same title and product.
			$product  = $location[ self::PARAM_CUSTOM_LOCATION_PRODUCT ];
			$campaign = $location[ self::PARAM_CUSTOM_LOCATION_CAMPAIGN ] ?? null;
			$found    = \array_filter(
				$param,
				function ( $loc ) use ( $product, $campaign ) {
					return isset( $loc[ self::PARAM_CUSTOM_LOCATION_PRODUCT ] )
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
				self::PARAM_CUSTOM_LOCATION_SEL_FOR_TARGET => \sanitize_text_field( strval( $location[ self::PARAM_CUSTOM_LOCATION_SEL_FOR_TARGET ] ) ),
				self::PARAM_CUSTOM_LOCATION_PRODUCT        => \sanitize_text_field( strval( $location[ self::PARAM_CUSTOM_LOCATION_PRODUCT ] ) ),
				self::PARAM_CUSTOM_LOCATION_CAMPAIGN       => isset( $location[ self::PARAM_CUSTOM_LOCATION_CAMPAIGN ] ) ? \sanitize_text_field( strval( $location[ self::PARAM_CUSTOM_LOCATION_CAMPAIGN ] ) ) : null,
				self::PARAM_CUSTOM_LOCATION_WIDGET_STYLES  => \sanitize_text_field( strval( $location[ self::PARAM_CUSTOM_LOCATION_WIDGET_STYLES ] ) ),
				self::PARAM_CUSTOM_LOCATION_DISPLAY_WIDGET => (bool) $location[ self::PARAM_CUSTOM_LOCATION_DISPLAY_WIDGET ],
			);
		}
		return $param;
	}
}
