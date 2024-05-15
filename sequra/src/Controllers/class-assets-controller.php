<?php
/**
 * Assets Controller
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers
 */

namespace SeQura\WC\Controllers;

/**
 * Define the assets related functionality
 */
class Assets_Controller implements Interface_Assets_Controller {

	private const HANDLE_CORE                                    = 'sequra-core';
	private const HANDLE_CORE_IMAGES_PROVIDER                    = self::HANDLE_CORE . '-images-provider';
	private const HANDLE_CORE_AJAX_SERVICE                       = self::HANDLE_CORE . '-ajax-service';
	private const HANDLE_CORE_TRANSLATION_SERVICE                = self::HANDLE_CORE . '-translation-service';
	private const HANDLE_CORE_TEMPLATE_SERVICE                   = self::HANDLE_CORE . '-template-service';
	private const HANDLE_CORE_UTILITY_SERVICE                    = self::HANDLE_CORE . '-utility-service';
	private const HANDLE_CORE_VALIDATION_SERVICE                 = self::HANDLE_CORE . '-validation-service';
	private const HANDLE_CORE_RESPONSE_SERVICE                   = self::HANDLE_CORE . '-response-service';
	private const HANDLE_CORE_PAGECONTROLLER_FACTORY             = self::HANDLE_CORE . '-pagecontroller-factory';
	private const HANDLE_CORE_FORM_FACTORY                       = self::HANDLE_CORE . '-form-factory';
	private const HANDLE_CORE_STATE_UUID_SERVICE                 = self::HANDLE_CORE . '-state-uuid-service';
	private const HANDLE_CORE_ELEMENT_GENERATOR                  = self::HANDLE_CORE . '-element-generator';
	private const HANDLE_CORE_MODAL_COMPONENT                    = self::HANDLE_CORE . '-modal-component';
	private const HANDLE_CORE_DROPDOWN_COMPONENT                 = self::HANDLE_CORE . '-dropdown-component';
	private const HANDLE_CORE_MULTI_ITEM_SELECTOR_COMPONENT      = self::HANDLE_CORE . '-multi-item-selector-component';
	private const HANDLE_CORE_DATA_TABLE_COMPONENT               = self::HANDLE_CORE . '-data-table-component';
	private const HANDLE_CORE_PAGE_HEADER_COMPONENT              = self::HANDLE_CORE . '-page-header-component';
	private const HANDLE_CORE_CONNECTION_SETTINGS_FORM           = self::HANDLE_CORE . '-connection-settings-form';
	private const HANDLE_CORE_WIDGET_SETTINGS_FORM               = self::HANDLE_CORE . '-widget-settings-form';
	private const HANDLE_CORE_GENERAL_SETTINGS_FORM              = self::HANDLE_CORE . '-general-settings-form';
	private const HANDLE_CORE_ORDER_STATUS_MAPPING_SETTINGS_FORM = self::HANDLE_CORE . '-order-status-mapping-settings-form';
	private const HANDLE_CORE_STATE_CONTROLLER                   = self::HANDLE_CORE . '-state-controller';
	private const HANDLE_CORE_ONBOARDING_CONTROLLER              = self::HANDLE_CORE . '-onboarding-controller';
	private const HANDLE_CORE_PAYMENT_CONTROLLER                 = self::HANDLE_CORE . '-payment-controller';
	private const HANDLE_CORE_SETTINGS_CONTROLLER                = self::HANDLE_CORE . '-settings-controller';
	
	private const INTEGRATION_CORE_VERSION = '1.0.0';

	/**
	 * URL to the assets directory
	 *
	 * @var string
	 */
	private $assets_dir_url;

	/**
	 * Version of the assets
	 *
	 * @var string
	 */
	private $assets_version;

	/**
	 * Constructor
	 *
	 * @param string $assets_dir_url URL to the assets directory.
	 * @param string $assets_version Version of the assets.
	 */
	public function __construct( $assets_dir_url, $assets_version ) {
		$this->assets_dir_url = $assets_dir_url;
		$this->assets_version = $assets_version;
	}

	/**
	 * Enqueue styles and scripts in WP-Admin
	 */
	public function enqueue_admin() {

		$screen = get_current_screen();
		if ( ! $screen || 'settings_page_sequra' !== $screen->id ) {
			return;
		}
		// Styles.
		wp_enqueue_style( self::HANDLE_CORE, "{$this->assets_dir_url}/css/sequra-core.css", array(), self::INTEGRATION_CORE_VERSION );
		
		// Scripts.
		$onboarding_page_config = array(
			'getConnectionDataUrl'      => '', // TODO: Add the URL.
			'saveConnectionDataUrl'     => '', // TODO: Add the URL.
			'validateConnectionDataUrl' => '', // TODO: Add the URL.
			'getSellingCountriesUrl'    => '', // TODO: Add the URL.
			'getCountrySettingsUrl'     => '', // TODO: Add the URL.
			'saveCountrySettingsUrl'    => '', // TODO: Add the URL.
			'getPaymentMethodsUrl'      => '', // TODO: Add the URL.
			'getWidgetSettingsUrl'      => '', // TODO: Add the URL.
			'saveWidgetSettingsUrl'     => '', // TODO: Add the URL.
		);

		$page_config = array(
			'onboarding'   => $onboarding_page_config,
			'settings'     => array_merge(
				$onboarding_page_config,
				array(
					'getShopPaymentMethodsUrl'          => '', // TODO: Add the URL.
					'getShopCategoriesUrl'              => '', // TODO: Add the URL.
					'getShopProductsUrl'                => '', // TODO: Add the URL.
					'getGeneralSettingsUrl'             => '', // TODO: Add the URL.
					'saveGeneralSettingsUrl'            => '', // TODO: Add the URL.
					'getShopOrderStatusesUrl'           => '', // TODO: Add the URL.
					'getOrderStatusMappingSettingsUrl'  => '', // TODO: Add the URL.
					'saveOrderStatusMappingSettingsUrl' => '', // TODO: Add the URL.
					'disconnectUrl'                     => '', // TODO: Add the URL.
				)
			),
			'payment'      => array(
				'getPaymentMethodsUrl'      => '', // TODO: Add the URL.
				'getSellingCountriesUrl'    => '', // TODO: Add the URL.
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
			'storesUrl'         => '', // TODO: Add the URL.
			'currentStoreUrl'   => '', // TODO: Add the URL.
			'stateUrl'          => '', // TODO: Add the URL.
			'versionUrl'        => '', // TODO: Add the URL.
			'shopNameUrl'       => '', // TODO: Add the URL.
			'pageConfiguration' => $page_config,
		);

		$pages_payment = array( 'methods' );
		$sequra_fe     = array(
			'translations'      => array(
				'default' => 'en',
				'current' => explode( '_', get_locale() )[0],
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
		);

		wp_register_script( self::HANDLE_CORE_IMAGES_PROVIDER, "{$this->assets_dir_url}/js/ImagesProvider.js", array(), self::INTEGRATION_CORE_VERSION, true );
		wp_localize_script( self::HANDLE_CORE_IMAGES_PROVIDER, 'SequraFE', $sequra_fe );
		wp_register_script( self::HANDLE_CORE_AJAX_SERVICE, "{$this->assets_dir_url}/js/AjaxService.js", array(), self::INTEGRATION_CORE_VERSION, true );
		wp_register_script( self::HANDLE_CORE_TRANSLATION_SERVICE, "{$this->assets_dir_url}/js/TranslationService.js", array(), self::INTEGRATION_CORE_VERSION, true );
		wp_register_script( self::HANDLE_CORE_TEMPLATE_SERVICE, "{$this->assets_dir_url}/js/TemplateService.js", array(), self::INTEGRATION_CORE_VERSION, true );
		wp_register_script( self::HANDLE_CORE_UTILITY_SERVICE, "{$this->assets_dir_url}/js/UtilityService.js", array(), self::INTEGRATION_CORE_VERSION, true );
		wp_register_script( self::HANDLE_CORE_VALIDATION_SERVICE, "{$this->assets_dir_url}/js/ValidationService.js", array(), self::INTEGRATION_CORE_VERSION, true );
		wp_register_script( self::HANDLE_CORE_RESPONSE_SERVICE, "{$this->assets_dir_url}/js/ResponseService.js", array(), self::INTEGRATION_CORE_VERSION, true );
		wp_register_script( self::HANDLE_CORE_PAGECONTROLLER_FACTORY, "{$this->assets_dir_url}/js/PageControllerFactory.js", array(), self::INTEGRATION_CORE_VERSION, true );
		wp_register_script( self::HANDLE_CORE_FORM_FACTORY, "{$this->assets_dir_url}/js/FormFactory.js", array(), self::INTEGRATION_CORE_VERSION, true );
		wp_register_script( self::HANDLE_CORE_STATE_UUID_SERVICE, "{$this->assets_dir_url}/js/StateUUIDService.js", array(), self::INTEGRATION_CORE_VERSION, true );
		wp_register_script( self::HANDLE_CORE_ELEMENT_GENERATOR, "{$this->assets_dir_url}/js/ElementGenerator.js", array(), self::INTEGRATION_CORE_VERSION, true );
		wp_register_script( self::HANDLE_CORE_MODAL_COMPONENT, "{$this->assets_dir_url}/js/ModalComponent.js", array(), self::INTEGRATION_CORE_VERSION, true );
		wp_register_script( self::HANDLE_CORE_DROPDOWN_COMPONENT, "{$this->assets_dir_url}/js/DropdownComponent.js", array(), self::INTEGRATION_CORE_VERSION, true );
		wp_register_script( self::HANDLE_CORE_MULTI_ITEM_SELECTOR_COMPONENT, "{$this->assets_dir_url}/js/MultiItemSelectorComponent.js", array(), self::INTEGRATION_CORE_VERSION, true );
		wp_register_script( self::HANDLE_CORE_DATA_TABLE_COMPONENT, "{$this->assets_dir_url}/js/DataTableComponent.js", array(), self::INTEGRATION_CORE_VERSION, true );
		wp_register_script( self::HANDLE_CORE_PAGE_HEADER_COMPONENT, "{$this->assets_dir_url}/js/PageHeaderComponent.js", array(), self::INTEGRATION_CORE_VERSION, true );
		wp_register_script( self::HANDLE_CORE_CONNECTION_SETTINGS_FORM, "{$this->assets_dir_url}/js/ConnectionSettingsForm.js", array(), self::INTEGRATION_CORE_VERSION, true );
		wp_register_script( self::HANDLE_CORE_WIDGET_SETTINGS_FORM, "{$this->assets_dir_url}/js/WidgetSettingsForm.js", array(), self::INTEGRATION_CORE_VERSION, true );
		wp_register_script( self::HANDLE_CORE_GENERAL_SETTINGS_FORM, "{$this->assets_dir_url}/js/GeneralSettingsForm.js", array(), self::INTEGRATION_CORE_VERSION, true );
		wp_register_script( self::HANDLE_CORE_ORDER_STATUS_MAPPING_SETTINGS_FORM, "{$this->assets_dir_url}/js/OrderStatusMappingSettingsForm.js", array(), self::INTEGRATION_CORE_VERSION, true );
		wp_register_script( self::HANDLE_CORE_STATE_CONTROLLER, "{$this->assets_dir_url}/js/StateController.js", array(), self::INTEGRATION_CORE_VERSION, true );
		wp_register_script( self::HANDLE_CORE_ONBOARDING_CONTROLLER, "{$this->assets_dir_url}/js/OnboardingController.js", array(), self::INTEGRATION_CORE_VERSION, true );
		wp_register_script( self::HANDLE_CORE_PAYMENT_CONTROLLER, "{$this->assets_dir_url}/js/PaymentController.js", array(), self::INTEGRATION_CORE_VERSION, true );
		wp_register_script( self::HANDLE_CORE_SETTINGS_CONTROLLER, "{$this->assets_dir_url}/js/SettingsController.js", array(), self::INTEGRATION_CORE_VERSION, true );
		wp_register_script(
			self::HANDLE_CORE,
			"{$this->assets_dir_url}/js/settings.js",
			array(
				self::HANDLE_CORE_IMAGES_PROVIDER,
				self::HANDLE_CORE_AJAX_SERVICE,
				self::HANDLE_CORE_TRANSLATION_SERVICE,
				self::HANDLE_CORE_TEMPLATE_SERVICE,
				self::HANDLE_CORE_UTILITY_SERVICE,
				self::HANDLE_CORE_VALIDATION_SERVICE,
				self::HANDLE_CORE_RESPONSE_SERVICE,
				self::HANDLE_CORE_PAGECONTROLLER_FACTORY,
				self::HANDLE_CORE_FORM_FACTORY,
				self::HANDLE_CORE_STATE_UUID_SERVICE,
				self::HANDLE_CORE_ELEMENT_GENERATOR,
				self::HANDLE_CORE_MODAL_COMPONENT,
				self::HANDLE_CORE_DROPDOWN_COMPONENT,
				self::HANDLE_CORE_MULTI_ITEM_SELECTOR_COMPONENT,
				self::HANDLE_CORE_DATA_TABLE_COMPONENT,
				self::HANDLE_CORE_PAGE_HEADER_COMPONENT,
				self::HANDLE_CORE_CONNECTION_SETTINGS_FORM,
				self::HANDLE_CORE_WIDGET_SETTINGS_FORM,
				self::HANDLE_CORE_GENERAL_SETTINGS_FORM,
				self::HANDLE_CORE_ORDER_STATUS_MAPPING_SETTINGS_FORM,
				self::HANDLE_CORE_STATE_CONTROLLER,
				self::HANDLE_CORE_ONBOARDING_CONTROLLER,
				self::HANDLE_CORE_PAYMENT_CONTROLLER,
				self::HANDLE_CORE_SETTINGS_CONTROLLER,
			),
			self::INTEGRATION_CORE_VERSION,
			true 
		);

		wp_enqueue_script( self::HANDLE_CORE );
	}

	/**
	 * Enqueue styles and scripts in Front-End
	 */
	public function enqueue_front() {
	}
}
