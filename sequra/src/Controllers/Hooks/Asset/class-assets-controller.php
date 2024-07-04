<?php
/**
 * Assets Controller
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers
 */

namespace SeQura\WC\Controllers\Hooks\Asset;

use SeQura\WC\Controllers\Controller;
use SeQura\WC\Services\Core\Configuration;
use SeQura\WC\Services\I18n\Interface_I18n;
use SeQura\WC\Services\Interface_Logger_Service;

/**
 * Define the assets related functionality
 */
class Assets_Controller extends Controller implements Interface_Assets_Controller {

	private const HANDLE_SETTINGS_PAGE     = 'sequra-settings';
	private const HANDLE_CHECKOUT          = 'sequra-checkout';
	private const HANDLE_CORE              = 'sequra-core';
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
	 * @var Configuration
	 */
	private $configuration;

	/**
	 * Constructor
	 */
	public function __construct( 
		string $assets_dir_url, 
		string $assets_dir_path, 
		string $assets_version, 
		Interface_I18n $i18n, 
		Interface_Logger_Service $logger,
		string $templates_path, 
		Configuration $configuration 
	) {
		parent::__construct( $logger, $templates_path );
		$this->assets_dir_url  = $assets_dir_url;
		$this->assets_dir_path = $assets_dir_path;
		$this->assets_version  = $assets_version;
		$this->i18n            = $i18n;
		$this->logger          = $logger;
		$this->configuration   = $configuration;
	}

	/**
	 * Enqueue styles and scripts in WP-Admin
	 */
	public function enqueue_admin(): void {
		$this->logger->log_info( 'Hook executed', __FUNCTION__, __CLASS__ );
		if ( ! $this->configuration->is_settings_page() ) {
			return;
		}
		// Styles.
		wp_enqueue_style( self::HANDLE_CORE, "{$this->assets_dir_url}/css/sequra-core.css", array(), self::INTEGRATION_CORE_VERSION );
		
		// Scripts.
		wp_register_script( self::HANDLE_SETTINGS_PAGE, "{$this->assets_dir_url}/js/dist/page/settings.min.js", array(), $this->assets_version, true );
		wp_localize_script( self::HANDLE_SETTINGS_PAGE, 'SequraFE', $this->get_sequra_fe_l10n() );
		wp_enqueue_script( self::HANDLE_SETTINGS_PAGE );
	}

	/**
	 * Get the SequraFE object
	 *
	 * @return mixed[]
	 */
	private function get_sequra_fe_l10n(): array {
		$connection_config      = array(
			'getConnectionDataUrl' => get_rest_url( null, 'sequra/v1/onboarding/data/{storeId}' ),
		);
		$payment_page_config    = array_merge(
			$connection_config,
			array(
				'getPaymentMethodsUrl'      => get_rest_url( null, 'sequra/v1/payment/methods/{storeId}/{merchantId}' ),
				'getAllPaymentMethodsUrl'   => get_rest_url( null, 'sequra/v1/payment/methods/{storeId}' ),
				'getSellingCountriesUrl'    => get_rest_url( null, 'sequra/v1/onboarding/countries/selling/{storeId}' ),
				'getCountrySettingsUrl'     => get_rest_url( null, 'sequra/v1/onboarding/countries/{storeId}' ),
				'validateConnectionDataUrl' => get_rest_url( null, 'sequra/v1/onboarding/data/validate/{storeId}' ),
			)
		);
		$onboarding_page_config = array_merge(
			$payment_page_config, 
			array(
				'saveConnectionDataUrl'  => get_rest_url( null, 'sequra/v1/onboarding/data/{storeId}' ),
				'saveCountrySettingsUrl' => get_rest_url( null, 'sequra/v1/onboarding/countries/{storeId}' ),
				'getWidgetSettingsUrl'   => get_rest_url( null, 'sequra/v1/onboarding/widgets/{storeId}' ),
				'saveWidgetSettingsUrl'  => get_rest_url( null, 'sequra/v1/onboarding/widgets/{storeId}' ),
			)
		);
		$page_config            = array(
			'onboarding'   => $onboarding_page_config,
			'settings'     => array_merge(
				$onboarding_page_config,
				array(
					'getShopPaymentMethodsUrl'          => '', // Not used in this implementation.
					'getShopCategoriesUrl'              => get_rest_url( null, 'sequra/v1/settings/shop-categories/{storeId}' ),
					'getGeneralSettingsUrl'             => get_rest_url( null, 'sequra/v1/settings/general/{storeId}' ),
					'saveGeneralSettingsUrl'            => get_rest_url( null, 'sequra/v1/settings/general/{storeId}' ),
					'getShopOrderStatusesUrl'           => get_rest_url( null, 'sequra/v1/settings/order-status/list/{storeId}' ),
					'getOrderStatusMappingSettingsUrl'  => get_rest_url( null, 'sequra/v1/settings/order-status/{storeId}' ),
					'saveOrderStatusMappingSettingsUrl' => get_rest_url( null, 'sequra/v1/settings/order-status/{storeId}' ),
					'disconnectUrl'                     => get_rest_url( null, 'sequra/v1/onboarding/data/disconnect/{storeId}' ),
				)
			),
			'payment'      => $payment_page_config,
			'transactions' => array_merge(
				$connection_config,
				array(
					'getTransactionLogsUrl' => get_rest_url( null, 'sequra/v1/log/{storeId}' ),
				)
			),
			'advanced'     => array_merge(
				$connection_config,
				array(
					'getLogsUrl'          => get_rest_url( null, 'sequra/v1/log/{storeId}' ),
					'removeLogsUrl'       => get_rest_url( null, 'sequra/v1/log/{storeId}' ),
					'getLogsSettingsUrl'  => get_rest_url( null, 'sequra/v1/log/settings/{storeId}' ),
					'saveLogsSettingsUrl' => get_rest_url( null, 'sequra/v1/log/settings/{storeId}' ),
				)
			),
		);

		$state_controller = array(
			'storesUrl'         => get_rest_url( null, 'sequra/v1/settings/stores/{storeId}' ),
			'currentStoreUrl'   => get_rest_url( null, 'sequra/v1/settings/current-store' ),
			'stateUrl'          => get_rest_url( null, 'sequra/v1/settings/state/{storeId}' ),
			'versionUrl'        => get_rest_url( null, 'sequra/v1/settings/version/{storeId}' ),
			'shopNameUrl'       => get_rest_url( null, 'sequra/v1/settings/shop-name/{storeId}' ),
			'pageConfiguration' => $page_config,
		);

		$pages_payment = array( 'methods' );
		$sequra_fe     = array(
			'translations'      => array(
				'default' => $this->load_translation(),
				'current' => $this->load_translation( $this->i18n->get_lang() ),
			),
			'pages'             => array(
				'onboarding' => array( 'connect', 'countries', 'widgets' ),
				// 'onboarding'   => array( 'connect', 'countries' ),
				// 'settings'     => array( 'general', 'connection', 'order_status' ),
				'settings'   => array( 'general', 'connection', 'order_status', 'widget' ),
				'payment'    => $pages_payment,
				// 'transactions' => array( 'logs' ),
				'advanced'   => array( 'debug' ),
			),
			'integration'       => array(
				'authToken'    => '', // Not used in this implementation.
				'isMultistore' => count( $this->configuration->get_stores() ) > 1,
				'hasVersion'   => version_compare( $this->configuration->get_marketplace_version(), $this->configuration->get_module_version(), '>' ),
			),
			'generalSettings'   => array(
				'useHostedPage'               => false,
				'useReplacementPaymentMethod' => false,
				'useAllowedIPAddresses'       => true,
			),
			'isPromotional'     => false,
			'_state_controller' => $state_controller,
			'customHeader'      => array( 'X-WP-Nonce' => wp_create_nonce( 'wp_rest' ) ),
		);

		return $sequra_fe;
	}

	/**
	 * Enqueue styles and scripts in Front-End
	 */
	public function enqueue_front(): void {
		$this->logger->log_info( 'Hook executed', __FUNCTION__, __CLASS__ );

		if ( is_checkout() ) {
			wp_enqueue_style( self::HANDLE_CHECKOUT, "{$this->assets_dir_url}/css/checkout.css", array(), $this->assets_version );
		}
	}

	/**
	 * Load translations from the .json file
	 * 
	 * @param string $lang Language code.
	 * @return mixed[]
	 */
	private function load_translation( $lang = 'en' ): array {
		$path         = "{$this->assets_dir_path}/lang/{$lang}.json";
		$translations = array();
		
		global $wp_filesystem;
		require_once ABSPATH . '/wp-admin/includes/file.php';
		if ( ! WP_Filesystem() ) {
			return $translations;
		}

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
