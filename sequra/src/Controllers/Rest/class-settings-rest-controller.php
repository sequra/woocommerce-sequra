<?php
/**
 * REST Settings Controller
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers/Rest
 */

namespace SeQura\WC\Controllers\Rest;

/**
 * REST Settings Controller
 */
class Settings_REST_Controller extends REST_Controller {

	/**
	 * Constructor.
	 *
	 * @param string            $rest_namespace The namespace.
	 */
	public function __construct( $rest_namespace ) {
		$this->namespace = $rest_namespace;
		$this->rest_base = '/settings';
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
		$data = array(
			'storeId'   => get_current_blog_id(),
			'storeName' => 'Default Store View',
		);
		return rest_ensure_response( $data );
	}

	/**
	 * GET version.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_version() {
		$data = array(
			'current'               => '2.5.0.3',
			'new'                   => '2.5.0.4',
			'downloadNewVersionUrl' => 'https://sequra.es',
		);

		return rest_ensure_response( $data );
	}

	/**
	 * GET stores.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_stores() {
		$data = array(
			'storeId'   => get_current_blog_id(),
			'storeName' => 'Default Store View',
		);

		return rest_ensure_response( array( $data ) );
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
		$data = array();

		return rest_ensure_response( $data );
	}

	/**
	 * GET shop categories.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_shop_categories() {
		$data = array(
			array(
				'id'   => '2',
				'name' => 'Default Category',
			),
			array(
				'id'   => '3',
				'name' => 'testcat',
			),
		);

		return rest_ensure_response( $data );
	}
}
