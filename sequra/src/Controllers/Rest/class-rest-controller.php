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

	/**
	 * Sanitize an array strings.
	 * 
	 * @param array $param The parameter.
	 */
	public function sanitize_array_sanitize_text_field( $param ): array {
		return map_deep( $param, 'sanitize_text_field' );
	}

	/**
	 * Get base argument structure.
	 * 
	 * @param bool $required      If the argument is required.
	 * @param mixed $default_value The default value. Null will be ignored.
	 */
	private function get_arg( $required = true, $default_value = null ): array {
		$arg = array( 'required' => $required );
		if ( null !== $default_value ) {
			$arg['default'] = $default_value;
		}
		return $arg;
	}

	/**
	 * Get argument structure for a boolean parameter.
	 * 
	 * @param bool $required      If the argument is required.
	 * @param mixed $default_value The default value. Null will be ignored.
	 */
	protected function get_arg_bool( $required = true, $default_value = null ) {
		return array_merge(
			$this->get_arg( $required, $default_value ),
			array(
				'validate_callback' => array( $this, 'validate_is_bool' ),
				'sanitize_callback' => array( $this, 'sanitize_bool' ),
			)
		);
	}

	/**
	 * Get argument structure for a boolean parameter.
	 * 
	 * @param bool $required      If the argument is required.
	 * @param mixed $default_value The default value. Null will be ignored.
	 * @param callable $validate The validate callback. Leave null to use the default.
	 * @param callable $sanitize The sanitize callback. Leave null to use the default.
	 */
	protected function get_arg_string( $required = true, $default_value = null, $validate = null, $sanitize = null ) {
		return array_merge(
			$this->get_arg( $required, $default_value ),
			array(
				'validate_callback' => null === $validate ? array( $this, 'validate_not_empty_string' ) : $validate,
				'sanitize_callback' => null === $sanitize ? 'sanitize_text_field' : $sanitize,
			)
		);
	}
}
