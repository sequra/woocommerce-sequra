<?php
/**
 * Assets Controller
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers
 */

namespace SeQura\WC\Controllers;

use SeQura\WC\Services\Interface_I18n;
use SeQura\WC\Services\Interface_Settings;

/**
 * Define the assets related functionality
 */
class Assets_Controller implements Interface_Assets_Controller {

	private const HANDLE_SETTINGS_PAGE                           = 'sequra-settings';
	private const HANDLE_CORE                                    = 'sequra-core';
	// private const HANDLE_CORE_IMAGES_PROVIDER                    = self::HANDLE_CORE . '-images-provider';
	// private const HANDLE_CORE_AJAX_SERVICE                       = self::HANDLE_CORE . '-ajax-service';
	// private const HANDLE_CORE_TRANSLATION_SERVICE                = self::HANDLE_CORE . '-translation-service';
	// private const HANDLE_CORE_TEMPLATE_SERVICE                   = self::HANDLE_CORE . '-template-service';
	// private const HANDLE_CORE_UTILITY_SERVICE                    = self::HANDLE_CORE . '-utility-service';
	// private const HANDLE_CORE_VALIDATION_SERVICE                 = self::HANDLE_CORE . '-validation-service';
	// private const HANDLE_CORE_RESPONSE_SERVICE                   = self::HANDLE_CORE . '-response-service';
	// private const HANDLE_CORE_PAGECONTROLLER_FACTORY             = self::HANDLE_CORE . '-pagecontroller-factory';
	// private const HANDLE_CORE_FORM_FACTORY                       = self::HANDLE_CORE . '-form-factory';
	// private const HANDLE_CORE_STATE_UUID_SERVICE                 = self::HANDLE_CORE . '-state-uuid-service';
	// private const HANDLE_CORE_ELEMENT_GENERATOR                  = self::HANDLE_CORE . '-element-generator';
	// private const HANDLE_CORE_MODAL_COMPONENT                    = self::HANDLE_CORE . '-modal-component';
	// private const HANDLE_CORE_DROPDOWN_COMPONENT                 = self::HANDLE_CORE . '-dropdown-component';
	// private const HANDLE_CORE_MULTI_ITEM_SELECTOR_COMPONENT      = self::HANDLE_CORE . '-multi-item-selector-component';
	// private const HANDLE_CORE_DATA_TABLE_COMPONENT               = self::HANDLE_CORE . '-data-table-component';
	// private const HANDLE_CORE_PAGE_HEADER_COMPONENT              = self::HANDLE_CORE . '-page-header-component';
	// private const HANDLE_CORE_CONNECTION_SETTINGS_FORM           = self::HANDLE_CORE . '-connection-settings-form';
	// private const HANDLE_CORE_WIDGET_SETTINGS_FORM               = self::HANDLE_CORE . '-widget-settings-form';
	// private const HANDLE_CORE_GENERAL_SETTINGS_FORM              = self::HANDLE_CORE . '-general-settings-form';
	// private const HANDLE_CORE_ORDER_STATUS_MAPPING_SETTINGS_FORM = self::HANDLE_CORE . '-order-status-mapping-settings-form';
	// private const HANDLE_CORE_STATE_CONTROLLER                   = self::HANDLE_CORE . '-state-controller';
	// private const HANDLE_CORE_ONBOARDING_CONTROLLER              = self::HANDLE_CORE . '-onboarding-controller';
	// private const HANDLE_CORE_PAYMENT_CONTROLLER                 = self::HANDLE_CORE . '-payment-controller';
	// private const HANDLE_CORE_SETTINGS_CONTROLLER                = self::HANDLE_CORE . '-settings-controller';
	
	private const INTEGRATION_CORE_VERSION = '1.0.0';

	/**
	 * URL to the assets directory
	 *
	 * @var string
	 */
	private $assets_dir_url;

	/**
	 * Path to the assets directory
	 *
	 * @var string
	 */
	private $assets_dir_path;

	/**
	 * Version of the assets
	 *
	 * @var string
	 */
	private $assets_version;

	/**
	 * I18n service
	 *
	 * @var Interface_I18n
	 */
	private $i18n;

	/**
	 * Settings service
	 *
	 * @var Interface_Settings
	 */
	private $settings;

	/**
	 * Constructor
	 *
	 * @param string $assets_dir_url URL to the assets directory.
	 * @param string $assets_dir_path Path to the assets directory.
	 * @param string $assets_version Version of the assets.
	 * @param Interface_I18n $i18n I18n service.
	 * @param Interface_Settings $settings Settings service.
	 */
	public function __construct( 
		$assets_dir_url, 
		$assets_dir_path, 
		$assets_version, 
		Interface_I18n $i18n, 
		Interface_Settings $settings 
	) {
		$this->assets_dir_url  = $assets_dir_url;
		$this->assets_dir_path = $assets_dir_path;
		$this->assets_version  = $assets_version;
		$this->i18n            = $i18n;
		$this->settings        = $settings;
	}

	/**
	 * Enqueue styles and scripts in WP-Admin
	 */
	public function enqueue_admin() {
		if ( ! $this->settings->is_settings_page() ) {
			return;
		}
		// Styles.
		wp_enqueue_style( self::HANDLE_CORE, "{$this->assets_dir_url}/css/sequra-core.css", array(), self::INTEGRATION_CORE_VERSION );
		
		// Scripts.
		$onboarding_page_config = array(
			'getConnectionDataUrl'      => get_rest_url( null, 'sequra/v1/onboarding/data' ), // TODO: Add the URL.
			'saveConnectionDataUrl'     => '', // TODO: Add the URL.
			'validateConnectionDataUrl' => '', // TODO: Add the URL.
			'getSellingCountriesUrl'    => get_rest_url( null, 'sequra/v1/payment/selling-countries' ), // TODO: Add the URL.
			'getCountrySettingsUrl'     => get_rest_url( null, 'sequra/v1/onboarding/countries' ), // TODO: Add the URL.
			'saveCountrySettingsUrl'    => '', // TODO: Add the URL.
			'getPaymentMethodsUrl'      => '', // TODO: Add the URL.
			'getWidgetSettingsUrl'      => get_rest_url( null, 'sequra/v1/onboarding/widgets' ), // TODO: Add the URL.
			'saveWidgetSettingsUrl'     => '', // TODO: Add the URL.
		);

		$page_config = array(
			'onboarding'   => $onboarding_page_config,
			'settings'     => array_merge(
				$onboarding_page_config,
				array(
					'getShopPaymentMethodsUrl'          => '', // TODO: Add the URL.
					'getShopCategoriesUrl'              => get_rest_url( null, 'sequra/v1/settings/shop-categories' ), // TODO: Add the URL.
					'getShopProductsUrl'                => '', // TODO: Add the URL.
					'getGeneralSettingsUrl'             => get_rest_url( null, 'sequra/v1/settings/general' ), // TODO: Add the URL.
					'saveGeneralSettingsUrl'            => '', // TODO: Add the URL.
					'getShopOrderStatusesUrl'           => '', // TODO: Add the URL.
					'getOrderStatusMappingSettingsUrl'  => '', // TODO: Add the URL.
					'saveOrderStatusMappingSettingsUrl' => '', // TODO: Add the URL.
					'disconnectUrl'                     => '', // TODO: Add the URL.
				)
			),
			'payment'      => array(
				'getPaymentMethodsUrl'      => get_rest_url( null, 'sequra/v1/payment/methods' ), // TODO: Add the URL.
				'getSellingCountriesUrl'    => get_rest_url( null, 'sequra/v1/payment/selling-countries' ), // TODO: Add the URL.
				'getCountrySettingsUrl'     => '', // TODO: Add the URL.
				'getConnectionDataUrl'      => '', // TODO: Add the URL.
				'validateConnectionDataUrl' => '', // TODO: Add the URL.
			),
			'transactions' => array(
				'getConnectionDataUrl'  => '', // TODO: Add the URL.
				'getTransactionLogsUrl' => '', // TODO: Add the URL.
			),
		);

		$state_controller = array(
			'storesUrl'         => get_rest_url( null, 'sequra/v1/settings/stores' ), // TODO: Add the URL.
			'currentStoreUrl'   => get_rest_url( null, 'sequra/v1/settings/current-store' ), // TODO: Add the URL.
			'stateUrl'          => get_rest_url( null, 'sequra/v1/settings/state' ), // TODO: Add the URL.
			'versionUrl'        => get_rest_url( null, 'sequra/v1/settings/version' ), // TODO: Add the URL.
			'shopNameUrl'       => '', // TODO: Add the URL.
			'pageConfiguration' => $page_config,
		);

		// $translations_default = 
		// how to load .json file as php array?
		

		$pages_payment = array( 'methods' );
		$sequra_fe     = array(
			'translations'      => array(
				'default' => $this->load_translation(),
				'current' => $this->load_translation( $this->i18n->get_lang() ),
			),
			'pages'             => array(
				'onboarding'   => array( 'connect', 'countries' ),
				'settings'     => array( 'general', 'connection', 'order_status' ),
				'payment'      => $pages_payment,
				'transactions' => array( 'logs' ),
			),
			'integration'       => array(
				'authToken'    => '', // TODO: Add the token.
				'isMultistore' => false,
				'hasVersion'   => false,
			),
			'generalSettings'   => array(
				'useHostedPage'               => false,
				'useReplacementPaymentMethod' => false,
				'useAllowedIPAddresses'       => false,
			),
			'isPromotional'     => empty( $pages_payment ),
			'_state_controller' => $state_controller,
			'_nonce'            => wp_create_nonce( 'wp_rest' ),
		);

		// wp_register_script( self::HANDLE_CORE_IMAGES_PROVIDER, "{$this->assets_dir_url}/js/ImagesProvider.js", array(), self::INTEGRATION_CORE_VERSION, true );
		// wp_localize_script( self::HANDLE_CORE_IMAGES_PROVIDER, 'SequraFE', $sequra_fe );
		// wp_register_script( self::HANDLE_CORE_AJAX_SERVICE, "{$this->assets_dir_url}/js/AjaxService.js", array(), self::INTEGRATION_CORE_VERSION, true );
		// wp_register_script( self::HANDLE_CORE_TRANSLATION_SERVICE, "{$this->assets_dir_url}/js/TranslationService.js", array(), self::INTEGRATION_CORE_VERSION, true );
		// wp_register_script( self::HANDLE_CORE_TEMPLATE_SERVICE, "{$this->assets_dir_url}/js/TemplateService.js", array(), self::INTEGRATION_CORE_VERSION, true );
		// wp_register_script( self::HANDLE_CORE_UTILITY_SERVICE, "{$this->assets_dir_url}/js/UtilityService.js", array(), self::INTEGRATION_CORE_VERSION, true );
		// wp_register_script( self::HANDLE_CORE_VALIDATION_SERVICE, "{$this->assets_dir_url}/js/ValidationService.js", array(), self::INTEGRATION_CORE_VERSION, true );
		// wp_register_script( self::HANDLE_CORE_RESPONSE_SERVICE, "{$this->assets_dir_url}/js/ResponseService.js", array(), self::INTEGRATION_CORE_VERSION, true );
		// wp_register_script( self::HANDLE_CORE_PAGECONTROLLER_FACTORY, "{$this->assets_dir_url}/js/PageControllerFactory.js", array(), self::INTEGRATION_CORE_VERSION, true );
		// wp_register_script( self::HANDLE_CORE_FORM_FACTORY, "{$this->assets_dir_url}/js/FormFactory.js", array(), self::INTEGRATION_CORE_VERSION, true );
		// wp_register_script( self::HANDLE_CORE_STATE_UUID_SERVICE, "{$this->assets_dir_url}/js/StateUUIDService.js", array(), self::INTEGRATION_CORE_VERSION, true );
		// wp_register_script( self::HANDLE_CORE_ELEMENT_GENERATOR, "{$this->assets_dir_url}/js/ElementGenerator.js", array(), self::INTEGRATION_CORE_VERSION, true );
		// wp_register_script( self::HANDLE_CORE_MODAL_COMPONENT, "{$this->assets_dir_url}/js/ModalComponent.js", array(), self::INTEGRATION_CORE_VERSION, true );
		// wp_register_script( self::HANDLE_CORE_DROPDOWN_COMPONENT, "{$this->assets_dir_url}/js/DropdownComponent.js", array(), self::INTEGRATION_CORE_VERSION, true );
		// wp_register_script( self::HANDLE_CORE_MULTI_ITEM_SELECTOR_COMPONENT, "{$this->assets_dir_url}/js/MultiItemSelectorComponent.js", array(), self::INTEGRATION_CORE_VERSION, true );
		// wp_register_script( self::HANDLE_CORE_DATA_TABLE_COMPONENT, "{$this->assets_dir_url}/js/DataTableComponent.js", array(), self::INTEGRATION_CORE_VERSION, true );
		// wp_register_script( self::HANDLE_CORE_PAGE_HEADER_COMPONENT, "{$this->assets_dir_url}/js/PageHeaderComponent.js", array(), self::INTEGRATION_CORE_VERSION, true );
		// wp_register_script( self::HANDLE_CORE_CONNECTION_SETTINGS_FORM, "{$this->assets_dir_url}/js/ConnectionSettingsForm.js", array(), self::INTEGRATION_CORE_VERSION, true );
		// wp_register_script( self::HANDLE_CORE_WIDGET_SETTINGS_FORM, "{$this->assets_dir_url}/js/WidgetSettingsForm.js", array(), self::INTEGRATION_CORE_VERSION, true );
		// wp_register_script( self::HANDLE_CORE_GENERAL_SETTINGS_FORM, "{$this->assets_dir_url}/js/GeneralSettingsForm.js", array(), self::INTEGRATION_CORE_VERSION, true );
		// wp_register_script( self::HANDLE_CORE_ORDER_STATUS_MAPPING_SETTINGS_FORM, "{$this->assets_dir_url}/js/OrderStatusMappingSettingsForm.js", array(), self::INTEGRATION_CORE_VERSION, true );
		// wp_register_script( self::HANDLE_CORE_STATE_CONTROLLER, "{$this->assets_dir_url}/js/StateController.js", array(), self::INTEGRATION_CORE_VERSION, true );
		// wp_register_script( self::HANDLE_CORE_ONBOARDING_CONTROLLER, "{$this->assets_dir_url}/js/OnboardingController.js", array(), self::INTEGRATION_CORE_VERSION, true );
		// wp_register_script( self::HANDLE_CORE_PAYMENT_CONTROLLER, "{$this->assets_dir_url}/js/PaymentController.js", array(), self::INTEGRATION_CORE_VERSION, true );
		// wp_register_script( self::HANDLE_CORE_SETTINGS_CONTROLLER, "{$this->assets_dir_url}/js/SettingsController.js", array(), self::INTEGRATION_CORE_VERSION, true );
		// wp_register_script(
		// self::HANDLE_CORE,
		// "{$this->assets_dir_url}/js/settings.js",
		// array(
		// self::HANDLE_CORE_IMAGES_PROVIDER,
		// self::HANDLE_CORE_AJAX_SERVICE,
		// self::HANDLE_CORE_TRANSLATION_SERVICE,
		// self::HANDLE_CORE_TEMPLATE_SERVICE,
		// self::HANDLE_CORE_UTILITY_SERVICE,
		// self::HANDLE_CORE_VALIDATION_SERVICE,
		// self::HANDLE_CORE_RESPONSE_SERVICE,
		// self::HANDLE_CORE_PAGECONTROLLER_FACTORY,
		// self::HANDLE_CORE_FORM_FACTORY,
		// self::HANDLE_CORE_STATE_UUID_SERVICE,
		// self::HANDLE_CORE_ELEMENT_GENERATOR,
		// self::HANDLE_CORE_MODAL_COMPONENT,
		// self::HANDLE_CORE_DROPDOWN_COMPONENT,
		// self::HANDLE_CORE_MULTI_ITEM_SELECTOR_COMPONENT,
		// self::HANDLE_CORE_DATA_TABLE_COMPONENT,
		// self::HANDLE_CORE_PAGE_HEADER_COMPONENT,
		// self::HANDLE_CORE_CONNECTION_SETTINGS_FORM,
		// self::HANDLE_CORE_WIDGET_SETTINGS_FORM,
		// self::HANDLE_CORE_GENERAL_SETTINGS_FORM,
		// self::HANDLE_CORE_ORDER_STATUS_MAPPING_SETTINGS_FORM,
		// self::HANDLE_CORE_STATE_CONTROLLER,
		// self::HANDLE_CORE_ONBOARDING_CONTROLLER,
		// self::HANDLE_CORE_PAYMENT_CONTROLLER,
		// self::HANDLE_CORE_SETTINGS_CONTROLLER,
		// ),
		// self::INTEGRATION_CORE_VERSION,
		// true 
		// );
		wp_register_script( self::HANDLE_SETTINGS_PAGE, "{$this->assets_dir_url}/js/settings-page.js", array(), $this->assets_version, true );
		wp_localize_script( self::HANDLE_SETTINGS_PAGE, 'SequraFE', $sequra_fe );
		wp_enqueue_script( self::HANDLE_CORE );
	}

	/**
	 * Enqueue styles and scripts in Front-End
	 */
	public function enqueue_front() {
	}

	/**
	 * Load translations from the .json file
	 * 
	 * @param string $lang Language code.
	 */
	private function load_translation( $lang = 'en' ): array {
		$path         = "{$this->assets_dir_path}/lang/{$lang}.json";
		$translations = array();
		
		global $wp_filesystem;
		require_once ABSPATH . '/wp-admin/includes/file.php';
		WP_Filesystem();

		if ( ! $wp_filesystem->exists( $path ) ) {
			return $translations;
		}

		$content = $wp_filesystem->get_contents( $path );

		if ( empty( $content ) ) {
			return $translations;
		}

		$content = json_decode( $content, true );
		if ( is_array( $content ) ) {
			$translations = $content;
		}
		return $translations;
	}
}
