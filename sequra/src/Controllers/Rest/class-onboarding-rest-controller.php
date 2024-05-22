<?php
/**
 * REST Onboarding Controller
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers/Rest
 */

namespace SeQura\WC\Controllers\Rest;

use SeQura\Core\BusinessLogic\AdminAPI\AdminAPI;
use SeQura\Core\BusinessLogic\AdminAPI\Connection\Requests\OnboardingRequest;
use SeQura\WC\Services\Core\Configuration;

/**
 * REST Onboarding Controller
 */
class Onboarding_REST_Controller extends REST_Controller {

	/**
	 * Configuration.
	 *
	 * @var Configuration
	 */
	private $configuration;
	
	/**
	 * Constructor.
	 *
	 * @param string $rest_namespace The namespace.
	 * @param Configuration $configuration The configuration.
	 */
	public function __construct( $rest_namespace, Configuration $configuration ) {
		$this->namespace     = $rest_namespace;
		$this->rest_base     = '/onboarding';
		$this->configuration = $configuration;



		// // Usa AUTH_KEY y asegúrate de que tiene la longitud correcta (32 bytes)
		// $key = hash( 'sha256', '1234', true ); // Genera una clave de 32 bytes a partir de AUTH_KEY

		// // El valor que deseas cifrar
		// $valor = 'mi_valor_secreto';

		// // Generar un nonce (debe ser único para cada cifrado)
		// $nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );

		// // Cifrar el valor
		// $valor_cifrado = sodium_crypto_secretbox( $valor, $nonce, $key );

		// // Almacenar el valor cifrado y el nonce en la base de datos
		// $val = base64_encode( $nonce . $valor_cifrado );

		// // Recuperar el valor cifrado y el nonce de la base de datos
		// $datos_cifrados = base64_decode( $val );
		// $nonce          = mb_substr( $datos_cifrados, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit' );
		// $valor_cifrado  = mb_substr( $datos_cifrados, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit' );

		// // Descifrar el valor
		// $valor_descifrado = sodium_crypto_secretbox_open( $valor_cifrado, $nonce, $key );
		// return;
	}

	/**
	 * Register the API endpoints.
	 */
	public function register_routes() {
		$this->register_post( 'validate', 'validate_data' );
		$this->register_get( 'data', 'get_data' );
		$this->register_get( 'countries', 'get_countries' );
		$this->register_get( 'widgets', 'get_widgets' );
		$this->register_post(
			'data',
			'set_data',
			array(
				'environment'         => array(
					'required'          => true,
					'validate_callback' => array( $this, 'validate_environment' ),
				),
				'username'            => array(
					'required'          => true,
					'validate_callback' => array( $this, 'validate_not_empty_string' ),
				),
				'password'            => array(
					'required'          => true,
					'validate_callback' => array( $this, 'validate_not_empty_string' ),
				),
				'sendStatisticalData' => array(
					'default'           => false,
					'required'          => true,
					'validate_callback' => array( $this, 'validate_is_bool' ),
					'sanitize_callback' => array( $this, 'sanitize_bool' ),
				),
				'merchantId'          => array(
					'default'           => null,
					'required'          => false,
					'validate_callback' => array( $this, 'validate_not_empty_string' ),
				),
			)
		);
	}

	/**
	 * GET data.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_data() {
		$data = AdminAPI::get()->connection( (string) get_current_blog_id() )->getOnboardingData();

		// $onboarding_data = array(
		// 'environment'         => 'sandbox',
		// 'username'            => 'dummy',
		// 'password'            => 'ZqbjrN6bhPYVIyram3wcuQgHUmP1C4',
		// 'merchantId'          => null,
		// 'sendStatisticalData' => true,
		// );
		return rest_ensure_response( $data->toArray() );
	}

	/**
	 * POST data.
	 * 
	 * @param WP_REST_Request $request The request.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function validate_data( $request ) {
		// TODO: Check credentials against the API.
		return rest_ensure_response(
			array(
				'isValid' => true,
			) 
		);
	}

	/**
	 * POST data.
	 * 
	 * @throws \Exception
	 * @param WP_REST_Request $request The request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function set_data( $request ) {
		$response = null;
		try {
			$response = AdminAPI::get()->connection( (string) get_current_blog_id() )->saveOnboardingData(
				new OnboardingRequest(
					$request->get_param( 'environment' ),
					$request->get_param( 'username' ),
					$request->get_param( 'password' ),
					$request->get_param( 'sendStatisticalData' ),
					$request->get_param( 'merchantId' )
				)
			);

			$is_ok    = $response->isSuccessful();
			$response = $response->toArray();

			if ( ! $is_ok ) {
				throw new \Exception( $response['errorMessage'] );
			}
		} catch ( \Throwable $e ) {
			$response = new \WP_Error( 'error', $e->getMessage() );
		}
		return rest_ensure_response( $response );
	}

	/**
	 * GET countries.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_countries() {
		$data = array(
			array(
				'countryCode' => 'ES',
				'merchantId'  => 'dummy_ps_mikel',
			),
			array(
				'countryCode' => 'FR',
				'merchantId'  => 'dummy_fr',
			),
		);
		return rest_ensure_response( $data );
	}

	/**
	 * GET widgets.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_widgets() {
		$data = array(
			'useWidgets'                            => true,
			'displayWidgetOnProductPage'            => true,
			'showInstallmentAmountInProductListing' => true,
			'showInstallmentAmountInCartPage'       => true,
			'assetsKey'                             => 'ADc3ZdOLh4',
			'miniWidgetSelector'                    => '',
			'widgetLabels'                          => array(
				'message'           => 'Desde %s/mes',
				'messageBelowLimit' => 'Fracciona a partir de %s',
			),
			'widgetStyles'                          => '{"alignment":"center","amount-font-bold":"true","amount-font-color":"#1C1C1C","amount-font-size":"15","background-color":"white","border-color":"#B1AEBA","border-radius":"","class":"","font-color":"#1C1C1C","link-font-color":"#1C1C1C","link-underline":"true","no-costs-claim":"","size":"M","starting-text":"only","type":"banner"}',
		);
		return rest_ensure_response( $data );
	}

	/**
	 * Validate if the parameter is not empty.
	 */
	public function validate_environment( $param, $request, $key ) {
		return in_array( $param, array( 'sandbox', 'production' ), true );
	}
}
