<?php
/**
 * Product Controller
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers
 */

namespace SeQura\WC\Controllers\Hooks\Product;

use SeQura\Core\BusinessLogic\CheckoutAPI\CheckoutAPI;
use SeQura\Core\BusinessLogic\CheckoutAPI\PromotionalWidgets\Requests\PromotionalWidgetsCheckoutRequest;
use SeQura\Core\BusinessLogic\Domain\Integration\PromotionalWidgets\WidgetConfiguratorInterface;
use SeQura\Core\Infrastructure\Logger\LogContextData;
use SeQura\Core\Infrastructure\Utility\RegexProvider;
use SeQura\WC\Controllers\Controller;
use SeQura\WC\Core\Extension\Infrastructure\Configuration\Configuration;
use SeQura\WC\Services\I18n\Interface_I18n;
use SeQura\WC\Services\Interface_Logger_Service;
use SeQura\WC\Services\Payment\Interface_Payment_Method_Service;
use SeQura\WC\Services\Payment\Interface_Payment_Service;
use SeQura\WC\Services\Product\Interface_Product_Service;
use SeQura\WC\Services\Shopper\Interface_Shopper_Service;
use WC_Product;
use WP_Post;

/**
 * Product Controller implementation
 */
class Product_Controller extends Controller implements Interface_Product_Controller {

	private const FIELD_NAME_IS_BANNED                         = 'is_sequra_banned';
	private const FIELD_NAME_IS_SERVICE                        = 'is_sequra_service';
	private const FIELD_NAME_SERVICE_END_DATE                  = 'sequra_service_end_date';
	private const FIELD_NAME_SERVICE_DESIRED_FIRST_CHARGE_DATE = 'sequra_desired_first_charge_date';
	private const FIELD_NAME_REGISTRATION_AMOUNT               = 'sequra_registration_amount';
	private const NONCE_SEQURA_PRODUCT                         = '_sequra_product_nonce';

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
	 * Product service
	 *
	 * @var Interface_Product_Service
	 */
	private $product_service;

	/**
	 * Payment service
	 *
	 * @var Interface_Payment_Service
	 */
	private $payment_service;

	/**
	 * Payment method service
	 *
	 * @var Interface_Payment_Method_Service
	 */
	private $payment_method_service;

	/**
	 * RegEx service
	 *
	 * @var RegexProvider
	 */
	private $regex;

	/**
	 * Shopper service
	 *
	 * @var Interface_Shopper_Service
	 */
	private $shopper_service;

	/**
	 * Widget configurator
	 *
	 * @var WidgetConfiguratorInterface
	 */
	private $widget_configurator;

	/**
	 * Constructor
	 */
	public function __construct( 
		Interface_Logger_Service $logger,
		string $templates_path, 
		Configuration $configuration,
		Interface_Product_Service $product_service,
		Interface_Payment_Service $payment_service,
		Interface_Payment_Method_Service $payment_method_service,
		Interface_I18n $i18n,
		RegexProvider $regex,
		Interface_Shopper_Service $shopper_service,
		WidgetConfiguratorInterface $widget_configurator
	) {
		parent::__construct( $logger, $templates_path );
		$this->logger                 = $logger;
		$this->configuration          = $configuration;
		$this->product_service        = $product_service;
		$this->payment_service        = $payment_service;
		$this->payment_method_service = $payment_method_service;
		$this->i18n                   = $i18n;
		$this->regex                  = $regex;
		$this->shopper_service        = $shopper_service;
		$this->widget_configurator    = $widget_configurator;
	}

	/**
	 * Handle the widget shortcode callback
	 * [sequra_widget]
	 * Expected attributes:
	 * - product: The seQura product identifier. Required.
	 * - campaign: The seQura campaign name. Optional.
	 * - dest: A CSS selector to place the widget. Required.
	 * - product_id: The WooCommerce product identifier. Required
	 * - price: A CSS selector to get the product price. Optional.
	 * - alt_price: An alternative CSS selector to retrieve the product price for special product page layouts. Optional.
	 * - is_alt_price: A CSS selector to determine if the product has an alternative price. Optional.
	 * - reg_amount: The registration amount. Optional.
	 * - theme: The theme to use. Accepted values are: L, R, legacy, legacyL, legacyR, minimal, minimalL, minimalR or JSON formatted string. Optional.
	 * - min_amount: The minimum amount to display the widget. Optional.
	 * - max_amount: The maximum amount to display the widget. Optional.
	 * 
	 * @param array<string, string> $atts The shortcode attributes
	 * @return string
	 */
	public function do_widget_shortcode( $atts ) {
		$this->logger->log_debug( 'Shortcode called', __FUNCTION__, __CLASS__ );
		$atts = (array) $atts;
		
		// Check for required attributes.
		foreach ( array( 'product', 'product_id' ) as $required ) {
			if ( ! isset( $atts[ $required ] ) ) {
				$this->logger->log_error( "\"$required\" attribute is required", __FUNCTION__, __CLASS__ );
				return '';
			}
		}

		$current_country = $this->i18n->get_current_country();
		$campaign        = isset( $atts['campaign'] ) && '' !== $atts['campaign'] ? $atts['campaign'] : null;
		
		if ( ! $this->configuration->is_widget_enabled( $atts['product'], $campaign, $current_country ) ) {
			$this->logger->log_info( 'Widget is disabled', __FUNCTION__, __CLASS__ );
			return '';
		}

		// Replace old attribute names introduced in 2.0.0 with the new ones.
		$atts_replacements = array(
			'variation_price' => 'alt_price',
			'is_variable'     => 'is_alt_price',
		);

		foreach ( $atts_replacements as $old => $new ) {
			if ( isset( $atts[ $old ] ) ) {
				$atts[ $new ] = $atts[ $old ];
				unset( $atts[ $old ] );
			}
		}

		$atts = \shortcode_atts(
			array(
				'product'      => '',
				'campaign'     => '',
				'product_id'   => '',
				'theme'        => '',
				'reverse'      => 0,
				'reg_amount'   => $this->product_service->get_registration_amount( (int) $atts['product_id'], true ),
				'dest'         => '',
				'price'        => '',
				'alt_price'    => '',
				'is_alt_price' => '',
				'min_amount'   => 0,
				'max_amount'   => null,
			),
			$atts,
			'sequra_widget'
		);

		if ( ! $this->product_service->can_display_widget_for_method( (int) $atts['product_id'], $atts['product'] ) ) {
			$this->logger->log_info(
				'Widget cannot be displayed for product', 
				__FUNCTION__,
				__CLASS__,
				array( 
					new LogContextData( 'payment_method', $atts['product'] ),
					new LogContextData( 'campaign', $atts['campaign'] ),
					new LogContextData( 'product_id', $atts['product_id'] ),
				) 
			);
			return '';
		}

		ob_start();
		\wc_get_template( 'front/widget.php', $atts, '', $this->templates_path );
		return ob_get_clean();
	}

	/**
	 * Handle the cart widget shortcode callback
	 * 
	 * @param array<string, string> $atts The shortcode attributes
	 * @return string
	 */
	public function do_cart_widget_shortcode( $atts ) {
		$this->logger->log_debug( 'Shortcode called', __FUNCTION__, __CLASS__ );

		try {
			$country = $this->i18n->get_current_country();

			/**
			 * Fetch promotional widget data from CheckoutAPI
			 *  
			 * @var array<array<string, mixed>> $widgets */
			$widgets = CheckoutAPI::get()->promotionalWidgets( $this->configuration->get_store_id() )
			->getAvailableWidgetForCartPage(
				new PromotionalWidgetsCheckoutRequest(
					$country,
					$country,
					$this->widget_configurator->getCurrency() ?? '',
					$this->shopper_service->get_ip() 
				)
			)
			->toArray();

			if ( empty( $widgets ) ) {
				$this->logger->log_info( 'No cart widget available', __FUNCTION__, __CLASS__ );
				return '';
			}

			/**
			 * The first available widget.
			 *  
			 * @var array<string, mixed> $widget */
			$widget = $widgets[0];

			$atts = \shortcode_atts(
				array(
					'product'             => $widget['product'] ?? '',
					'campaign'            => $widget['campaign'] ?? '',
					'dest'                => $widget['dest'] ?? '',
					'theme'               => $widget['theme'] ?? '',
					'price'               => $widget['priceSel'] ?? '',
					'alt_price'           => $widget['altPriceSel'] ?? '',
					'is_alt_price'        => $widget['altTriggerSelector'] ?? '',
					'message'             => $widget['miniWidgetMessage'],
					'message_below_limit' => $widget['miniWidgetBelowLimitMessage'],
					'min_amount'          => $widget['minAmount'] ?? 0,
					'max_amount'          => $widget['maxAmount'] ?? null,
					'reg_amount'          => 0,
					'reverse'             => 0,
				),
				(array) $atts,
				'sequra_cart_widget'
			);

			ob_start();
			\wc_get_template( 'front/widget.php', $atts, '', $this->templates_path );
			return ob_get_clean();
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			return '';
		}
	}

	/**
	 * Handle the product listing widget shortcode callback
	 * 
	 * @param array<string, string> $atts The shortcode attributes
	 * @return string
	 */
	public function do_product_listing_widget_shortcode( $atts ) {
		$this->logger->log_debug( 'Shortcode called', __FUNCTION__, __CLASS__ );
		
		try {
			$country = $this->i18n->get_current_country();
			/** 
			 * Fetch promotional widget data from CheckoutAPI
			 * 
			 * @var array<int, array<string, mixed>> $widgets */
			$widgets = CheckoutAPI::get()->promotionalWidgets( $this->configuration->get_store_id() )
			->getAvailableMiniWidgetForProductListingPage(
				new PromotionalWidgetsCheckoutRequest(
					$country,
					$country,
					$this->widget_configurator->getCurrency() ?? '',
					$this->shopper_service->get_ip()
				)
			)
			->toArray();

			if ( empty( $widgets ) ) {
				$this->logger->log_info( 'No product listing widget available', __FUNCTION__, __CLASS__ );
				return '';
			}

			/**
			 * The first available widget.
			 *
			 * @var array<string, mixed> $widget */
			$widget = $widgets[0];

			$atts = \shortcode_atts(
				array(
					'product'             => $widget['product'] ?? '',
					'campaign'            => $widget['campaign'] ?? '',
					'dest'                => $widget['dest'],
					'price'               => $widget['priceSel'],
					'message'             => $widget['miniWidgetMessage'],
					'message_below_limit' => $widget['miniWidgetBelowLimitMessage'],
					'min_amount'          => $widget['minAmount'] ?? 0,
					'max_amount'          => $widget['maxAmount'] ?? null,
				),
				(array) $atts,
				'sequra_product_listing_widget'
			);

			ob_start();
			\wc_get_template( 'front/mini_widget.php', $atts, '', $this->templates_path );
			return ob_get_clean();
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			return '';
		}
	}

	/**
	 * Add [sequra_widget] to product page automatically
	 */
	private function add_widget_shortcode_to_product_page(): void {
		if ( ! \is_product() ) {
			return;
		}
		/**
		 * The current product.
		 *
		 * @var WC_Product $product
		 */
		global $product;

		/**
		 * Fetch promotional widget data from CheckoutAPI
		 *
		 * @var array<int, array<string, mixed>> $widgets */
		$widgets = array();
		$country = $this->i18n->get_current_country();

		try {
			$widgets = CheckoutAPI::get()->promotionalWidgets( $this->configuration->get_store_id() )
			->getAvailableWidgetsForProductPage(
				new PromotionalWidgetsCheckoutRequest(
					$country,
					$country,
					$this->widget_configurator->getCurrency() ?? '',
					$this->shopper_service->get_ip(),
					(string) $product->get_id()
				)
			)
			->toArray();
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			return;
		}
			
		foreach ( $widgets as $widget ) {
			$atts = array(
				'product'      => $widget['product'] ?? '',
				'campaign'     => $widget['campaign'] ?? '',
				'product_id'   => $product->get_id(),
				'theme'        => $widget['theme'] ?? '',
				'dest'         => $widget['dest'] ?? '',
				'price'        => $widget['priceSel'] ?? '',
				'alt_price'    => $widget['altPriceSel'] ?? '',
				'is_alt_price' => $widget['altTriggerSelector'] ?? '',
				'min_amount'   => $widget['minAmount'] ?? 0,
				'max_amount'   => $widget['maxAmount'] ?? null,
				
			);
			$atts_str = '';
			foreach ( $atts as $key => $value ) {
				$atts_str .= " $key='$value'";
			}
			echo \do_shortcode( "[sequra_widget $atts_str]" );
		}
	}

	/**
	 * Add [sequra_cart_widget] to product page automatically
	 */
	private function add_widget_shortcode_to_cart_page(): void {
		if ( ! \is_cart() ) {
			return;
		}
		echo \do_shortcode( '[sequra_cart_widget]' );
	}

	/**
	 * Add [sequra_product_listing_widget] to product archive automatically
	 */
	private function add_widget_shortcode_to_product_listing_page(): void {
		if ( ! \is_product_category() && ! \is_product_tag() && ! \is_shop() ) {
			return;
		}
		echo \do_shortcode( '[sequra_product_listing_widget]' );
	}

	/**
	 * Add [sequra_widget] to product page automatically
	 * Add [sequra_cart_widget] to cart page automatically
	 * 
	 * @return void
	 */
	public function add_widget_shortcode_to_page() {
		$this->logger->log_debug( 'Hook executed', __FUNCTION__, __CLASS__ );
		if ( \did_action( 'before_woocommerce_sequra_add_widget_to_page' ) ) {
			return;
		}

		/**
		 * Fires before the SeQura widget is added to the page to prevent multiple executions.
		 *
		 * @since 3.0.0
		 */
		\do_action( 'before_woocommerce_sequra_add_widget_to_page' );

		$this->add_widget_shortcode_to_product_page();
		$this->add_widget_shortcode_to_cart_page();
		$this->add_widget_shortcode_to_product_listing_page();
	}

	/**
	 * Add meta boxes to the product edit page
	 * 
	 * @return void
	 */
	public function add_meta_boxes() {
		\add_meta_box( 'sequra_settings', esc_html__( 'seQura settings', 'sequra' ), array( $this, 'render_meta_boxes' ), 'product', 'side', 'default' );
	}

	/**
	 * Render the meta boxes
	 * 
	 * @param WP_Post $post The post object
	 * @return void
	 */
	public function render_meta_boxes( $post ) {
		$args = array(
			'is_banned'                                    => $this->product_service->get_is_banned( $post->ID ),
			'is_banned_field_name'                         => self::FIELD_NAME_IS_BANNED,
			'enabled_for_services'                         => $this->configuration->is_enabled_for_services(),
			'is_service'                                   => $this->product_service->is_service( $post->ID ),
			'is_service_field_name'                        => self::FIELD_NAME_IS_SERVICE,
			'service_end_date_default'                     => $this->configuration->get_default_services_end_date(),
			'service_end_date'                             => $this->product_service->get_service_end_date( $post->ID, true ),
			'service_end_date_field_name'                  => self::FIELD_NAME_SERVICE_END_DATE,
			'allow_payment_delay'                          => $this->configuration->allow_first_service_payment_delay(),
			'service_desired_first_charge_date'            => $this->product_service->get_desired_first_charge_date( $post->ID, true ) ?? '',
			'service_desired_first_charge_date_field_name' => self::FIELD_NAME_SERVICE_DESIRED_FIRST_CHARGE_DATE,
			'date_or_duration_regex'                       => $this->regex->getDateOrDurationRegex( false ),
			'allow_registration_items'                     => $this->configuration->allow_service_reg_items(),
			'service_registration_amount'                  => $this->product_service->get_registration_amount( $post->ID ),
			'service_registration_amount_field_name'       => self::FIELD_NAME_REGISTRATION_AMOUNT,
			'nonce_name'                                   => self::NONCE_SEQURA_PRODUCT,
		);
		\wc_get_template( 'admin/product_metabox.php', $args, '', $this->templates_path );
	}

	/**
	 * Save product meta
	 * 
	 * @param int $post_id The post ID
	 * @return void
	 */
	public function save_product_meta( $post_id ) {
		if ( ! isset( $_POST[ self::NONCE_SEQURA_PRODUCT ] ) 
		|| ! \wp_verify_nonce( $_POST[ self::NONCE_SEQURA_PRODUCT ], -1 ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		|| ! \current_user_can( 'edit_post', $post_id )
		|| \wp_doing_ajax() ) {
			return;
		}

		$this->product_service->set_is_banned( $post_id, isset( $_POST[ self::FIELD_NAME_IS_BANNED ] ) ? \sanitize_text_field( \wp_unslash( $_POST[ self::FIELD_NAME_IS_BANNED ] ) ) : null );
		$this->product_service->set_is_service( $post_id, isset( $_POST[ self::FIELD_NAME_IS_SERVICE ] ) ? \sanitize_text_field( \wp_unslash( $_POST[ self::FIELD_NAME_IS_SERVICE ] ) ) : null );
		$this->product_service->set_service_end_date( $post_id, ! empty( $_POST[ self::FIELD_NAME_SERVICE_END_DATE ] ) ? \sanitize_text_field( \wp_unslash( $_POST[ self::FIELD_NAME_SERVICE_END_DATE ] ) ) : null );
		$this->product_service->set_desired_first_charge_date( $post_id, ! empty( $_POST[ self::FIELD_NAME_SERVICE_DESIRED_FIRST_CHARGE_DATE ] ) ? \sanitize_text_field( \wp_unslash( $_POST[ self::FIELD_NAME_SERVICE_DESIRED_FIRST_CHARGE_DATE ] ) ) : null );
		$this->product_service->set_registration_amount( $post_id, ! empty( $_POST[ self::FIELD_NAME_REGISTRATION_AMOUNT ] ) ? \sanitize_text_field( \wp_unslash( $_POST[ self::FIELD_NAME_REGISTRATION_AMOUNT ] ) ) : null );
	}
}
