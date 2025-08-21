<?php
/**
 * Assets Controller
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers
 */

namespace SeQura\WC\Controllers\Hooks\Asset;

use SeQura\WC\Controllers\Controller;
use SeQura\WC\Core\Extension\Infrastructure\Configuration\Configuration;
use SeQura\WC\Services\Assets\Interface_Assets;
use SeQura\WC\Services\I18n\Interface_I18n;
use SeQura\WC\Services\Interface_Logger_Service;
use SeQura\WC\Services\Payment\Interface_Payment_Method_Service;
use SeQura\WC\Services\Regex\Interface_Regex;
use SeQura\Core\BusinessLogic\CheckoutAPI\CheckoutAPI;
use SeQura\Core\BusinessLogic\CheckoutAPI\PaymentMethods\Requests\GetCachedPaymentMethodsRequest;
use SeQura\Core\BusinessLogic\CheckoutAPI\PaymentMethods\Responses\CachedPaymentMethodsResponse;
use Throwable;

/**
 * Define the assets related functionality
 */
class Assets_Controller extends Controller implements Interface_Assets_Controller {

	private const HANDLE_SETTINGS_PAGE     = 'sequra-settings';
	private const HANDLE_CHECKOUT          = 'sequra-checkout';
	private const HANDLE_WIDGET            = 'sequra-widget';
	private const HANDLE_CONFIG_PARAMS     = 'sequra-config-params';
	private const HANDLE_CORE              = 'sequra-core';
	private const INTEGRATION_CORE_VERSION = '1.0.0';
	private const STRATEGY_DEFER           = 'defer';

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
	 * WP Version
	 * 
	 * @var string
	 */
	private $wp_version;

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
	 * Assets service
	 *
	 * @var Interface_Assets
	 */
	private $assets;

	/**
	 * Payment method service
	 * 
	 * @var Interface_Payment_Method_Service
	 */
	private $payment_method_service;

	/**
	 * Regex service
	 * 
	 * @var Interface_Regex
	 */
	private $regex;

	/**
	 * Constructor
	 */
	public function __construct( 
		string $assets_dir_url, 
		string $assets_dir_path, 
		string $assets_version, 
		string $wp_version, 
		Interface_I18n $i18n, 
		Interface_Logger_Service $logger,
		string $templates_path, 
		Configuration $configuration,
		Interface_Assets $assets,
		Interface_Payment_Method_Service $payment_method_service,
		Interface_Regex $regex
	) {
		parent::__construct( $logger, $templates_path );
		$this->assets_dir_url         = $assets_dir_url;
		$this->assets_dir_path        = $assets_dir_path;
		$this->assets_version         = $assets_version;
		$this->i18n                   = $i18n;
		$this->logger                 = $logger;
		$this->configuration          = $configuration;
		$this->assets                 = $assets;
		$this->payment_method_service = $payment_method_service;
		$this->regex                  = $regex;
		$this->wp_version             = $wp_version;
	}

	/**
	 * Enqueue styles and scripts in WP-Admin
	 * 
	 * @return void
	 */
	public function enqueue_admin() {
		$this->logger->log_debug( 'Hook executed', __FUNCTION__, __CLASS__ );
		if ( ! $this->configuration->is_settings_page() ) {
			return;
		}
		// Styles.
		\wp_enqueue_style( self::HANDLE_CORE, "{$this->assets_dir_url}/integration-core-ui/css/sequra-core.css", array(), self::INTEGRATION_CORE_VERSION );
		
		// Scripts.
		\wp_register_script( self::HANDLE_SETTINGS_PAGE, "{$this->assets_dir_url}/js/dist/page/settings.min.js", array(), $this->assets_version, true );
		\wp_localize_script( self::HANDLE_SETTINGS_PAGE, 'SequraFE', $this->get_sequra_fe_l10n() );
		\wp_enqueue_script( self::HANDLE_SETTINGS_PAGE );
	}

	/**
	 * Get the SequraFE object
	 *
	 * @return mixed[]
	 */
	private function get_sequra_fe_l10n(): array {
		$connection_config      = array(
			'getConnectionDataUrl'          => \get_rest_url( null, 'sequra/v1/onboarding/data/{storeId}' ),
			'getDeploymentsUrl'             => \get_rest_url( null, 'sequra/v1/onboarding/deployments/{storeId}' ),
			'getNotConnectedDeploymentsUrl' => \get_rest_url( null, 'sequra/v1/onboarding/deployments/not-connected/{storeId}' ),
		);
		$payment_page_config    = array_merge(
			$connection_config,
			array(
				'getPaymentMethodsUrl'             => \get_rest_url( null, 'sequra/v1/payment/methods/{storeId}/{merchantId}' ),
				'getAllAvailablePaymentMethodsUrl' => \get_rest_url( null, 'sequra/v1/payment/methods/{storeId}' ),
				'getSellingCountriesUrl'           => \get_rest_url( null, 'sequra/v1/onboarding/countries/selling/{storeId}' ),
				'getCountrySettingsUrl'            => \get_rest_url( null, 'sequra/v1/onboarding/countries/{storeId}' ),
				'validateConnectionDataUrl'        => \get_rest_url( null, 'sequra/v1/onboarding/data/validate/{storeId}' ),
			)
		);
		$onboarding_page_config = array_merge(
			$payment_page_config, 
			array(
				'saveConnectionDataUrl'  => \get_rest_url( null, 'sequra/v1/onboarding/data/{storeId}' ),
				'saveCountrySettingsUrl' => \get_rest_url( null, 'sequra/v1/onboarding/countries/{storeId}' ),
				'getWidgetSettingsUrl'   => \get_rest_url( null, 'sequra/v1/onboarding/widgets/{storeId}' ),
				'saveWidgetSettingsUrl'  => \get_rest_url( null, 'sequra/v1/onboarding/widgets/{storeId}' ),
				'disconnectUrl'          => \get_rest_url( null, 'sequra/v1/onboarding/data/disconnect/{storeId}' ),
				'connectUrl'             => \get_rest_url( null, 'sequra/v1/onboarding/data/connect/{storeId}' ),
			)
		);
		$page_config            = array(
			'onboarding'   => $onboarding_page_config,
			'settings'     => array_merge(
				$onboarding_page_config,
				array(
					'getShopPaymentMethodsUrl'          => '', // Not used in this implementation.
					'getShopCategoriesUrl'              => \get_rest_url( null, 'sequra/v1/settings/shop-categories/{storeId}' ),
					'getGeneralSettingsUrl'             => \get_rest_url( null, 'sequra/v1/settings/general/{storeId}' ),
					'saveGeneralSettingsUrl'            => \get_rest_url( null, 'sequra/v1/settings/general/{storeId}' ),
					'getShopOrderStatusesUrl'           => \get_rest_url( null, 'sequra/v1/settings/order-status/list/{storeId}' ),
					'getOrderStatusMappingSettingsUrl'  => \get_rest_url( null, 'sequra/v1/settings/order-status/{storeId}' ),
					'saveOrderStatusMappingSettingsUrl' => \get_rest_url( null, 'sequra/v1/settings/order-status/{storeId}' ),
				)
			),
			'payment'      => $payment_page_config,
			'transactions' => array_merge(
				$connection_config,
				array(
					'getTransactionLogsUrl' => \get_rest_url( null, 'sequra/v1/log/{storeId}' ),
				)
			),
			'advanced'     => array_merge(
				$connection_config,
				array(
					'getLogsUrl'          => \get_rest_url( null, 'sequra/v1/log/{storeId}' ),
					'removeLogsUrl'       => \get_rest_url( null, 'sequra/v1/log/{storeId}' ),
					'getLogsSettingsUrl'  => \get_rest_url( null, 'sequra/v1/log/settings/{storeId}' ),
					'saveLogsSettingsUrl' => \get_rest_url( null, 'sequra/v1/log/settings/{storeId}' ),
				)
			),
		);

		$state_controller = array(
			'storesUrl'         => \get_rest_url( null, 'sequra/v1/settings/stores/{storeId}' ),
			'currentStoreUrl'   => \get_rest_url( null, 'sequra/v1/settings/current-store' ),
			'stateUrl'          => \get_rest_url( null, 'sequra/v1/settings/state/{storeId}' ),
			'versionUrl'        => \get_rest_url( null, 'sequra/v1/settings/version/{storeId}' ),
			'shopNameUrl'       => \get_rest_url( null, 'sequra/v1/settings/shop-name/{storeId}' ),
			'pageConfiguration' => $page_config,
		);

		$sequra_fe = array(
			'flags'             => array(
				'isShowCheckoutAsHostedPageFieldVisible' => false, // Not used in this implementation.
			),
			'translations'      => array(
				'default' => $this->load_translation(),
				'current' => $this->load_translation( $this->i18n->get_lang() ),
			),
			'pages'             => array(
				'onboarding' => array( 'deployments', 'connect', 'countries', 'widgets' ),
				'settings'   => array( 'general', 'connection', 'order_status', 'widget' ),
				'payment'    => array( 'methods' ),
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
				'useOrderReporting'           => false,
			),
			'isPromotional'     => false,
			'_state_controller' => $state_controller,
			'customHeader'      => array( 'X-WP-Nonce' => \wp_create_nonce( 'wp_rest' ) ),

			'regex'             => array(
				'ip'             => $this->regex->ip( false ),
				'dateOrDuration' => $this->regex->date_or_duration( false ),
			),
			'miniWidgetLabels'  => array(
				'messages'           => $this->configuration->get_mini_widget_default_messages(),
				'messagesBelowLimit' => $this->configuration->get_mini_widget_default_messages_below_limit(),
			),
		);

		return $sequra_fe;
	}

	/**
	 * Get the SequraWidgetFacade object
	 *
	 * @return array<string, mixed>
	 */
	private function get_sequra_widget_facade_l10n(): array {
		return array(
			'widgets'     => array(),
			'miniWidgets' => array(),
		);
	}

	/**
	 * Get the SequraConfigParams object
	 *
	 * @return array<string, mixed>
	 */
	private function get_sequra_config_params_l10n(): array {

		$country  = $this->i18n->get_current_country();
		$merchant = $this->configuration->get_merchant_ref( $country );
		$methods  = array();

		try {
			/** @var array<int, array<string, mixed>> $methods */
			$methods = CheckoutAPI::get()
			->cachedPaymentMethods( $this->configuration->get_store_id() )
			->getCachedPaymentMethodsSupportedOnProductPage( new GetCachedPaymentMethodsRequest( $merchant ?? '', $country, $country ) )
			->toArray();
		} catch ( Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
		}

		return array(
			'scriptUri'         => $this->assets->get_cdn_resource_uri( $this->configuration->get_env(), 'sequra-checkout.min.js' ),
			'thousandSeparator' => \wc_get_price_thousand_separator(),
			'decimalSeparator'  => \wc_get_price_decimal_separator(),
			'locale'            => $this->i18n->get_locale(),
			'merchant'          => $merchant,
			'assetKey'          => $this->configuration->get_assets_key(),
			'products'          => array_column( $methods, 'product' ),
		);
	}

	/**
	 * Get the script arguments to enqueue based on the WP version
	 * 
	 * @return array|bool
	 */
	private function get_script_args( string $strategy, bool $in_footer ) {
		if ( version_compare( $this->wp_version, '6.3', '>=' ) ) {
			return array(
				'strategy'  => $strategy,
				'in_footer' => $in_footer,
			);
		}
		return $in_footer;
	}

	/**
	 * Enqueue styles and scripts in Front-End for the checkout page
	 */
	private function enqueue_front_checkout(): void {
		\wp_enqueue_style( self::HANDLE_CHECKOUT, "{$this->assets_dir_url}/css/checkout.css", array(), $this->assets_version );
		\wp_register_script( 
			self::HANDLE_CHECKOUT,
			"{$this->assets_dir_url}/js/dist/page/checkout.min.js",
			array( self::HANDLE_CONFIG_PARAMS ),
			$this->assets_version,
			$this->get_script_args( self::STRATEGY_DEFER, true )
		);

		/**
		 * Check if the checkout is a Gutenberg block based version
		 *
		 * @since 3.0.0
		 */
		$is_block = apply_filters( 
			'sequra_is_block_checkout', 
			! $this->payment_method_service->is_order_pay_page() // TODO: Remove this condition once the block checkout is implemented in the order pay page.
			&& class_exists( 'Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils' ) 
			&& method_exists( 'Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils', 'is_checkout_block_default' )
			&& \Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils::is_checkout_block_default()
		);

		/**
		 * Set the flag to delay the updated checkout event listener
		 * 
		 * @since 3.2.0
		 * @param bool $is_updated_checkout_listener_delayed
		 * @return bool
		 */
		$is_updated_checkout_listener_delayed = (bool) apply_filters( 'sequra_is_updated_checkout_listener_delayed', false );

		/**
		 * Set the delay for the updated checkout event listener in milliseconds
		 * 
		 * @since 3.2.0
		 * @param int $updated_checkout_listener_delay
		 * @return int
		 */
		$updated_checkout_listener_delay = (int) apply_filters( 'sequra_updated_checkout_listener_delay', 0 );

		\wp_localize_script(
			self::HANDLE_CHECKOUT,
			'SeQuraCheckout',
			array(
				'isBlockCheckout'                  => $is_block,
				'isUpdatedCheckoutListenerDelayed' => $is_updated_checkout_listener_delayed,
				'updatedCheckoutListenerDelay'     => $updated_checkout_listener_delay,
			) 
		);
		\wp_enqueue_script( self::HANDLE_CHECKOUT );
	}

	/**
	 * Enqueue styles and scripts in Front-End for the widgets
	 */
	private function enqueue_front_widgets(): void {
		\wp_enqueue_style( self::HANDLE_WIDGET, "{$this->assets_dir_url}/css/widget.css", array(), $this->assets_version );
		\wp_register_script( 
			self::HANDLE_WIDGET, 
			"{$this->assets_dir_url}/js/dist/page/widget-facade.min.js",
			array( self::HANDLE_CONFIG_PARAMS ),
			$this->assets_version,
			$this->get_script_args( self::STRATEGY_DEFER, false )
		);
		\wp_localize_script( self::HANDLE_WIDGET, 'SequraWidgetFacade', $this->get_sequra_widget_facade_l10n() );
		\wp_enqueue_script( self::HANDLE_WIDGET );
	}

	/**
	 * Check if the current post has the widget shortcode in its content
	 */
	private function has_widget_shortcode(): bool {
		if ( \is_checkout() || \is_cart() || ! \is_singular() ) {
			return false;
		}
		global $post;
		return \has_shortcode( $post->post_content, 'sequra_widget' );
	}

	/**
	 * Check if the current post has the cart widget shortcode in its content
	 */
	private function has_cart_widget_shortcode(): bool {
		if ( \is_checkout() || \is_product() || ! \is_page() ) {
			return false;
		}
		global $post;
		return \has_shortcode( $post->post_content, 'sequra_cart_widget' );
	}

	/**
	 * Check if the current post has the cart widget shortcode in its content
	 */
	private function has_product_listing_widget_shortcode(): bool {
		if ( \is_checkout() || \is_product() || \is_cart() || ! \is_page() ) {
			return false;
		}
		global $post;
		return \has_shortcode( $post->post_content, 'sequra_product_listing_widget' );
	}
	
	/**
	 * Enqueue styles and scripts in Front-End
	 * 
	 * @return void
	 */
	public function enqueue_front() {
		$this->logger->log_debug( 'Hook executed', __FUNCTION__, __CLASS__ );

		\wp_register_script( 
			self::HANDLE_CONFIG_PARAMS, 
			"{$this->assets_dir_url}/js/dist/page/sequra-config-params.min.js",
			array(),
			$this->assets_version,
			$this->get_script_args( self::STRATEGY_DEFER, false )
		);
		\wp_localize_script( self::HANDLE_CONFIG_PARAMS, 'SequraConfigParams', $this->get_sequra_config_params_l10n() );

		if ( $this->payment_method_service->is_checkout() ) {
			$this->enqueue_front_checkout();
		} 
		
		if ( \is_product() 
		|| $this->has_widget_shortcode() 
		|| \is_cart() 
		|| $this->has_cart_widget_shortcode()
		|| \is_product_category() 
		|| \is_product_tag() 
		|| \is_shop()
		|| $this->has_product_listing_widget_shortcode() ) {
			$this->enqueue_front_widgets();
		}
	}

	/**
	 * Load translations from the .json file
	 * 
	 * @param string $lang Language code.
	 * @return mixed[]
	 */
	private function load_translation( $lang = 'en' ): array {
		$path         = "{$this->assets_dir_path}/integration-core-ui/lang/{$lang}.json";
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
