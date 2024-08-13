<?php
/**
 * Product Controller
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers
 */

namespace SeQura\WC\Controllers\Hooks\Asset;

use SeQura\Core\BusinessLogic\AdminAPI\AdminAPI;
use SeQura\Core\Infrastructure\Logger\LogContextData;
use SeQura\WC\Controllers\Controller;
use SeQura\WC\Core\Extension\Infrastructure\Configuration\Configuration;
use SeQura\WC\Services\I18n\Interface_I18n;
use SeQura\WC\Services\Interface_Logger_Service;
use SeQura\WC\Services\Payment\Interface_Payment_Method_Service;
use SeQura\WC\Services\Payment\Interface_Payment_Service;
use SeQura\WC\Services\Product\Interface_Product_Service;
use WC_Product;

use function PHPUnit\Framework\returnSelf;

/**
 * Product Controller implementation
 */
class Product_Controller extends Controller implements Interface_Product_Controller {

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
	 * Constructor
	 */
	public function __construct( 
		Interface_Logger_Service $logger,
		string $templates_path, 
		Configuration $configuration,
		Interface_Product_Service $product_service,
		Interface_Payment_Service $payment_service,
		Interface_Payment_Method_Service $payment_method_service,
		Interface_I18n $i18n
	) {
		parent::__construct( $logger, $templates_path );
		$this->logger                 = $logger;
		$this->configuration          = $configuration;
		$this->product_service        = $product_service;
		$this->payment_service        = $payment_service;
		$this->payment_method_service = $payment_method_service;
		$this->i18n                   = $i18n;
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
	 * - theme: The theme to use. Accepted values are: L, R, legacy, legacyL, legacyR, minimal, minimalL, minimalR. Optional.
	 * 
	 * @param array<string, string> $atts The shortcode attributes
	 */
	public function do_widget_shortcode( array $atts ): string {
		$this->logger->log_info( 'Shortcode called', __FUNCTION__, __CLASS__ );

		if ( ! $this->configuration->is_widget_enabled() ) {
			$this->logger->log_info( 'Widget is disabled', __FUNCTION__, __CLASS__ );
			return '';
		}

		// Check for required attributes.
		foreach ( array( 'product', 'product_id' ) as $required ) {
			if ( ! isset( $atts[ $required ] ) ) {
				$this->logger->log_error( "\"$required\" attribute is required", __FUNCTION__, __CLASS__ );
				return '';
			}
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

		$atts = shortcode_atts(
			array(
				'product'      => '',
				'campaign'     => '',
				'product_id'   => '',
				'theme'        => '',
				'reverse'      => 0,
				'reg_amount'   => $this->product_service->get_registration_amount( (int) $atts['product_id'], true ),
				'dest'         => $this->configuration->get_widget_dest_css_sel( $atts['product'] ?? null, $this->i18n->get_current_country() ),
				'price'        => $this->configuration->get_widget_price_css_sel(),
				'alt_price'    => $this->configuration->get_widget_alt_price_css_sel(),
				'is_alt_price' => $this->configuration->get_widget_is_alt_price_css_sel(),
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
					new LogContextData( 'product_id', $atts['product_id'] ),
				) 
			);
			return '';
		}

		ob_start();
		wc_get_template( 'front/widget.php', $atts, '', $this->templates_path );
		return ob_get_clean();
	}

	/**
	 * Add [sequra_widget] to product page automatically
	 */
	public function add_widget_shortcode_to_product_page(): void {
		$this->logger->log_info( 'Hook executed', __FUNCTION__, __CLASS__ );
		if ( did_action( 'before_woocommerce_sequra_add_widget_to_product_page' ) || ! is_product() ) {
			return;
		}

		/**
		 * Fires before the SeQura widget is added to the product page to prevent multiple executions.
		 *
		 * @since 2.1.0.
		 */
		do_action( 'before_woocommerce_sequra_add_widget_to_product_page' );

		/**
		 * The current product.
		 *
		 * @var WC_Product $product
		 */
		global $product;
		
		$methods = array();
		try {
			$store_id = $this->configuration->get_store_id();
			$merchant = $this->payment_service->get_merchant_id();
			$methods  = $this->payment_method_service->get_all_payment_methods( $store_id, $merchant );
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			return;
		}

		foreach ( $methods as $method ) {
			if ( ! $this->product_service->can_display_widget_for_method( $product, $method ) ) {
				continue;
			}
			
			$atts = array(
				'product'    => $method['product'] ?? '',
				'campaign'   => $method['campaign'] ?? '',
				'product_id' => $product->get_id(),
			);

			$atts_str = '';
			foreach ( $atts as $key => $value ) {
				$atts_str .= " $key=\"$value\"";
			}
			
			// $att_dest       = trim( $sequra->settings[ 'dest_css_sel_' . $sq_product ] );
		
			echo do_shortcode( "[sequra_widget $atts_str]" );
		}
	}
}
