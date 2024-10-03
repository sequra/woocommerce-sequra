<?php
/**
 * REST Settings Controller
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers/Rest
 */

namespace SeQura\WC\Controllers\Rest;

use SeQura\Core\BusinessLogic\AdminAPI\AdminAPI;
use SeQura\Core\BusinessLogic\AdminAPI\OrderStatusSettings\Requests\OrderStatusSettingsRequest;
use SeQura\Core\BusinessLogic\Domain\Order\OrderStates;
use SeQura\WC\Core\Extension\BusinessLogic\AdminAPI\GeneralSettings\Requests\General_Settings_Request;
use SeQura\WC\Services\Interface_Logger_Service;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST Settings Controller
 */
class General_Settings_REST_Controller extends REST_Controller {

	const PARAM_SEND_ORDER_REPORTS_PERIODICALLY_TO_SEQURA = 'sendOrderReportsPeriodicallyToSeQura';
	const PARAM_SHOW_SEQURA_CHECKOUT_AS_HOSTED_PAGE       = 'showSeQuraCheckoutAsHostedPage';
	const PARAM_ALLOWED_IP_ADDRESSES                      = 'allowedIPAddresses';
	const PARAM_EXCLUDED_PRODUCTS                         = 'excludedProducts';
	const PARAM_EXCLUDED_CATEGORIES                       = 'excludedCategories';
	const PARAM_ENABLED_FOR_SERVICES                      = 'enabledForServices';
	const PARAM_ALLOW_FIRST_SERVICE_PAYMENT_DELAY         = 'allowFirstServicePaymentDelay';
	const PARAM_ALLOW_SERVICE_REG_ITEMS                   = 'allowServiceRegItems';
	const PARAM_DEFAULT_SERVICES_END_DATE                 = 'defaultServicesEndDate';
	
	/**
	 * Constructor.
	 *
	 * @param string            $rest_namespace The namespace.
	 * @param Interface_Logger_Service $logger         The logger service.
	 */
	public function __construct( $rest_namespace, Interface_Logger_Service $logger ) {
		parent::__construct( $logger );
		$this->namespace = $rest_namespace;
		$this->rest_base = '/settings';
	}

	/**
	 * Register the API endpoints.
	 */
	public function register_routes(): void {
		$this->logger->log_info( 'Hook executed', __FUNCTION__, __CLASS__ );
		$store_id_args = array( self::PARAM_STORE_ID => $this->get_arg_string() );
		$general_args  = array_merge(
			$store_id_args,
			array(
				self::PARAM_SEND_ORDER_REPORTS_PERIODICALLY_TO_SEQURA => $this->get_arg_bool(),
				self::PARAM_SHOW_SEQURA_CHECKOUT_AS_HOSTED_PAGE => $this->get_arg_bool( false, false ),
				self::PARAM_ALLOWED_IP_ADDRESSES      => $this->get_arg_ip_list( true, array() ),
				self::PARAM_EXCLUDED_PRODUCTS         => array(
					'required'          => true,
					'validate_callback' => array( $this, 'validate_array_of_product_sku' ),
					'sanitize_callback' => array( $this, 'sanitize_array_sanitize_text_field' ),
				),
				self::PARAM_EXCLUDED_CATEGORIES       => array(
					'required'          => false,
					'validate_callback' => array( $this, 'validate_array_of_product_cat' ),
					'sanitize_callback' => array( $this, 'sanitize_array_of_ids' ),
				),
				self::PARAM_ENABLED_FOR_SERVICES      => $this->get_arg_bool( false, false ),
				self::PARAM_ALLOW_FIRST_SERVICE_PAYMENT_DELAY => $this->get_arg_bool( false, true ),
				self::PARAM_ALLOW_SERVICE_REG_ITEMS   => $this->get_arg_bool( false, true ),
				self::PARAM_DEFAULT_SERVICES_END_DATE => $this->get_arg_string( false, 'P1Y', array( $this, 'validate_time_duration' ) ),
			)
		);

		$store_id = $this->url_param_pattern( self::PARAM_STORE_ID );

		$this->register_get( 'current-store', 'get_current_store' );
		$this->register_get( "version/{$store_id}", 'get_version', $store_id_args );
		$this->register_get( "stores/{$store_id}", 'get_stores', $store_id_args );
		$this->register_get( "state/{$store_id}", 'get_state', $store_id_args );
		$this->register_get( "shop-categories/{$store_id}", 'get_shop_categories', $store_id_args );
		$this->register_get( "shop-name/{$store_id}", 'get_shop_name', $store_id_args );
		
		$this->register_get( "general/{$store_id}", 'get_general', $store_id_args );
		$this->register_post( "general/{$store_id}", 'save_general', $general_args );
		
		$this->register_get( "order-status/list/{$store_id}", 'get_list_order_status', $store_id_args );
		$this->register_get( "order-status/{$store_id}", 'get_order_status', $store_id_args );
		$this->register_post( "order-status/{$store_id}", 'save_order_status', $store_id_args );
	}

	/**
	 * GET current store.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_current_store() {
		$response = null;
		try {
			$response = AdminAPI::get()
			->store( (string) get_current_blog_id() )
			->getCurrentStore()
			->toArray();
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			$response = new WP_Error( 'error', $e->getMessage() );
		}
		return rest_ensure_response( $response );
	}

	/**
	 * GET version.
	 * 
	 * @param WP_REST_Request $request The request.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_version( WP_REST_Request $request ) {
		$response = null;
		try {
			$response = AdminAPI::get()
			->integration( strval( $request->get_param( self::PARAM_STORE_ID ) ) )
			->getVersion()
			->toArray();
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			$response = new WP_Error( 'error', $e->getMessage() );
		}
		return rest_ensure_response( $response );
	}

	/**
	 * GET stores.
	 * 
	 * @param WP_REST_Request $request The request.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_stores( WP_REST_Request $request ) {
		$response = null;
		try {
			$response = AdminAPI::get()
			->store( strval( $request->get_param( self::PARAM_STORE_ID ) ) )
			->getStores()
			->toArray();
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			$response = new WP_Error( 'error', $e->getMessage() );
		}
		return rest_ensure_response( $response );
	}

	/**
	 * GET state.
	 * 
	 * @param WP_REST_Request $request The request.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_state( WP_REST_Request $request ) {
		$response = null;
		try {
			$response = AdminAPI::get()
			->integration( strval( $request->get_param( self::PARAM_STORE_ID ) ) )
			->getUIState( true ) // Pass false if the Onboarding does not configure widgets. 
			->toArray();
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			$response = new WP_Error( 'error', $e->getMessage() );
		}
		return rest_ensure_response( $response );
	}

	/**
	 * GET general settings.
	 * 
	 * @param WP_REST_Request $request The request.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_general( WP_REST_Request $request ) {
		$response = null;
		try {
			$response = AdminAPI::get()
			->generalSettings( strval( $request->get_param( self::PARAM_STORE_ID ) ) )
			->getGeneralSettings()
			->toArray();
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			$response = new WP_Error( 'error', $e->getMessage() );
		}
		return rest_ensure_response( $response );
	}

	/**
	 * GET general settings.
	 * 
	 * @param WP_REST_Request $request The request.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_shop_name( WP_REST_Request $request ) {
		$response = null;
		try {
			$response = AdminAPI::get()
			->integration( strval( $request->get_param( self::PARAM_STORE_ID ) ) )
			->getShopName()
			->toArray();
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			$response = new WP_Error( 'error', $e->getMessage() );
		}
		return rest_ensure_response( $response );
	}

	/**
	 * Save general settings.
	 * 
	 * @throws \Exception
	 * @param WP_REST_Request $request The request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function save_general( WP_REST_Request $request ) {
		$response = null;
		try {
			$response = AdminAPI::get()
			->generalSettings( strval( $request->get_param( self::PARAM_STORE_ID ) ) )
			->saveGeneralSettings(
				new General_Settings_Request(
					(bool) $request->get_param( self::PARAM_SEND_ORDER_REPORTS_PERIODICALLY_TO_SEQURA ),
					(bool) $request->get_param( self::PARAM_SHOW_SEQURA_CHECKOUT_AS_HOSTED_PAGE ),
					(array) $request->get_param( self::PARAM_ALLOWED_IP_ADDRESSES ),
					(array) $request->get_param( self::PARAM_EXCLUDED_PRODUCTS ),
					(array) $request->get_param( self::PARAM_EXCLUDED_CATEGORIES ),
					(bool) $request->get_param( self::PARAM_ENABLED_FOR_SERVICES ),
					(bool) $request->get_param( self::PARAM_ALLOW_FIRST_SERVICE_PAYMENT_DELAY ),
					(bool) $request->get_param( self::PARAM_ALLOW_SERVICE_REG_ITEMS ),
					strval( $request->get_param( self::PARAM_DEFAULT_SERVICES_END_DATE ) )
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
	 * GET shop categories.
	 * 
	 * @param WP_REST_Request $request The request.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_shop_categories( WP_REST_Request $request ) {
		$response = null;
		try {
			$response = AdminAPI::get()
			->generalSettings( strval( $request->get_param( self::PARAM_STORE_ID ) ) )
			->getShopCategories()
			->toArray();
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			$response = new WP_Error( 'error', $e->getMessage() );
		}
		return rest_ensure_response( $response );
	}

	/**
	 * GET Order Status List.
	 * 
	 * @param WP_REST_Request $request The request.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_list_order_status( WP_REST_Request $request ) {
		$response = null;
		try {
			$response = AdminAPI::get()
			->orderStatusSettings( strval( $request->get_param( self::PARAM_STORE_ID ) ) )
			->getShopOrderStatuses()
			->toArray();
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			$response = new WP_Error( 'error', $e->getMessage() );
		}
		return rest_ensure_response( $response );
	}

	/**
	 * GET Order Status settings.
	 * 
	 * @param WP_REST_Request $request The request.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_order_status( WP_REST_Request $request ) {
		$response = null;
		try {
			$response = AdminAPI::get()
			->orderStatusSettings( strval( $request->get_param( self::PARAM_STORE_ID ) ) )
			->getOrderStatusSettings()
			->toArray();
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			$response = new WP_Error( 'error', $e->getMessage() );
		}
		return rest_ensure_response( $response );
	}

	/**
	 * POST Order Status settings.
	 * 
	 * @param WP_REST_Request $request The request.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function save_order_status( WP_REST_Request $request ) {
		if ( ! $this->validate_order_status( $request ) ) {
			return new WP_REST_Response( 'Invalid data', 400 );
		}

		$response = null;
		try {
			$response = AdminAPI::get()
			->orderStatusSettings( strval( $request->get_param( self::PARAM_STORE_ID ) ) )
			->saveOrderStatusSettings(
				new OrderStatusSettingsRequest( (array) json_decode( $request->get_body(), true ) )
			)
			->toArray();
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			$response = new WP_Error( 'error', $e->getMessage() );
		}
		return rest_ensure_response( $response );
	}

	/**
	 * Validate if the parameter is a boolean.
	 * 
	 * @param mixed $param The parameter.
	 * @param WP_REST_Request $request The request.
	 * @param string $key The key.
	 */
	public function validate_array_of_product_sku( $param, $request, $key ): bool {
		if ( ! is_array( $param ) ) {
			return false;
		}

		foreach ( $param as $sku ) {
			if ( ! is_string( $sku ) || '' === trim( $sku ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Validate if the parameter is a boolean.
	 * 
	 * @param mixed $param The parameter.
	 * @param WP_REST_Request $request The request.
	 * @param string $key The key.
	 */
	public function validate_array_of_product_cat( $param, $request, $key ): bool {
		if ( ! is_array( $param ) ) {
			return false;
		}

		foreach ( $param as $term_id ) {
			if ( ! is_numeric( $term_id ) || empty( $term_id ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Validate if order status payload is valid.
	 * 
	 * @param WP_REST_Request $request The request.
	 */
	public function validate_order_status( WP_REST_Request $request ): bool {
		try {
			$data = json_decode( $request->get_body(), true );
			if ( ! is_array( $data ) ) {
				return false;
			}
			$allowed_shop_statuses   = AdminAPI::get()
			->orderStatusSettings( strval( $request->get_param( self::PARAM_STORE_ID ) ) )
			->getShopOrderStatuses()
			->toArray();
			$allowed_shop_statuses   = array_column( $allowed_shop_statuses, 'id' );
			$allowed_sequra_statuses = OrderStates::toArray();

			foreach ( $data as $status_map ) {
				if ( ! isset( $status_map['sequraStatus'] ) 
				|| ! isset( $status_map['shopStatus'] ) 
				|| ! is_string( $status_map['sequraStatus'] ) 
				|| ! is_string( $status_map['shopStatus'] )
				|| ! in_array( $status_map['sequraStatus'], $allowed_sequra_statuses, true )
				|| ! in_array( $status_map['shopStatus'], $allowed_shop_statuses, true )
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
	 * Sanitize an array of ids.
	 * 
	 * @param mixed[] $param The parameter.
	 * @return mixed[]
	 */
	public function sanitize_array_of_ids( array $param ): array {
		foreach ( $param as &$val ) {
			$val = (string) abs( intval( $val ) );
		}
		return $param;
	}
}
