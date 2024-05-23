<?php
/**
 * Helper for REST Controllers
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers/Rest
 */

namespace SeQura\WC\Controllers\Rest;

/**
 * Helper for REST Controllers
 */
abstract class REST_Controller extends \WP_REST_Controller {

	/**
	 * Check if the current user can manage options.
	 */
	public function can_user_manage_options() {
		return user_can( get_current_user_id(), 'manage_options' );
	}

	/**
	 * Register GET endpoint.
	 * 
	 * @param string $endpoint The endpoint.
	 * @param string $fun       The function.
	 * @param array  $arguments The arguments. See https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
	 * @param string $permission_callback The permission callback.
	 */
	protected function register_get( $endpoint, $fun, $args = array(), $permission_callback = 'can_user_manage_options' ) {
		$this->register( \WP_REST_Server::READABLE, $endpoint, $fun, $args, $permission_callback );
	}

	/**
	 * Register POST endpoint.
	 * 
	 * @param string $endpoint The endpoint.
	 * @param string $fun       The function.
	 * @param array  $arguments The arguments. See https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
	 * @param string $permission_callback The permission callback.
	 */
	protected function register_post( $endpoint, $fun, $args = array(), $permission_callback = 'can_user_manage_options' ) {
		$this->register( \WP_REST_Server::CREATABLE, $endpoint, $fun, $args, $permission_callback );
	}

	/**
	 * Register endpoint.
	 * 
	 * @param string $endpoint The endpoint.
	 * @param string $fun       The function.
	 * @param array  $arguments The arguments. See https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
	 * @param string $permission_callback The permission callback.
	 */
	private function register( $methods, $endpoint, $fun, $args, $permission_callback ) {
		$args = array(
			'methods'             => $methods,
			'callback'            => array( $this, $fun ),
			'permission_callback' => array( $this, $permission_callback ),
		);
		if ( ! empty( $arguments ) && is_array( $arguments ) ) {
			$args['args'] = $arguments;
		}
		register_rest_route( $this->namespace, "{$this->rest_base}/$endpoint", $args );
	}

	/**
	 * Validate if the parameter is not empty string.
	 */
	public function validate_not_empty_string( $param, $request, $key ) {
		return is_string( $param ) && '' !== trim( $param );
	}

	/**
	 * Validate if the parameter is a boolean.
	 */
	public function validate_is_bool( $param, $request, $key ) {
		return is_bool( $param );
	}

	/**
	 * Sanitize boolean.
	 */
	public function sanitize_bool( $param ) {
		return (bool) $param;
	}
}
