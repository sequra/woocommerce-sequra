<?php
/**
 * The core plugin class.
 *
 * @package    SeQura/WC
 */

namespace SeQura\WC;

use SeQura\WC\Controllers\Hooks\Asset\Interface_Assets_Controller;
use SeQura\WC\Controllers\Hooks\Product\Interface_Product_Controller;
use SeQura\WC\Controllers\Hooks\I18n\Interface_I18n_Controller;
use SeQura\WC\Controllers\Hooks\Order\Interface_Order_Controller;
use SeQura\WC\Controllers\Hooks\Payment\Interface_Payment_Controller;
use SeQura\WC\Controllers\Hooks\Process\Interface_Async_Process_Controller;
use SeQura\WC\Controllers\Hooks\Settings\Interface_Settings_Controller;
use SeQura\WC\Controllers\Rest\REST_Controller;
use SeQura\WC\Services\Migration\Interface_Migration_Manager;

/**
 * The core plugin class.
 */
class Plugin {

	/**
	 * The plugin data.
	 * 
	 * @var array<string, string>
	 */
	private $data;

	/**
	 * The plugin base name.
	 * 
	 * @var string
	 */
	private $base_name;

	/**
	 * The plugin file path.
	 * 
	 * @var string
	 */
	private $file_path;

	/**
	 * Migration manager.
	 * 
	 * @var Interface_Migration_Manager
	 */
	private $migration_manager;

	private const HOOK_DELIVERY_REPORT = 'sequra_delivery_report';

	/**
	 * Construct the plugin and bind hooks with controllers.
	 */
	public function __construct(
		array $data,
		string $file_path,
		string $base_name,
		Interface_Migration_Manager $migration_manager,
		Interface_I18n_Controller $i18n_controller,
		Interface_Assets_Controller $assets_controller,
		Interface_Settings_Controller $settings_controller,
		Interface_Payment_Controller $payment_controller,
		REST_Controller $rest_settings_controller,
		REST_Controller $rest_onboarding_controller,
		REST_Controller $rest_payment_controller,
		REST_Controller $rest_log_controller,
		Interface_Product_Controller $product_controller,
		Interface_Async_Process_Controller $async_process_controller,
		Interface_Order_Controller $order_controller
	) {
		$this->data              = $data;
		$this->file_path         = $file_path;
		$this->base_name         = $base_name;
		$this->migration_manager = $migration_manager;

		add_action( 'plugins_loaded', array( $this, 'install' ) );

		// I18n.
		add_action( 'plugins_loaded', array( $i18n_controller, 'load_text_domain' ) );

		// Assets hooks.
		add_action( 'admin_enqueue_scripts', array( $assets_controller, 'enqueue_admin' ) );
		add_action( 'wp_enqueue_scripts', array( $assets_controller, 'enqueue_front' ) );

		// Settings hooks.
		add_action( 'admin_menu', array( $settings_controller, 'register_page' ) );
		add_filter( "plugin_action_links_{$base_name}", array( $settings_controller, 'add_action_link' ), 10, 4 );
		add_filter( 'sequra_settings_page_url', array( $settings_controller, 'get_settings_page_url' ) );
		add_filter( 'admin_footer_text', array( $settings_controller, 'remove_footer_admin' ) );
		add_filter( 'plugin_row_meta', array( $settings_controller, 'add_plugin_row_meta' ), 10, 2 );

		// REST Controllers.
		add_action( 'rest_api_init', array( $rest_settings_controller, 'register_routes' ) );
		add_action( 'rest_api_init', array( $rest_onboarding_controller, 'register_routes' ) );
		add_action( 'rest_api_init', array( $rest_payment_controller, 'register_routes' ) );
		add_action( 'rest_api_init', array( $rest_log_controller, 'register_routes' ) );

		// Payment hooks.
		add_filter( 'woocommerce_payment_gateways', array( $payment_controller, 'register_gateway_classes' ) );
		add_action( 'woocommerce_blocks_loaded', array( $payment_controller, 'register_gateway_gutenberg_block' ) );
		
		add_filter( 'woocommerce_thankyou_order_received_text', array( $payment_controller, 'order_received_text' ), 10, 2 );
		add_filter( 'woocommerce_order_get_payment_method_title', array( $payment_controller, 'order_get_payment_method_title' ), 10, 2 );
		
		// Product widget.
		add_action( 'woocommerce_after_main_content', array( $product_controller, 'add_widget_shortcode_to_page' ) );
		add_action( 'wp_footer', array( $product_controller, 'add_widget_shortcode_to_page' ) );

		add_shortcode( 'sequra_widget', array( $product_controller, 'do_widget_shortcode' ) );
		add_shortcode( 'sequra_cart_widget', array( $product_controller, 'do_cart_widget_shortcode' ) );
		add_shortcode( 'sequra_product_listing_widget', array( $product_controller, 'do_product_listing_widget_shortcode' ) );
		
		add_action( 'add_meta_boxes', array( $product_controller, 'add_meta_boxes' ) );
		add_action( 'woocommerce_process_product_meta', array( $product_controller, 'save_product_meta' ) );

		// Delivery report.
		add_action( self::HOOK_DELIVERY_REPORT, array( $async_process_controller, 'send_delivery_report' ) );
		add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', array( $order_controller, 'handle_custom_query_vars' ), 10, 3 );
		
		// Order Update.
		add_action( 'woocommerce_order_status_changed', array( $order_controller, 'handle_order_status_changed' ), 10, 4 );
		add_action( 'woocommerce_admin_order_data_after_order_details', array( $order_controller, 'show_link_to_sequra_back_office' ) );

		// WooCommerce Compat.
		add_action( 'before_woocommerce_init', array( $this, 'declare_woocommerce_compatibility' ) );
	}

	/**
	 * Handle activation of the plugin.
	 */
	public function activate(): void {
		if ( version_compare( PHP_VERSION, $this->data['RequiresPHP'], '<' ) ) {
			deactivate_plugins( $this->base_name );
			wp_die( esc_html( 'This plugin requires PHP ' . $this->data['RequiresPHP'] . ' or greater.' ) );
		}

		global $wp_version;
		if ( version_compare( $wp_version, $this->data['RequiresWP'], '<' ) ) {
			deactivate_plugins( $this->base_name );
			wp_die( esc_html( 'This plugin requires WordPress ' . $this->data['RequiresWP'] . ' or greater.' ) );
		}

		if ( ! defined( 'WC_VERSION' ) || version_compare( WC_VERSION, $this->data['RequiresWC'], '<' ) ) {
			deactivate_plugins( $this->base_name );
			wp_die( esc_html( 'This plugin requires WooCommerce ' . $this->data['RequiresWC'] . ' or greater.' ) );
		}
	}

	/**
	 * Handle deactivation of the plugin.
	 */
	public function deactivate(): void {
		wp_clear_scheduled_hook( self::HOOK_DELIVERY_REPORT );
	}

	/**
	 * Execute the installation process if needed.
	 */
	public function install(): void {
		if ( ! wp_next_scheduled( self::HOOK_DELIVERY_REPORT ) ) {
			$random_offset = wp_rand( 0, 25200 ); // 60*60*7 seconds from 2AM to 8AM.
			$tomorrow      = gmdate( 'Y-m-d 02:00', strtotime( 'tomorrow' ) );
			$time          = $random_offset + strtotime( $tomorrow );
			wp_schedule_event( $time, 'daily', self::HOOK_DELIVERY_REPORT );
		}
		$this->migration_manager->run_install_migrations();
	}

	/**
	 * Declare WooCommerce compatibility.
	 */
	public function declare_woocommerce_compatibility() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', $this->file_path, true );
		}
	}
}
