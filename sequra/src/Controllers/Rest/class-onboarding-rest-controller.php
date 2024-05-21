<?php
/**
 * REST Onboarding Controller
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers/Rest
 */

namespace SeQura\WC\Controllers\Rest;

use SeQura\Core\BusinessLogic\AdminAPI\AdminAPI;

/**
 * REST Onboarding Controller
 */
class Onboarding_REST_Controller extends REST_Controller {

	/**
	 * Constructor.
	 *
	 * @param string $rest_namespace The namespace.
	 */
	public function __construct( $rest_namespace ) {
		$this->namespace = $rest_namespace;
		$this->rest_base = '/onboarding';
	}

	/**
	 * Register the API endpoints.
	 */
	public function register_routes() {
		$this->register_get( 'data', 'get_data' );
		$this->register_get( 'countries', 'get_countries' );
		$this->register_get( 'widgets', 'get_widgets' );
	}

	/**
	 * GET data.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_data() {

		// $a = AdminAPI::get()->connection($this->storeId);

		$data = AdminAPI::get()->connection( (string) get_current_blog_id() )->getOnboardingData();

		$onboarding_data = array(
			'environment'         => 'sandbox',
			'username'            => 'dummy',
			'password'            => 'ZqbjrN6bhPYVIyram3wcuQgHUmP1C4',
			'merchantId'          => null,
			'sendStatisticalData' => true,
		);
		return rest_ensure_response( $onboarding_data );
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
}
