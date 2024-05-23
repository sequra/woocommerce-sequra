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
	 * Configuration.
	 *
	 * @var Configuration
	 */
	private $configuration;

	/**
	 * Constructor.
	 *
	 * @param string            $rest_namespace The namespace.
	 * @param Configuration $configuration The configuration.
	 */
	public function __construct( $rest_namespace, Configuration $configuration ) {
		$this->namespace     = $rest_namespace;
		$this->rest_base     = '/settings';
		$this->configuration = $configuration;
	}

	/**
	 * Register the API endpoints.
	 */
	public function register_routes() {

		$general_args = array(
			'sendOrderReportsPeriodicallyToSeQura' => array(
				'required'          => true,
				'validate_callback' => array( $this, 'validate_is_bool' ),
				'sanitize_callback' => array( $this, 'sanitize_bool' ),
			),
			'showSeQuraCheckoutAsHostedPage'       => array(
				'required'          => true,
				'validate_callback' => array( $this, 'validate_is_bool' ),
				'sanitize_callback' => array( $this, 'sanitize_bool' ),
			),
			'allowedIPAddresses'                   => array(
				'required' => true,
				// 'validate_callback' => array( $this, 'validate_not_empty_string' ),
			),
			'excludedProducts'                     => array(
				'required'          => true,
				'validate_callback' => array( $this, 'validate_array_of_product_sku' ),
				'sanitize_callback' => array( $this, 'sanitize_array_of_sku' ),
			),
			'excludedCategories'                   => array(
				'required'          => false,
				'validate_callback' => array( $this, 'validate_array_of_product_cat' ),
				'sanitize_callback' => array( $this, 'sanitize_array_of_ids' ),
			),
		);

		$this->register_get( 'current-store', 'get_current_store' );
		$this->register_get( 'version', 'get_version' );
		$this->register_get( 'stores', 'get_stores' );
		$this->register_get( 'state', 'get_state' );
		$this->register_get( 'shop-categories', 'get_shop_categories' );
		
		$this->register_get( 'general', 'get_general' );
		$this->register_post( 'general', 'save_general', $general_args );
	}

	/**
	 * GET current store.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_current_store() {
		return rest_ensure_response( $this->configuration->get_current_store() );
	}

	/**
	 * GET version.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_version() {
		return rest_ensure_response(
			array(
				'current'               => $this->configuration->get_module_version(),
				'new'                   => $this->configuration->get_marketplace_version(),
				'downloadNewVersionUrl' => $this->configuration->get_marketplace_url(),
			) 
		);
	}

	/**
	 * GET stores.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_stores() {
		return rest_ensure_response( $this->configuration->get_stores() );
	}

	/**
	 * GET state.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_state() {
		$data = array(
			'state' => 'dashboard',
		);

		return rest_ensure_response( $data );
	}

	/**
	 * GET general settings.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_general() {
		$response = null;
		try {
			$response = AdminAPI::get()
			->generalSettings( $this->configuration->get_store_id() )
			->getGeneralSettings()
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
			->generalSettings( $this->configuration->get_store_id() )
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
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_shop_categories() {
		$response = null;
		try {
			$response = AdminAPI::get()
			->generalSettings( $this->configuration->get_store_id() )
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
			$val = "{absint( $val )}";
		}
		return $param;
	}

	/**
	 * Sanitize an array of SKU.
	 * 
	 * @param array $param The parameter.
	 */
	public function sanitize_array_of_sku( $param ): array {
		foreach ( $param as &$val ) {
			$val = sanitize_text_field( $val );
		}
		return $param;
	}
}
