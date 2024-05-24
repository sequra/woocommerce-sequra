<?php
/**
 * REST Settings Controller
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers/Rest
 */

namespace SeQura\WC\Controllers\Rest;

use SeQura\Core\BusinessLogic\AdminAPI\AdminAPI;
use SeQura\Core\BusinessLogic\AdminAPI\GeneralSettings\Requests\GeneralSettingsRequest;
use SeQura\WC\Services\Core\Configuration;

/**
 * REST Settings Controller
 */
class General_Settings_REST_Controller extends REST_Controller {

	/**
	 * Constructor.
	 *
	 * @param string            $rest_namespace The namespace.
	 * @param Configuration $configuration The configuration.
	 */
	public function __construct( $rest_namespace ) {
		$this->namespace = $rest_namespace;
		$this->rest_base = '/settings';
	}

	/**
	 * Register the API endpoints.
	 */
	public function register_routes() {
		$store_id_args = array( self::PARAM_STORE_ID => $this->get_arg_string() );
		$general_args  = array_merge(
			$store_id_args,
			array(
				'sendOrderReportsPeriodicallyToSeQura' => $this->get_arg_bool(),
				'showSeQuraCheckoutAsHostedPage'       => $this->get_arg_bool(),
				'allowedIPAddresses'                   => $this->get_arg_ip_list( true, array() ),
				'excludedProducts'                     => array(
					'required'          => true,
					'validate_callback' => array( $this, 'validate_array_of_product_sku' ),
					'sanitize_callback' => array( $this, 'sanitize_array_sanitize_text_field' ),
				),
				'excludedCategories'                   => array(
					'required'          => false,
					'validate_callback' => array( $this, 'validate_array_of_product_cat' ),
					'sanitize_callback' => array( $this, 'sanitize_array_of_ids' ),
				),
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
			// TODO: Log error.
			$response = new \WP_Error( 'error', $e->getMessage() );
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
	public function get_version( $request ) {
		$response = null;
		try {
			$response = AdminAPI::get()
			->integration( $request->get_param( self::PARAM_STORE_ID ) )
			->getVersion()
			->toArray();
		} catch ( \Throwable $e ) {
			// TODO: Log error.
			$response = new \WP_Error( 'error', $e->getMessage() );
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
	public function get_stores( $request ) {
		$response = null;
		try {
			$response = AdminAPI::get()
			->store( $request->get_param( self::PARAM_STORE_ID ) )
			->getStores()
			->toArray();
		} catch ( \Throwable $e ) {
			// TODO: Log error.
			$response = new \WP_Error( 'error', $e->getMessage() );
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
	public function get_state( $request ) {
		$response = null;
		try {
			$response = AdminAPI::get()
			->integration( $request->get_param( self::PARAM_STORE_ID ) )
			->getUIState( true ) // TODO: Pass false if the Onboarding does not configure widgets. 
			->toArray();
		} catch ( \Throwable $e ) {
			// TODO: Log error.
			$response = new \WP_Error( 'error', $e->getMessage() );
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
	public function get_general( $request ) {
		$response = null;
		try {
			$response = AdminAPI::get()
			->generalSettings( $request->get_param( self::PARAM_STORE_ID ) )
			->getGeneralSettings()
			->toArray();
		} catch ( \Throwable $e ) {
			// TODO: Log error.
			$response = new \WP_Error( 'error', $e->getMessage() );
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
	public function get_shop_name( $request ) {
		$response = null;
		try {
			$response = AdminAPI::get()
			->integration( $request->get_param( self::PARAM_STORE_ID ) )
			->getShopName()
			->toArray();
		} catch ( \Throwable $e ) {
			// TODO: Log error.
			$response = new \WP_Error( 'error', $e->getMessage() );
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
	public function save_general( $request ) {
		$response = null;
		try {
			$response = AdminAPI::get()
			->generalSettings( $request->get_param( self::PARAM_STORE_ID ) )
			->saveGeneralSettings(
				new GeneralSettingsRequest(
					$request->get_param( 'sendOrderReportsPeriodicallyToSeQura' ),
					$request->get_param( 'showSeQuraCheckoutAsHostedPage' ),
					$request->get_param( 'allowedIPAddresses' ),
					$request->get_param( 'excludedProducts' ),
					$request->get_param( 'excludedCategories' )
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
	 * GET shop categories.
	 * 
	 * @param WP_REST_Request $request The request.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_shop_categories( $request ) {
		$response = null;
		try {
			$response = AdminAPI::get()
			->generalSettings( $request->get_param( self::PARAM_STORE_ID ) )
			->getShopCategories()
			->toArray();
		} catch ( \Throwable $e ) {
			// TODO: Log error.
			$response = new \WP_Error( 'error', $e->getMessage() );
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
	}

	/**
	 * Sanitize an array of ids.
	 * 
	 * @param array $param The parameter.
	 */
	public function sanitize_array_of_ids( $param ): array {
		foreach ( $param as &$val ) {
			$val = (string) absint( $val );
		}
		return $param;
	}
}
