<?php
/**
 * Assets Controller
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers
 */

namespace SeQura\WC\Controllers;

use SeQura\WC\Services\Core\Configuration;
use SeQura\WC\Services\Interface_I18n;

/**
 * Define the assets related functionality
 */
class Assets_Controller implements Interface_Assets_Controller {

	private const HANDLE_SETTINGS_PAGE     = 'sequra-settings';
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
	 *
	 * @param string $assets_dir_url URL to the assets directory.
	 * @param string $assets_dir_path Path to the assets directory.
	 * @param string $assets_version Version of the assets.
	 * @param Interface_I18n $i18n I18n service.
	 * @param Configuration $configuration Configuration service.
	 */
	public function __construct( 
		$assets_dir_url, 
		$assets_dir_path, 
		$assets_version, 
		Interface_I18n $i18n, 
		Configuration $configuration 
	) {
		$this->assets_dir_url  = $assets_dir_url;
		$this->assets_dir_path = $assets_dir_path;
		$this->assets_version  = $assets_version;
		$this->i18n            = $i18n;
		$this->configuration   = $configuration;
	}

	/**
	 * Enqueue styles and scripts in WP-Admin
	 */
	public function enqueue_admin() {
		if ( ! $this->configuration->is_settings_page() ) {
			return;
		}
		// Styles.
		wp_enqueue_style( self::HANDLE_CORE, "{$this->assets_dir_url}/css/sequra-core.css", array(), self::INTEGRATION_CORE_VERSION );
		
		// Scripts.
		wp_register_script( self::HANDLE_SETTINGS_PAGE, "{$this->assets_dir_url}/js/settings-page.js", array(), $this->assets_version, true );
		wp_localize_script( self::HANDLE_SETTINGS_PAGE, 'SequraFE', $this->get_sequra_fe_l10n() );
		wp_enqueue_script( self::HANDLE_SETTINGS_PAGE );
	}

	/**
	 * Get the SequraFE object
	 *
	 * @return array
	 */
	private function get_sequra_fe_l10n() {
		$onboarding_page_config = array(
			'getConnectionDataUrl'      => get_rest_url( null, 'sequra/v1/onboarding/data' ),
			'saveConnectionDataUrl'     => get_rest_url( null, 'sequra/v1/onboarding/data' ),
			'validateConnectionDataUrl' => get_rest_url( null, 'sequra/v1/onboarding/data/validate' ),
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
					'disconnectUrl'                     => get_rest_url( null, 'sequra/v1/onboarding/data/disconnect' ), // TODO: Add the URL.
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
			'customHeader'      => array( 'X-WP-Nonce' => wp_create_nonce( 'wp_rest' ) ),
		);

		return $sequra_fe;
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
