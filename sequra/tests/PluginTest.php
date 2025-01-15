<?php
/**
 * Tests for the Plugin class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests;

use SeQura\WC\Controllers\Hooks\Asset\Interface_Assets_Controller;
use SeQura\WC\Controllers\Hooks\I18n\Interface_I18n_Controller;
use SeQura\WC\Controllers\Hooks\Order\Interface_Order_Controller;
use SeQura\WC\Controllers\Hooks\Payment\Interface_Payment_Controller;
use SeQura\WC\Controllers\Hooks\Process\Interface_Async_Process_Controller;
use SeQura\WC\Controllers\Hooks\Product\Interface_Product_Controller;
use SeQura\WC\Controllers\Hooks\Settings\Interface_Settings_Controller;
use SeQura\WC\Plugin;
use SeQura\WC\Controllers\Rest\REST_Controller;
use SeQura\WC\Services\Interface_Constants;
use SeQura\WC\Services\Migration\Interface_Migration_Manager;
use WP_UnitTestCase;

class PluginTest extends WP_UnitTestCase {

	private $plugin;
	private $constants;
	private $plugin_data;
	private $wp_version;
	private $wc_version;
	private $base_name;
	private $file_path;
	private $i18n_controller;
	private $asset_controller;
	private $settings_controller;
	private $payment_controller;
	private $rest_settings_controller;
	private $rest_onboarding_controller;
	private $rest_payment_controller;
	private $rest_log_controller;
	private $migration_manager;
	private $product_controller;
	private $async_process_controller;
	private $order_controller;

	public function set_up() {

		$this->wp_version  = '5.9';
		$this->wc_version  = '4.0';
		$this->base_name   = 'sequra/sequra.php';
		$this->file_path   = '/var/www/html/wordpress/plugins/sequra/sequra.php';
		$this->plugin_data = array(
			'Name'        => 'seQura',
			'TextDomain'  => 'sequra',
			'DomainPath'  => '/languages',
			'Version'     => '3.0.0',
			'RequiresPHP' => '7.3',
			'RequiresWP'  => '5.9',
			'RequiresWC'  => '4.0',
		);

		$this->constants                  = $this->createMock( Interface_Constants::class );
		$this->i18n_controller            = $this->createMock( Interface_I18n_Controller::class );
		$this->migration_manager          = $this->createMock( Interface_Migration_Manager::class );
		$this->asset_controller           = $this->createMock( Interface_Assets_Controller::class );
		$this->settings_controller        = $this->createMock( Interface_Settings_Controller::class );
		$this->payment_controller         = $this->createMock( Interface_Payment_Controller::class );
		$this->rest_settings_controller   = $this->createMock( REST_Controller::class );
		$this->rest_onboarding_controller = $this->createMock( REST_Controller::class );
		$this->rest_payment_controller    = $this->createMock( REST_Controller::class );
		$this->rest_log_controller        = $this->createMock( REST_Controller::class );
		$this->product_controller         = $this->createMock( Interface_Product_Controller::class );
		$this->async_process_controller   = $this->createMock( Interface_Async_Process_Controller::class );
		$this->order_controller           = $this->createMock( Interface_Order_Controller::class );
	}

	private function setup_plugin_instance() {

		$this->constants->method( 'get_plugin_basename' )->willReturn( $this->base_name );
		$this->constants->method( 'get_plugin_file_path' )->willReturn( $this->file_path );
		$this->constants->method( 'get_environment_data' )->willReturn( array( 'wp_version' => $this->wp_version ) );
		$this->constants->method( 'get_woocommerce_data' )->willReturn( array( 'Version' => $this->wc_version ) );
		$this->constants->method( 'get_plugin_data' )->willReturn( $this->plugin_data );

		$this->plugin = new Plugin( 
			$this->constants,
			$this->migration_manager,
			$this->i18n_controller, 
			$this->asset_controller,
			$this->settings_controller,
			$this->payment_controller,
			$this->rest_settings_controller,
			$this->rest_onboarding_controller,
			$this->rest_payment_controller,
			$this->rest_log_controller,
			$this->product_controller,
			$this->async_process_controller,
			$this->order_controller
		);
	}

	public function testConstructor_happyPath_hooksAreRegistered() {
		$this->setup_plugin_instance();

		$this->assertEquals( 10, has_action( 'plugins_loaded', array( $this->plugin, 'install' ) ) );
		$this->assertEquals( 10, has_action( 'plugins_loaded', array( $this->i18n_controller, 'load_text_domain' ) ) );
		$this->assertEquals( 10, has_action( 'admin_enqueue_scripts', array( $this->asset_controller, 'enqueue_admin' ) ) );
		$this->assertEquals( 10, has_action( 'wp_enqueue_scripts', array( $this->asset_controller, 'enqueue_front' ) ) );
		$this->assertEquals( 10, has_action( 'admin_menu', array( $this->settings_controller, 'register_page' ) ) );
		$this->assertEquals( 10, has_filter( "plugin_action_links_{$this->base_name}", array( $this->settings_controller, 'add_action_link' ) ) );
		$this->assertEquals( 10, has_filter( 'sequra_settings_page_url', array( $this->settings_controller, 'get_settings_page_url' ) ) );
		$this->assertEquals( 10, has_action( 'admin_footer_text', array( $this->settings_controller, 'remove_footer_admin' ) ) );
		$this->assertEquals( 10, has_filter( 'plugin_row_meta', array( $this->settings_controller, 'add_plugin_row_meta' ) ) );
		$this->assertEquals( 10, has_action( 'rest_api_init', array( $this->rest_settings_controller, 'register_routes' ) ) );
		$this->assertEquals( 10, has_action( 'rest_api_init', array( $this->rest_onboarding_controller, 'register_routes' ) ) );
		$this->assertEquals( 10, has_action( 'rest_api_init', array( $this->rest_payment_controller, 'register_routes' ) ) );
		$this->assertEquals( 10, has_action( 'rest_api_init', array( $this->rest_log_controller, 'register_routes' ) ) );
		
		$this->assertEquals( 10, has_filter( 'woocommerce_payment_gateways', array( $this->payment_controller, 'register_gateway_classes' ) ) );
		$this->assertEquals( 10, has_action( 'woocommerce_blocks_loaded', array( $this->payment_controller, 'register_gateway_gutenberg_block' ) ) );
		$this->assertEquals( 10, has_filter( 'woocommerce_thankyou_order_received_text', array( $this->payment_controller, 'order_received_text' ) ) );
		$this->assertEquals( 10, has_filter( 'woocommerce_order_get_payment_method_title', array( $this->payment_controller, 'order_get_payment_method_title' ) ) );
		$this->assertEquals( 10, has_action( 'woocommerce_after_main_content', array( $this->product_controller, 'add_widget_shortcode_to_page' ) ) );
		$this->assertEquals( 10, has_action( 'wp_footer', array( $this->product_controller, 'add_widget_shortcode_to_page' ) ) );
		
		$this->assertEquals( true, shortcode_exists( 'sequra_widget' ) );
		$this->assertEquals( true, shortcode_exists( 'sequra_cart_widget' ) );
		$this->assertEquals( true, shortcode_exists( 'sequra_product_listing_widget' ) );
		
		$this->assertEquals( 10, has_action( 'woocommerce_process_product_meta', array( $this->product_controller, 'save_product_meta' ) ) );
		$this->assertEquals( 10, has_action( 'add_meta_boxes', array( $this->product_controller, 'add_meta_boxes' ) ) );
		$this->assertEquals( 10, has_action( 'sequra_delivery_report', array( $this->async_process_controller, 'send_delivery_report' ) ) );
		$this->assertEquals( 10, has_filter( 'woocommerce_order_data_store_cpt_get_orders_query', array( $this->order_controller, 'handle_custom_query_vars' ) ) );
		$this->assertEquals( 10, has_action( 'woocommerce_order_status_changed', array( $this->order_controller, 'handle_order_status_changed' ) ) );
		$this->assertEquals( 10, has_action( 'woocommerce_admin_order_data_after_order_details', array( $this->order_controller, 'show_link_to_sequra_back_office' ) ) );
		$this->assertEquals( 10, has_action( 'before_woocommerce_init', array( $this->plugin, 'declare_woocommerce_compatibility' ) ) );
	}

	public function testActivate_notMeetPhpRequirements_deactivateAndDie() {

		// A very high PHP version requirement to force the failure.
		$this->plugin_data['RequiresPHP'] = '999';

		$this->setup_plugin_instance();

		$this->plugin->activate();

		$this->assertFalse( \is_plugin_active( $this->base_name ) );
		$this->expectOutputRegex( '/\\nwp_die\(\) called\\nMessage: This plugin requires PHP 999 or greater\./' );
	}

	public function testActivate_notMeetWpRequirements_deactivateAndDie() {

		// A very high WP version requirement to force the failure.
		$this->plugin_data['RequiresWP'] = '999';

		$this->setup_plugin_instance();

		$this->plugin->activate();

		$this->assertFalse( \is_plugin_active( $this->base_name ) );
		$this->expectOutputRegex( '/\\nwp_die\(\) called\\nMessage: This plugin requires WordPress 999 or greater\./' );
	}

	public function testActivate_notMeetWcRequirements_deactivateAndDie() {

		// A very high WP version requirement to force the failure.
		$this->plugin_data['RequiresWC'] = '999';

		$this->setup_plugin_instance();

		$this->plugin->activate();

		$this->assertFalse( \is_plugin_active( $this->base_name ) );
		$this->expectOutputRegex( '/\\nwp_die\(\) called\nMessage: This plugin requires WooCommerce 999 or greater\./' );
	}
}
