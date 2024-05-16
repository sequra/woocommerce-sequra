<?php
/**
 * Implementation for the settings controller.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers
 */

namespace SeQura\WC\Controllers;

use SeQura\Core\BusinessLogic\AdminAPI\AdminAPI;
use SeQura\Core\Infrastructure\Configuration\Configuration;
use SeQura\Core\Infrastructure\ServiceRegister;
use SeQura\WC\Services\Interface_Settings;

/**
 * Implementation for the settings controller.
 */
class Settings_Controller implements Interface_Settings_Controller {

	private const MENU_SLUG   = 'sequra';
	private const PARENT_SLUG = 'options-general.php';

	/**
	 * The templates path.
	 *
	 * @var string
	 */
	private $templates_path;

	/**
	 * The settings.
	 *
	 * @var Interface_Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param string $templates_path The templates path.
	 */
	public function __construct( string $templates_path, Interface_Settings $settings ) {
		$this->templates_path = $templates_path;
		$this->settings       = $settings;
	}

	/**
	 * Register the settings page.
	 */
	public function register_page(): void {

		\add_submenu_page(
			self::PARENT_SLUG,
			__( 'seQura', 'sequra' ),
			__( 'seQura', 'sequra' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_page' )
		);

		// Additionally remove WP version footer text if we are in the settings page.
		if ( $this->settings->is_settings_page() ) {
			remove_filter( 'update_footer', 'core_update_footer' );
		}
	}

	/**
	 * Render the settings page.
	 */
	public function render_page(): void {
		\wc_get_template( 'admin/settings_page.php', array(), '', $this->templates_path );
	}

	/**
	 * Add action links to the plugin settings page.
	 *
	 * @param string[] $actions The actions.
	 * @param string   $plugin_file The plugin file.
	 * @param string   $plugin_data The plugin data.
	 * @param string   $context The context.
	 * @return string[]
	 */
	public function add_action_link( $actions, $plugin_file, $plugin_data, $context ): array {
		$args = array(
			'href' => admin_url( self::PARENT_SLUG . '?page=' . self::MENU_SLUG ),
			'text' => esc_attr__( 'Settings', 'sequra' ),
		);
		\ob_start();
		\wc_get_template( 'admin/action_link.php', $args, '', $this->templates_path );
		$actions['settings'] = \ob_get_clean();
		return $actions;
	}

	/**
	 * Removes the WP footer message
	 */
	public function remove_footer_admin( $text ): string {
		if ( ! $this->settings->is_settings_page() ) {
			return $text;
		}
		return '';
	}

	/**
	 * Register the API endpoints.
	 */
	public function register_api_endpoints() {
		register_rest_route(
			'sequra/v1',
			'/settings/current-store',
			array(
				'methods'  => 'GET',
				'callback' => array( $this, 'get_current_store' ),
			) 
		);
		
		register_rest_route(
			'sequra/v1',
			'/settings/version',
			array(
				'methods'  => 'GET',
				'callback' => array( $this, 'get_version' ),
			) 
		);
		register_rest_route(
			'sequra/v1',
			'/settings/stores',
			array(
				'methods'  => 'GET',
				'callback' => array( $this, 'get_stores' ),
			) 
		);
		register_rest_route(
			'sequra/v1',
			'/settings/state',
			array(
				'methods'  => 'GET',
				'callback' => array( $this, 'get_state' ),
			) 
		);
		register_rest_route(
			'sequra/v1',
			'/settings/general',
			array(
				'methods'  => 'GET',
				'callback' => array( $this, 'get_general' ),
			) 
		);
		register_rest_route(
			'sequra/v1',
			'/settings/shop-categories',
			array(
				'methods'  => 'GET',
				'callback' => array( $this, 'get_shop_categories' ),
			) 
		);

		// onboarding ---------------------
		register_rest_route(
			'sequra/v1',
			'/onboarding/data',
			array(
				'methods'  => 'GET',
				'callback' => array( $this, 'get_onboarding_data' ),
			) 
		);
		register_rest_route(
			'sequra/v1',
			'/onboarding/countries',
			array(
				'methods'  => 'GET',
				'callback' => array( $this, 'get_onboarding_countries' ),
			) 
		);
		register_rest_route(
			'sequra/v1',
			'/onboarding/widgets',
			array(
				'methods'  => 'GET',
				'callback' => array( $this, 'get_onboarding_widgets' ),
			) 
		);
		// payment ---------------------
		register_rest_route(
			'sequra/v1',
			'/payment/selling-countries',
			array(
				'methods'  => 'GET',
				'callback' => array( $this, 'get_payment_selling_countries' ),
			) 
		);
		register_rest_route(
			'sequra/v1',
			'/payment/methods',
			array(
				'methods'  => 'GET',
				'callback' => array( $this, 'get_payment_methods' ),
			) 
		);
	}

	public function get_current_store() {
		$data = array(
			'storeId'   => get_current_blog_id(),
			'storeName' => 'Default Store View',
		);
		return rest_ensure_response( $data );
	}

	public function get_version() {
		$data = array(
			'current'               => '2.5.0.3',
			'new'                   => '2.5.0.4',
			'downloadNewVersionUrl' => 'https://sequra.es',
		);

		return rest_ensure_response( $data );
	}

	public function get_stores() {
		$data = array(
			'storeId'   => get_current_blog_id(),
			'storeName' => 'Default Store View',
		);

		return rest_ensure_response( array( $data ) );
	}

	public function get_state() {
		// {"state":"dashboard"}
		$data = array(
			'state' => 'dashboard',
		);

		return rest_ensure_response( $data );
	}
	public function get_general() {
		$data = array();

		return rest_ensure_response( $data );
	}
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

	// onboarding
	public function get_onboarding_data() {
		$onboarding_data = array(
			'environment'         => 'sandbox',
			'username'            => 'dummy',
			'password'            => 'ZqbjrN6bhPYVIyram3wcuQgHUmP1C4',
			'merchantId'          => null,
			'sendStatisticalData' => true,
		);
		return rest_ensure_response( $onboarding_data );
	}
	public function get_onboarding_countries() {
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
	public function get_onboarding_widgets() {
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

	// payment
	public function get_payment_selling_countries() {
		$data = array(
			array(
				'code' => 'CO',
				'name' => 'Colombia',
			),
			array(
				'code' => 'FR',
				'name' => 'France',
			),
			array(
				'code' => 'IT',
				'name' => 'Italy',
			),
			array(
				'code' => 'PE',
				'name' => 'Peru',
			),
			array(
				'code' => 'PT',
				'name' => 'Portugal',
			),
			array(
				'code' => 'ES',
				'name' => 'Spain',
			),
		);
		return rest_ensure_response( $data );
	}
	public function get_payment_methods() {
		$data = array(
			array(
				'product'         => 'i1',
				'title'           => 'Paga Después',
				'longTitle'       => 'Recibe tu compra antes de pagar',
				'cost'            => array(
					'setupFee'        => 0,
					'instalmentFee'   => 0,
					'downPaymentFees' => 0,
					'instalmentTotal' => 0,
				),
				'startsAt'        => '2000-11-11 10:09:00',
				'endsAt'          => '2222-12-22 10:11:00',
				'campaign'        => null,
				'claim'           => 'Sin coste adicional',
				'description'     => 'Compra ahora, recibe primero y paga después. Cuando tu pedido salga de la tienda tendrás 7 días para realizar el pago desde el enlace que recibirás en tu email o mediante transferencia bancaria.',
				'icon'            => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n<svg id=\"Capa_1\" xmlns=\"http:\/\/www.w3.org\/2000\/svg\" height=\"40\" width=\"92\" viewBox=\"0 0 129 56\">\r\n  <defs>\r\n    <style>\r\n      .cls-1 {\r\n        fill: #00c2a3;\r\n      }\r\n      .cls-2 {\r\n        fill: #fff;\r\n        fill-rule: evenodd;\r\n      }\r\n    <\/style>\r\n  <\/defs>\r\n  <rect class=\"cls-1\" width=\"129\" height=\"56\" rx=\"8.2\" ry=\"8.2\"\/>\r\n  <g>\r\n    <path class=\"cls-2\" d=\"M69.3,36.45c-.67-.02-1.36-.13-2.05-.32,1.29-1.55,2.21-3.41,2.65-5.41,.65-2.96,.22-6.06-1.21-8.73-1.43-2.67-3.78-4.76-6.61-5.87-1.52-.6-3.12-.9-4.76-.9-1.4,0-2.78,.22-4.1,.67-2.88,.97-5.33,2.93-6.9,5.53-1.57,2.59-2.16,5.67-1.67,8.65,.49,2.98,2.04,5.7,4.36,7.67,2.23,1.88,5.07,2.95,8.02,3.03,.32,0,.61-.13,.83-.38,.16-.19,.25-.45,.25-.72v-2.12c0-.6-.47-1.07-1.07-1.09-1.93-.07-3.78-.79-5.23-2.01-1.54-1.3-2.57-3.1-2.89-5.07-.32-1.97,.07-4.01,1.1-5.72,1.04-1.72,2.67-3.02,4.58-3.65,.88-.3,1.79-.45,2.73-.45,1.08,0,2.14,.2,3.15,.6,1.89,.74,3.44,2.12,4.37,3.88,.95,1.77,1.23,3.82,.8,5.77-.33,1.52-1.09,2.93-2.2,4.07-.73-.75-1.32-1.63-1.75-2.64-.4-.94-.61-1.93-.65-2.95-.02-.6-.5-1.07-1.09-1.07h-2.13c-.28,0-.55,.1-.73,.26-.24,.21-.38,.51-.38,.84,.04,1.57,.37,3.1,.98,4.56,.65,1.55,1.58,2.94,2.78,4.14,1.2,1.19,2.6,2.12,4.17,2.77,1.47,.61,3.01,.93,4.59,.97h.02c.32,0,.62-.14,.83-.38,.16-.19,.25-.45,.25-.72v-2.1c.02-.29-.08-.56-.28-.77-.2-.21-.48-.34-.77-.35Z\"\/>\r\n    <path class=\"cls-2\" d=\"M21.14,28.97c-.63-.45-1.3-.79-1.99-1.02-.75-.26-1.52-.5-2.31-.69-.57-.12-1.11-.26-1.6-.42-.47-.14-.91-.32-1.3-.53l-.08-.03c-.15-.07-.27-.15-.39-.23-.1-.07-.2-.16-.26-.22-.05-.05-.08-.1-.08-.12-.02-.06-.02-.11-.02-.16,0-.15,.07-.3,.19-.43,.15-.17,.35-.3,.6-.41,.27-.12,.58-.2,.94-.25,.24-.03,.48-.05,.73-.05,.12,0,.24,0,.4,.02,.58,.02,1.14,.16,1.61,.41,.31,.15,1.01,.73,1.56,1.22,.14,.12,.32,.19,.51,.19,.16,0,.31-.05,.43-.13l2.22-1.52c.17-.12,.29-.3,.33-.5,.04-.21,0-.41-.13-.58-.58-.85-1.4-1.53-2.34-1.98-1.13-.58-2.4-.92-3.76-1.01-.27-.02-.54-.03-.81-.03-.62,0-1.23,.05-1.83,.15-.82,.13-1.62,.38-2.4,.75-.73,.35-1.38,.87-1.89,1.52-.52,.67-.82,1.47-.87,2.3-.09,.83,.07,1.66,.48,2.4,.37,.63,.9,1.17,1.53,1.57,.64,.4,1.34,.73,2.13,.98,.85,.28,1.65,.51,2.43,.7,.43,.11,.87,.23,1.36,.38,.38,.12,.71,.26,1.01,.45l.08,.04c.21,.13,.38,.29,.49,.45,.1,.16,.14,.32,.12,.56,0,.21-.09,.4-.22,.54-.17,.19-.41,.35-.6,.44h-.06l-.06,.02c-.33,.14-.69,.23-1.09,.28-.24,.03-.48,.04-.72,.04-.17,0-.35,0-.54-.02-.78-.02-1.55-.24-2.23-.62-.52-.33-.97-.71-1.34-1.12-.14-.16-.35-.26-.57-.26-.15,0-.31,.04-.45,.14l-2.34,1.62c-.18,.12-.3,.32-.33,.53-.03,.21,.03,.43,.17,.6,.72,.88,1.64,1.59,2.61,2.03l.04,.04,.05,.02c1.34,.56,2.73,.87,4.15,.93,.33,.02,.66,.04,1,.04,.59,0,1.18-.04,1.78-.11,.89-.11,1.75-.36,2.58-.73,.78-.37,1.48-.92,2-1.59,.56-.75,.88-1.65,.92-2.56,.08-.8-.07-1.61-.41-2.34-.32-.64-.8-1.22-1.41-1.67Z\"\/>\r\n    <path class=\"cls-2\" d=\"M112.44,20.49c-4.91,0-8.9,3.92-8.9,8.75s3.99,8.75,8.9,8.75c2.55,0,4.02-1.01,4.72-1.68v.6c0,.6,.49,1.09,1.09,1.09h2c.6,0,1.09-.49,1.09-1.09v-7.66c0-4.82-3.99-8.75-8.9-8.75Zm0,4.14c2.59,0,4.69,2.07,4.69,4.6s-2.11,4.6-4.69,4.6-4.7-2.06-4.7-4.6,2.11-4.6,4.7-4.6Z\"\/>\r\n    <path class=\"cls-2\" d=\"M101.34,20.5h0c-1.07,.04-2.11,.26-3.09,.67-1.1,.44-2.08,1.09-2.92,1.91-.83,.82-1.49,1.79-1.94,2.87-.41,.98-.63,2-.65,3.1v7.86c0,.6,.5,1.09,1.11,1.09h2.02c.61,0,1.11-.49,1.11-1.09v-7.89c.02-.52,.14-1.02,.34-1.5,.24-.57,.58-1.08,1.02-1.51,.44-.43,.96-.77,1.54-1.01,.47-.2,.99-.31,1.55-.35,.59-.03,1.06-.51,1.06-1.1v-1.99c-.02-.29-.15-.57-.36-.76-.21-.19-.46-.29-.78-.29Z\"\/>\r\n    <path class=\"cls-2\" d=\"M88.76,20.34h-2c-.6,0-1.1,.49-1.1,1.09v8.44c-.12,1.07-.55,1.97-1.26,2.67-.4,.4-.87,.72-1.39,.93-.54,.22-1.1,.33-1.66,.33s-1.12-.11-1.66-.33c-.53-.21-1-.53-1.39-.93-.71-.71-1.14-1.61-1.26-2.74v-8.37c0-.6-.49-1.09-1.09-1.09h-2c-.6,0-1.09,.49-1.09,1.09v8.07c0,1.14,.22,2.23,.65,3.25,.42,1.02,1.04,1.95,1.85,2.75,.79,.78,1.71,1.4,2.76,1.84,1.05,.43,2.15,.65,3.26,.65s2.24-.22,3.26-.65c1.02-.42,1.95-1.04,2.76-1.84,1.4-1.4,2.26-3.23,2.48-5.29v-8.78c-.01-.6-.51-1.08-1.11-1.08Z\"\/>\r\n    <path class=\"cls-2\" d=\"M34.62,20.53c-.38-.05-.77-.08-1.15-.08-1.73,0-3.41,.51-4.85,1.48-1.77,1.19-3.04,2.97-3.58,5.01-.55,2.05-.33,4.23,.61,6.13,.93,1.9,2.52,3.4,4.49,4.22,1.07,.44,2.19,.66,3.34,.66,.96,0,1.9-.16,2.81-.46,1.49-.52,2.82-1.41,3.83-2.59,.21-.24,.3-.56,.24-.88-.06-.32-.26-.6-.56-.75l-1.58-.82c-.16-.09-.35-.13-.54-.13-.31,0-.61,.12-.82,.34-.54,.52-1.16,.91-1.84,1.14-.51,.17-1.04,.25-1.57,.25-.64,0-1.26-.12-1.84-.37-1.08-.45-1.97-1.28-2.49-2.34-.03-.05-.05-.1-.07-.15h12.07c.61,0,1.1-.49,1.1-1.1v-.91c-.01-2.11-.78-4.14-2.17-5.72-1.4-1.6-3.33-2.63-5.43-2.91Zm-3.86,4.58c.81-.54,1.76-.83,2.73-.83,.22,0,.43,.01,.65,.04,1.18,.16,2.26,.74,3.05,1.63,.35,.4,.63,.85,.84,1.35h-9.07c.37-.89,1-1.66,1.81-2.2Z\"\/>\r\n  <\/g>\r\n<\/svg>\r\n",
				'costDescription' => 'sin coste adicional',
				'minAmount'       => 0,
				'maxAmount'       => null,
			),
			array(
				'product'         => 'pp3',
				'title'           => "Desde 0,00 \u20ac\/mes",
				'longTitle'       => "Desde 0,00 \u20ac\/mes o en 3 plazos sin coste",
				'cost'            => array(
					'setupFee'        => 0,
					'instalmentFee'   => 0,
					'downPaymentFees' => 0,
					'instalmentTotal' => 0,
				),
				'startsAt'        => '2022-09-15 09:11:00',
				'endsAt'          => '2049-10-20 09:11:00',
				'campaign'        => null,
				'claim'           => 'o en 3 plazos sin coste',
				'description'     => "Elige el plan de pago que prefieras. Solo con tu n\u00famero de DNI\/NIE, m\u00f3vil y tarjeta.",
				'icon'            => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n<svg id=\"Capa_1\" xmlns=\"http=>\/\/www.w3.org\/2000\/svg\" height=\"40\" width=\"92\" viewBox=\"0 0 129 56\">\r\n  <defs>\r\n    <style>\r\n      .cls-1 {\r\n        fill: #00c2a3;\r\n      }\r\n      .cls-2 {\r\n        fill: #fff;\r\n        fill-rule: evenodd;\r\n      }\r\n    <\/style>\r\n  <\/defs>\r\n  <rect class=\"cls-1\" width=\"129\" height=\"56\" rx=\"8.2\" ry=\"8.2\"\/>\r\n  <g>\r\n    <path class=\"cls-2\" d=\"M69.3,36.45c-.67-.02-1.36-.13-2.05-.32,1.29-1.55,2.21-3.41,2.65-5.41,.65-2.96,.22-6.06-1.21-8.73-1.43-2.67-3.78-4.76-6.61-5.87-1.52-.6-3.12-.9-4.76-.9-1.4,0-2.78,.22-4.1,.67-2.88,.97-5.33,2.93-6.9,5.53-1.57,2.59-2.16,5.67-1.67,8.65,.49,2.98,2.04,5.7,4.36,7.67,2.23,1.88,5.07,2.95,8.02,3.03,.32,0,.61-.13,.83-.38,.16-.19,.25-.45,.25-.72v-2.12c0-.6-.47-1.07-1.07-1.09-1.93-.07-3.78-.79-5.23-2.01-1.54-1.3-2.57-3.1-2.89-5.07-.32-1.97,.07-4.01,1.1-5.72,1.04-1.72,2.67-3.02,4.58-3.65,.88-.3,1.79-.45,2.73-.45,1.08,0,2.14,.2,3.15,.6,1.89,.74,3.44,2.12,4.37,3.88,.95,1.77,1.23,3.82,.8,5.77-.33,1.52-1.09,2.93-2.2,4.07-.73-.75-1.32-1.63-1.75-2.64-.4-.94-.61-1.93-.65-2.95-.02-.6-.5-1.07-1.09-1.07h-2.13c-.28,0-.55,.1-.73,.26-.24,.21-.38,.51-.38,.84,.04,1.57,.37,3.1,.98,4.56,.65,1.55,1.58,2.94,2.78,4.14,1.2,1.19,2.6,2.12,4.17,2.77,1.47,.61,3.01,.93,4.59,.97h.02c.32,0,.62-.14,.83-.38,.16-.19,.25-.45,.25-.72v-2.1c.02-.29-.08-.56-.28-.77-.2-.21-.48-.34-.77-.35Z\"\/>\r\n    <path class=\"cls-2\" d=\"M21.14,28.97c-.63-.45-1.3-.79-1.99-1.02-.75-.26-1.52-.5-2.31-.69-.57-.12-1.11-.26-1.6-.42-.47-.14-.91-.32-1.3-.53l-.08-.03c-.15-.07-.27-.15-.39-.23-.1-.07-.2-.16-.26-.22-.05-.05-.08-.1-.08-.12-.02-.06-.02-.11-.02-.16,0-.15,.07-.3,.19-.43,.15-.17,.35-.3,.6-.41,.27-.12,.58-.2,.94-.25,.24-.03,.48-.05,.73-.05,.12,0,.24,0,.4,.02,.58,.02,1.14,.16,1.61,.41,.31,.15,1.01,.73,1.56,1.22,.14,.12,.32,.19,.51,.19,.16,0,.31-.05,.43-.13l2.22-1.52c.17-.12,.29-.3,.33-.5,.04-.21,0-.41-.13-.58-.58-.85-1.4-1.53-2.34-1.98-1.13-.58-2.4-.92-3.76-1.01-.27-.02-.54-.03-.81-.03-.62,0-1.23,.05-1.83,.15-.82,.13-1.62,.38-2.4,.75-.73,.35-1.38,.87-1.89,1.52-.52,.67-.82,1.47-.87,2.3-.09,.83,.07,1.66,.48,2.4,.37,.63,.9,1.17,1.53,1.57,.64,.4,1.34,.73,2.13,.98,.85,.28,1.65,.51,2.43,.7,.43,.11,.87,.23,1.36,.38,.38,.12,.71,.26,1.01,.45l.08,.04c.21,.13,.38,.29,.49,.45,.1,.16,.14,.32,.12,.56,0,.21-.09,.4-.22,.54-.17,.19-.41,.35-.6,.44h-.06l-.06,.02c-.33,.14-.69,.23-1.09,.28-.24,.03-.48,.04-.72,.04-.17,0-.35,0-.54-.02-.78-.02-1.55-.24-2.23-.62-.52-.33-.97-.71-1.34-1.12-.14-.16-.35-.26-.57-.26-.15,0-.31,.04-.45,.14l-2.34,1.62c-.18,.12-.3,.32-.33,.53-.03,.21,.03,.43,.17,.6,.72,.88,1.64,1.59,2.61,2.03l.04,.04,.05,.02c1.34,.56,2.73,.87,4.15,.93,.33,.02,.66,.04,1,.04,.59,0,1.18-.04,1.78-.11,.89-.11,1.75-.36,2.58-.73,.78-.37,1.48-.92,2-1.59,.56-.75,.88-1.65,.92-2.56,.08-.8-.07-1.61-.41-2.34-.32-.64-.8-1.22-1.41-1.67Z\"\/>\r\n    <path class=\"cls-2\" d=\"M112.44,20.49c-4.91,0-8.9,3.92-8.9,8.75s3.99,8.75,8.9,8.75c2.55,0,4.02-1.01,4.72-1.68v.6c0,.6,.49,1.09,1.09,1.09h2c.6,0,1.09-.49,1.09-1.09v-7.66c0-4.82-3.99-8.75-8.9-8.75Zm0,4.14c2.59,0,4.69,2.07,4.69,4.6s-2.11,4.6-4.69,4.6-4.7-2.06-4.7-4.6,2.11-4.6,4.7-4.6Z\"\/>\r\n    <path class=\"cls-2\" d=\"M101.34,20.5h0c-1.07,.04-2.11,.26-3.09,.67-1.1,.44-2.08,1.09-2.92,1.91-.83,.82-1.49,1.79-1.94,2.87-.41,.98-.63,2-.65,3.1v7.86c0,.6,.5,1.09,1.11,1.09h2.02c.61,0,1.11-.49,1.11-1.09v-7.89c.02-.52,.14-1.02,.34-1.5,.24-.57,.58-1.08,1.02-1.51,.44-.43,.96-.77,1.54-1.01,.47-.2,.99-.31,1.55-.35,.59-.03,1.06-.51,1.06-1.1v-1.99c-.02-.29-.15-.57-.36-.76-.21-.19-.46-.29-.78-.29Z\"\/>\r\n    <path class=\"cls-2\" d=\"M88.76,20.34h-2c-.6,0-1.1,.49-1.1,1.09v8.44c-.12,1.07-.55,1.97-1.26,2.67-.4,.4-.87,.72-1.39,.93-.54,.22-1.1,.33-1.66,.33s-1.12-.11-1.66-.33c-.53-.21-1-.53-1.39-.93-.71-.71-1.14-1.61-1.26-2.74v-8.37c0-.6-.49-1.09-1.09-1.09h-2c-.6,0-1.09,.49-1.09,1.09v8.07c0,1.14,.22,2.23,.65,3.25,.42,1.02,1.04,1.95,1.85,2.75,.79,.78,1.71,1.4,2.76,1.84,1.05,.43,2.15,.65,3.26,.65s2.24-.22,3.26-.65c1.02-.42,1.95-1.04,2.76-1.84,1.4-1.4,2.26-3.23,2.48-5.29v-8.78c-.01-.6-.51-1.08-1.11-1.08Z\"\/>\r\n    <path class=\"cls-2\" d=\"M34.62,20.53c-.38-.05-.77-.08-1.15-.08-1.73,0-3.41,.51-4.85,1.48-1.77,1.19-3.04,2.97-3.58,5.01-.55,2.05-.33,4.23,.61,6.13,.93,1.9,2.52,3.4,4.49,4.22,1.07,.44,2.19,.66,3.34,.66,.96,0,1.9-.16,2.81-.46,1.49-.52,2.82-1.41,3.83-2.59,.21-.24,.3-.56,.24-.88-.06-.32-.26-.6-.56-.75l-1.58-.82c-.16-.09-.35-.13-.54-.13-.31,0-.61,.12-.82,.34-.54,.52-1.16,.91-1.84,1.14-.51,.17-1.04,.25-1.57,.25-.64,0-1.26-.12-1.84-.37-1.08-.45-1.97-1.28-2.49-2.34-.03-.05-.05-.1-.07-.15h12.07c.61,0,1.1-.49,1.1-1.1v-.91c-.01-2.11-.78-4.14-2.17-5.72-1.4-1.6-3.33-2.63-5.43-2.91Zm-3.86,4.58c.81-.54,1.76-.83,2.73-.83,.22,0,.43,.01,.65,.04,1.18,.16,2.26,.74,3.05,1.63,.35,.4,.63,.85,.84,1.35h-9.07c.37-.89,1-1.66,1.81-2.2Z\"\/>\r\n  <\/g>\r\n<\/svg>\r\n",
				'costDescription' => null,
				'minAmount'       => 0,
				'maxAmount'       => null,
			),
		);
		return rest_ensure_response( $data );
	}
}
