<?php
/**
 * REST Log Controller
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers/Rest
 */

namespace SeQura\WC\Controllers\Rest;

use SeQura\Core\BusinessLogic\ConfigurationWebhookAPI\Responses\AdvancedSettings\AdvancedSettingsResponse;
use SeQura\Core\BusinessLogic\Domain\AdvancedSettings\Models\AdvancedSettings;
use SeQura\Core\BusinessLogic\Domain\AdvancedSettings\Services\AdvancedSettingsService;
use SeQura\Core\Infrastructure\Logger\Logger;
use SeQura\WC\Services\Log\Interface_Logger_Service;
use SeQura\Core\Infrastructure\Utility\RegexProvider;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST Log Controller
 */
class Log_REST_Controller extends REST_Controller {

	private const PARAM_IS_ENABLED = 'isEnabled';
	private const PARAM_LOG_LEVEL  = 'level';

	/**
	 * Advanced settings service
	 *
	 * @var AdvancedSettingsService
	 */
	private $advanced_settings_service;

	/**
	 * Constructor.
	 *
	 * @param string            $rest_namespace The namespace.
	 * @param Interface_Logger_Service $logger         The logger service.
	 * @param RegexProvider $regex The regex provider.
	 * @param AdvancedSettingsService $advanced_settings_service The advanced settings service.
	 */
	public function __construct( 
		$rest_namespace, 
		Interface_Logger_Service $logger,
		RegexProvider $regex,
		AdvancedSettingsService $advanced_settings_service
	) {
		parent::__construct( $logger, $regex );
		$this->namespace                 = $rest_namespace;
		$this->rest_base                 = '/log';
		$this->advanced_settings_service = $advanced_settings_service;
	}

	/**
	 * Register the API endpoints.
	 * 
	 * @return void
	 */
	public function register_routes() {
		$this->logger->log_debug( 'Hook executed', __FUNCTION__, __CLASS__ );
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
		return \rest_ensure_response( $response );
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
		return \rest_ensure_response( $response );
	}

	/**
	 * GET logs configuration.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_configuration() {
		$advanced_settings = $this->advanced_settings_service->getAdvancedSettings() ?? new AdvancedSettings( false, Logger::DEBUG );
		return $this->build_response( new AdvancedSettingsResponse( $advanced_settings ) );
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
			$advanced_settings = new AdvancedSettings(
				(bool) $request->get_param( self::PARAM_IS_ENABLED ),
				(int) $request->get_param( self::PARAM_LOG_LEVEL )
			);
			$this->advanced_settings_service->setAdvancedSettings( $advanced_settings );
			$response = new AdvancedSettingsResponse( $advanced_settings );
		} catch ( \Throwable $e ) {
			$response = new WP_Error( 'error', $e->getMessage() );
		}
		return \rest_ensure_response( $response );
	}
}
