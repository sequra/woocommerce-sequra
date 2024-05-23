<?php
/**
 * REST Settings Controller
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers/Rest
 */

namespace SeQura\WC\Controllers\Rest;

use SeQura\Core\BusinessLogic\AdminAPI\AdminAPI;
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
		$this->register_get( 'current-store', 'get_current_store' );
		$this->register_get( 'version', 'get_version' );
		$this->register_get( 'stores', 'get_stores' );
		$this->register_get( 'state', 'get_state' );
		$this->register_get( 'general', 'get_general' );
		$this->register_get( 'shop-categories', 'get_shop_categories' );
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
	 * GET general.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_general() {
		$data = array();// TODO: what is this data?

		return rest_ensure_response( $data );
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
}
