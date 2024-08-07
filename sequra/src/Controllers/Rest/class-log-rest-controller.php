<?php
/**
 * REST Log Controller
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers/Rest
 */

namespace SeQura\WC\Controllers\Rest;

use SeQura\WC\Services\Interface_Logger_Service;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST Log Controller
 */
class Log_REST_Controller extends REST_Controller {

	const PARAM_IS_ENABLED = 'isEnabled';
	const PARAM_LOG_LEVEL  = 'level';

	/**
	 * Constructor.
	 *
	 * @param string            $rest_namespace The namespace.
	 * @param Interface_Logger_Service $logger         The logger service.
	 */
	public function __construct( $rest_namespace, Interface_Logger_Service $logger ) {
		parent::__construct( $logger );
		$this->namespace = $rest_namespace;
		$this->rest_base = '/log';
	}

	/**
	 * Register the API endpoints.
	 */
	public function register_routes(): void {
		$this->logger->log_info( 'Hook executed', __FUNCTION__, __CLASS__ );
		$store_id_args = array( self::PARAM_STORE_ID => $this->get_arg_string() );
		$conf_args     = array_merge(
			$store_id_args,
			array( 
				self::PARAM_IS_ENABLED => $this->get_arg_bool(),
				self::PARAM_LOG_LEVEL  => $this->get_arg_int(),
			) 
		);
		$store_id      = $this->url_param_pattern( self::PARAM_STORE_ID );

		$this->register_get( "{$store_id}", 'get_logs', $store_id_args );
		$this->register_delete( "{$store_id}", 'delete_logs', $store_id_args );

		$this->register_get( "settings/{$store_id}", 'get_configuration', $store_id_args );
		$this->register_post( "settings/{$store_id}", 'save_configuration', $conf_args );
	}

	/**
	 * GET logs.
	 * 
	 * @param WP_REST_Request $request The request.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_logs( WP_REST_Request $request ) {
		$response = null;
		try {
			$response = $this->logger->get_content(
				$request->get_param( self::PARAM_STORE_ID )
			);
		} catch ( \Throwable $e ) {
			$response = new WP_Error( 'error', $e->getMessage() );
		}
		return rest_ensure_response( $response );
	}

	/**
	 * DELETE logs.
	 * 
	 * @param WP_REST_Request $request The request.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_logs( WP_REST_Request $request ) {
		$response = null;
		try {
			$this->logger->clear(
				$request->get_param( self::PARAM_STORE_ID )
			);
			$response = array();
		} catch ( \Throwable $e ) {
			$response = new WP_Error( 'error', $e->getMessage() );
		}
		return rest_ensure_response( $response );
	}

	/**
	 * GET logs configuration.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_configuration() {
		$response = null;
		try {
			$response = $this->get_config_response();
		} catch ( \Throwable $e ) {
			$response = new WP_Error( 'error', $e->getMessage() );
		}
		return rest_ensure_response( $response );
	}

	/**
	 * POST logs configuration.
	 * 
	 * @param WP_REST_Request $request The request.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function save_configuration( WP_REST_Request $request ) {
		$response = null;
		try {
			$this->logger->enable( $request->get_param( self::PARAM_IS_ENABLED ) );
			$this->logger->set_min_log_level( $request->get_param( self::PARAM_LOG_LEVEL ) );
			$response = $this->get_config_response();
		} catch ( \Throwable $e ) {
			$response = new WP_Error( 'error', $e->getMessage() );
		}
		return rest_ensure_response( $response );
	}

	/**
	 * Make the logger configuration response.
	 *
	 * @return array<string, mixed>
	 */
	private function get_config_response(): array {
		return array(
			self::PARAM_IS_ENABLED => $this->logger->is_enabled(),
			self::PARAM_LOG_LEVEL  => $this->logger->get_min_log_level(),
		);
	}
}
