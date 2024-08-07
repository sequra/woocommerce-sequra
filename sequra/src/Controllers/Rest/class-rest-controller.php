<?php
/**
 * Helper for REST Controllers
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers/Rest
 */

namespace SeQura\WC\Controllers\Rest;

use SeQura\WC\Services\Interface_Logger_Service;
use WP_REST_Request;

/**
 * Helper for REST Controllers
 */
abstract class REST_Controller extends \WP_REST_Controller {

	protected const PARAM_STORE_ID    = 'storeId';
	protected const PARAM_MERCHANT_ID = 'merchantId';

	/**
	 * Logger service.
	 *
	 * @var Interface_Logger_Service
	 */
	protected $logger;

	/**
	 * Constructor.
	 *
	 * @param Interface_Logger_Service $logger         The logger service.
	 */
	public function __construct( Interface_Logger_Service $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Check if the current user can manage options.
	 */
	public function can_user_manage_options(): bool {
		return user_can( get_current_user_id(), 'manage_options' );
	}

	/**
	 * Register GET endpoint.
	 * 
	 * @param string $endpoint The endpoint.
	 * @param string $fun       The function.
	 * @param mixed[]  $args The arguments. See https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
	 * @param string $permission_callback The permission callback.
	 */
	protected function register_get( $endpoint, $fun, $args = array(), $permission_callback = 'can_user_manage_options' ): void {
		$this->register( \WP_REST_Server::READABLE, $endpoint, $fun, $args, $permission_callback );
	}

	/**
	 * Register POST endpoint.
	 * 
	 * @param string $endpoint The endpoint.
	 * @param string $fun       The function.
	 * @param mixed[]  $args The arguments. See https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
	 * @param string $permission_callback The permission callback.
	 */
	protected function register_post( $endpoint, $fun, $args = array(), $permission_callback = 'can_user_manage_options' ): void {
		$this->register( \WP_REST_Server::CREATABLE, $endpoint, $fun, $args, $permission_callback );
	}

	/**
	 * Register DELETE endpoint.
	 * 
	 * @param string $endpoint The endpoint.
	 * @param string $fun       The function.
	 * @param mixed[]  $args The arguments. See https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
	 * @param string $permission_callback The permission callback.
	 */
	protected function register_delete( $endpoint, $fun, $args = array(), $permission_callback = 'can_user_manage_options' ): void {
		$this->register( \WP_REST_Server::DELETABLE, $endpoint, $fun, $args, $permission_callback );
	}

	/**
	 * Register endpoint.
	 * 
	 * @param string $methods The HTTP Verb.
	 * @param string $endpoint The endpoint.
	 * @param string $fun       The function.
	 * @param mixed[]  $arguments The arguments. See https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
	 * @param string $permission_callback The permission callback.
	 */
	private function register( $methods, $endpoint, $fun, $arguments, $permission_callback ): void {
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
	 * 
	 * @param mixed $param The parameter.
	 * @param WP_REST_Request $request The request.
	 * @param string $key The key.
	 */
	public function validate_not_empty_string( $param, $request, $key ): bool {
		return is_string( $param ) && '' !== trim( $param );
	}

	/**
	 * Validate if the parameter is a boolean.
	 * 
	 * @param mixed $param The parameter.
	 * @param WP_REST_Request $request The request.
	 * @param string $key The key.
	 */
	public function validate_is_bool( $param, $request, $key ): bool {
		return is_bool( $param );
	}

	/**
	 * Validate if the parameter is an integer.
	 * 
	 * @param mixed $param The parameter.
	 * @param WP_REST_Request $request The request.
	 * @param string $key The key.
	 */
	public function validate_is_int( $param, $request, $key ): bool {
		return is_int( $param );
	}

	/**
	 * Validate id the parameter is an array of IP addresses.
	 * 
	 * @param mixed $param The parameter.
	 * @param WP_REST_Request $request The request.
	 * @param string $key The key.
	 */
	public function validate_ip_list( $param, $request, $key ): bool {
		// phpcs:ignore Generic.Files.LineLength.TooLong
		$ip_regex = '/^(((25[0-5]|2[0-4]\d|[01]?\d\d?)\.(25[0-5]|2[0-4]\d|[01]?\d\d?)\.(25[0-5]|2[0-4]\d|[01]?\d\d?)\.(25[0-5]|2[0-4]\d|[01]?\d\d?))|([0-9a-fA-F]{1,4}:){7}([0-9a-fA-F]{1,4}))$/';
		if ( ! is_array( $param ) ) {
			return false;
		}
		foreach ( $param as $ip ) {
			if ( preg_match( $ip_regex, $ip ) !== 1 ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Check dates (yyyy-mm-dd) and time durations (PnYnMnDTnHnMnS). ISO 8061 regex to validate the date format.
	 * 
	 * @param mixed $param The parameter.
	 * @param WP_REST_Request $request The request.
	 * @param string $key The key.
	 */
	public function validate_time_duration( $param, $request, $key ): bool {
		// phpcs:ignore Generic.Files.LineLength.TooLong
		$regex = '/^((?:\d{4}-(?:0[1-9]|1[0-2])-(?:0[1-9]|1\d|2[0-8]))|(?:\d{4}-(?:0[13-9]|1[0-2])-(?:29|30))|(?:\d{4}-(?:0[13578]|1[012])-(?:31))|(?:\d{2}(?:[02468][048]|[13579][26])-(?:02)-29)|(P(?:\d+Y)?(?:\d+M)?(?:\d+W)?(?:\d+D)?(?:T(?:\d+H)?(?:\d+M)?(?:\d+S)?)?))$/';
		return is_string( $param ) && preg_match( $regex, $param ) === 1 && 'P' !== $param && ! str_ends_with( $param, 'T' );
	}

	/**
	 * Sanitize boolean.
	 * 
	 * @param mixed $param The parameter.
	 */
	public function sanitize_bool( $param ): bool {
		return (bool) $param;
	}
	
	/**
	 * Sanitize boolean.
	 * 
	 * @param mixed $param The parameter.
	 */
	public function sanitize_int( $param ): int {
		return intval( $param );
	}

	/**
	 * Sanitize an array strings.
	 * 
	 * @param mixed[] $param The parameter.
	 * @return mixed[]
	 */
	public function sanitize_array_sanitize_text_field( $param ): array {
		foreach ( $param as &$value ) {
			$value = sanitize_text_field( strval( $value ) );
		}
		return $param;
	}

	/**
	 * Get base argument structure.
	 * 
	 * @param bool $required      If the argument is required.
	 * @param mixed $default_value The default value. Null will be ignored.
	 * @return mixed[]
	 */
	protected function get_arg( $required = true, $default_value = null ): array {
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
	 * @return mixed[]
	 */
	protected function get_arg_bool( $required = true, $default_value = null ): array {
		return array_merge(
			$this->get_arg( $required, $default_value ),
			array(
				'validate_callback' => array( $this, 'validate_is_bool' ),
				'sanitize_callback' => array( $this, 'sanitize_bool' ),
			)
		);
	}

	/**
	 * Get argument structure for a integer parameter.
	 * 
	 * @param bool $required      If the argument is required.
	 * @param mixed $default_value The default value. Null will be ignored.
	 * @return mixed[]
	 */
	protected function get_arg_int( $required = true, $default_value = null ): array {
		return array_merge(
			$this->get_arg( $required, $default_value ),
			array(
				'validate_callback' => array( $this, 'validate_is_int' ),
				'sanitize_callback' => array( $this, 'sanitize_int' ),
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
	 * @return mixed[]
	 */
	protected function get_arg_string( $required = true, $default_value = null, $validate = null, $sanitize = null ): array {
		return array_merge(
			$this->get_arg( $required, $default_value ),
			array(
				'validate_callback' => null === $validate ? array( $this, 'validate_not_empty_string' ) : $validate,
				'sanitize_callback' => null === $sanitize ? 'sanitize_text_field' : $sanitize,
			)
		);
	}

	/**
	 * Get argument structure for an IP list.
	 * 
	 * @param bool $required      If the argument is required.
	 * @param mixed $default_value The default value. Null will be ignored.
	 * @param callable $validate The validate callback. Leave null to use the default.
	 * @param callable $sanitize The sanitize callback. Leave null to use the default.
	 * @return mixed[]
	 */
	protected function get_arg_ip_list( $required = true, $default_value = null, $validate = null, $sanitize = null ): array {
		return array_merge(
			$this->get_arg( $required, $default_value ),
			array(
				'validate_callback' => null === $validate ? array( $this, 'validate_ip_list' ) : $validate,
				'sanitize_callback' => null === $sanitize ? array( $this, 'sanitize_array_sanitize_text_field' ) : $sanitize,
			)
		);
	}

	/**
	 * Return a valid pattern to be used in a URL to match a parameter.
	 * 
	 * @param string $param_name The URL parameter name.
	 * @param string $type       The type of the parameter. Default is 'string'. 
	 * Supported values are:
	 *  - 'string'.
	 */
	protected function url_param_pattern( $param_name, $type = 'string' ): string {
		switch ( $type ) {
			case 'string':
				return '(?P<' . $param_name . '>[\w]+)';
			default:
				return '';
		}
	}
}
