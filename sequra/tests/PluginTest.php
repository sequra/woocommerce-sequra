<?php
/**
 * Tests for the Plugin class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests;

use SeQura\WC\Controllers\Interface_Assets_Controller;
use SeQura\WC\Controllers\Interface_I18n_Controller;
use SeQura\WC\Plugin;
use SeQura\WC\Controllers\Interface_Settings_Controller;
use SeQura\WC\Controllers\Rest\REST_Controller;
use SeQura\WC\Services\Interface_Migration_Manager;
use WP_UnitTestCase;

class PluginTest extends WP_UnitTestCase {


	private $plugin;
	private $plugin_data;
	private $base_name;
	private $i18n_controller;
	private $asset_controller;
	private $settings_controller;
	private $rest_settings_controller;
	private $rest_onboarding_controller;
	private $rest_payment_controller;
	private $rest_log_controller;
	private $migration_manager;

	public function set_up() {

		$this->plugin_data = array(
			'Name'        => 'seQura',
			'TextDomain'  => 'sequra',
			'DomainPath'  => '/languages',
			'Version'     => '3.0.0',
			'RequiresPHP' => '7.3',
			'RequiresWP'  => '5.9',
			'RequiresWC'  => '4.0',
		);

		$this->base_name                  = 'sequra/sequra.php';
		$this->i18n_controller            = $this->createMock( Interface_I18n_Controller::class );
		$this->migration_manager          = $this->createMock( Interface_Migration_Manager::class );
		$this->asset_controller           = $this->createMock( Interface_Assets_Controller::class );
		$this->settings_controller        = $this->createMock( Interface_Settings_Controller::class );
		$this->rest_settings_controller   = $this->createMock( REST_Controller::class );
		$this->rest_onboarding_controller = $this->createMock( REST_Controller::class );
		$this->rest_payment_controller    = $this->createMock( REST_Controller::class );
		$this->rest_log_controller        = $this->createMock( REST_Controller::class );
	}

	public function testConstructor_happyPath_hooksAreRegistered() {
		$this->plugin = new Plugin( 
			$this->plugin_data, 
			$this->base_name,
			$this->migration_manager,
			$this->i18n_controller, 
			$this->asset_controller,
			$this->settings_controller,
			$this->rest_settings_controller,
			$this->rest_onboarding_controller,
			$this->rest_payment_controller,
			$this->rest_log_controller
		);

		$this->assertEquals( 10, \has_action( 'plugins_loaded', array( $this->plugin, 'install' ) ) );
		$this->assertEquals( 10, \has_action( 'plugins_loaded', array( $this->i18n_controller, 'load_text_domain' ) ) );
		$this->assertEquals( 10, \has_action( 'admin_enqueue_scripts', array( $this->asset_controller, 'enqueue_admin' ) ) );
		$this->assertEquals( 10, \has_action( 'wp_enqueue_scripts', array( $this->asset_controller, 'enqueue_front' ) ) );
		$this->assertEquals( 10, \has_action( 'admin_menu', array( $this->settings_controller, 'register_page' ) ) );
		$this->assertEquals( 10, \has_filter( "plugin_action_links_{$this->base_name}", array( $this->settings_controller, 'add_action_link' ) ) );
		$this->assertEquals( 10, \has_action( 'admin_footer_text', array( $this->settings_controller, 'remove_footer_admin' ) ) );
		$this->assertEquals( 10, \has_action( 'rest_api_init', array( $this->rest_settings_controller, 'register_routes' ) ) );
		$this->assertEquals( 10, \has_action( 'rest_api_init', array( $this->rest_onboarding_controller, 'register_routes' ) ) );
		$this->assertEquals( 10, \has_action( 'rest_api_init', array( $this->rest_payment_controller, 'register_routes' ) ) );
		$this->assertEquals( 10, \has_action( 'rest_api_init', array( $this->rest_log_controller, 'register_routes' ) ) );
	}

	public function testActivate_notMeetPhpRequirements_deactivateAndDie() {

		// A very high PHP version requirement to force the failure.
		$this->plugin_data['RequiresPHP'] = '999';

		$this->plugin = new Plugin( 
			$this->plugin_data, 
			$this->base_name,
			$this->migration_manager,
			$this->i18n_controller, 
			$this->asset_controller,
			$this->settings_controller,
			$this->rest_settings_controller,
			$this->rest_onboarding_controller,
			$this->rest_payment_controller,
			$this->rest_log_controller
		);

		$this->plugin->activate();

		$this->assertFalse( \is_plugin_active( $this->base_name ) );
		$this->expectOutputRegex( '/\\nwp_die\(\) called\\nMessage: This plugin requires PHP 999 or greater\./' );
	}

	public function testActivate_notMeetWpRequirements_deactivateAndDie() {

		// A very high WP version requirement to force the failure.
		$this->plugin_data['RequiresWP'] = '999';

		$this->plugin = new Plugin( 
			$this->plugin_data, 
			$this->base_name,
			$this->migration_manager,
			$this->i18n_controller, 
			$this->asset_controller,
			$this->settings_controller,
			$this->rest_settings_controller,
			$this->rest_onboarding_controller,
			$this->rest_payment_controller,
			$this->rest_log_controller
		);

		$this->plugin->activate();

		$this->assertFalse( \is_plugin_active( $this->base_name ) );
		$this->expectOutputRegex( '/\\nwp_die\(\) called\\nMessage: This plugin requires WordPress 999 or greater\./' );
	}

	public function testActivate_notMeetWcRequirements_deactivateAndDie() {

		// A very high WP version requirement to force the failure.
		$this->plugin_data['RequiresWC'] = '999';

		$this->plugin = new Plugin( 
			$this->plugin_data, 
			$this->base_name,
			$this->migration_manager,
			$this->i18n_controller, 
			$this->asset_controller,
			$this->settings_controller,
			$this->rest_settings_controller,
			$this->rest_onboarding_controller,
			$this->rest_payment_controller,
			$this->rest_log_controller
		);

		$this->plugin->activate();

		$this->assertFalse( \is_plugin_active( $this->base_name ) );
		$this->expectOutputRegex( '/\\nwp_die\(\) called\nMessage: This plugin requires WooCommerce 999 or greater\./' );
	}
}
