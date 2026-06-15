<?php
/**
 * REST Affiliate Settings Controller
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers/Rest
 */

namespace SeQura\WC\Controllers\Rest;

use SeQura\Core\BusinessLogic\Domain\Multistore\StoreContext;
use SeQura\Core\Infrastructure\Utility\RegexProvider;
use SeQura\WC\Services\Affiliate\Interface_Affiliate_Settings_Service;
use SeQura\WC\Services\Log\Interface_Logger_Service;
use WP_REST_Request;

/**
 * REST controller for the affiliate settings (enable toggle, Offer ID, Security Token).
 */
class Affiliate_Settings_REST_Controller extends REST_Controller {

	private const PARAM_ENABLED             = 'enabled';
	private const PARAM_OFFER_ID            = 'offerId';
	private const PARAM_SECURITY_TOKEN      = 'securityToken';
	private const MAX_OFFER_ID_LENGTH       = 4;
	private const MAX_SECURITY_TOKEN_LENGTH = 64;

	/**
	 * Store context.
	 *
	 * @var StoreContext
	 */
	private $store_context;

	/**
	 * Affiliate settings service.
	 *
	 * @var Interface_Affiliate_Settings_Service
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param string                               $rest_namespace The namespace.
	 * @param Interface_Logger_Service             $logger         The logger service.
	 * @param RegexProvider                        $regex          The regex provider.
	 * @param StoreContext                         $store_context  The store context.
	 * @param Interface_Affiliate_Settings_Service $settings       The affiliate settings service.
	 */
	public function __construct(
		$rest_namespace,
		Interface_Logger_Service $logger,
		RegexProvider $regex,
		StoreContext $store_context,
		Interface_Affiliate_Settings_Service $settings
	) {
		parent::__construct( $logger, $regex );
		$this->namespace     = $rest_namespace;
		$this->rest_base     = '/settings';
		$this->store_context = $store_context;
		$this->settings      = $settings;
	}

	/**
	 * Register the API endpoints.
	 */
	public function register_routes() {
		$this->logger->log_debug( 'Hook executed', __FUNCTION__, __CLASS__ );
		$store_id_args = array( self::PARAM_STORE_ID => $this->get_arg_string() );
		$save_args     = array_merge(
			$store_id_args,
			array(
				self::PARAM_ENABLED        => $this->get_arg_bool( false, false ),
				self::PARAM_OFFER_ID       => $this->get_arg_string( true, null, array( $this, 'validate_offer_id' ) ),
				self::PARAM_SECURITY_TOKEN => $this->get_arg_string( true, null, array( $this, 'validate_security_token' ) ),
			)
		);
		$store_id      = $this->url_param_pattern( self::PARAM_STORE_ID );

		$this->register_get( "affiliate/{$store_id}", 'get_affiliate', $store_id_args );
		$this->register_post( "affiliate/{$store_id}", 'save_affiliate', $save_args );
	}

	/**
	 * GET affiliate settings.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_affiliate( WP_REST_Request $request ) {
		$settings = $this->settings->get_settings( strval( $request->get_param( self::PARAM_STORE_ID ) ) );
		return $this->build_response_from_array(
			array(
				self::PARAM_ENABLED        => (bool) $settings['enabled'],
				self::PARAM_OFFER_ID       => (string) $settings['offer_id'],
				self::PARAM_SECURITY_TOKEN => (string) $settings['security_token'],
			),
			true
		);
	}

	/**
	 * POST affiliate settings.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function save_affiliate( WP_REST_Request $request ) {
		$this->settings->save_settings(
			strval( $request->get_param( self::PARAM_STORE_ID ) ),
			(bool) $request->get_param( self::PARAM_ENABLED ),
			strval( $request->get_param( self::PARAM_OFFER_ID ) ),
			strval( $request->get_param( self::PARAM_SECURITY_TOKEN ) )
		);
		return $this->build_response_from_array( array( 'success' => true ), true );
	}

	/**
	 * Validate the Offer ID (numeric, up to 4 chars per the TUNE/Simba contract).
	 *
	 * @param mixed           $param   The parameter.
	 * @param WP_REST_Request $request The request.
	 * @param string          $key     The key.
	 */
	public function validate_offer_id( $param, $request, $key ): bool {
		return is_string( $param ) && 1 === preg_match( '/^[0-9]{1,' . self::MAX_OFFER_ID_LENGTH . '}$/', $param );
	}

	/**
	 * Validate the Security Token (alphanumeric only, max 64 chars, matching the Simba webhook contract).
	 *
	 * @param mixed           $param   The parameter.
	 * @param WP_REST_Request $request The request.
	 * @param string          $key     The key.
	 */
	public function validate_security_token( $param, $request, $key ): bool {
		return is_string( $param )
			&& strlen( $param ) <= self::MAX_SECURITY_TOKEN_LENGTH
			&& 1 === preg_match( '/^[a-zA-Z0-9]+$/', $param );
	}
}
