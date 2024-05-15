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
use PHPUnit\Framework\MockObject\MockObject;
use SeQura\WC\Controllers\Interface_Settings_Controller;
use SeQura\WC\Interface_Bootstrap;
use WP_UnitTestCase;

class PluginTest extends WP_UnitTestCase {


	private $plugin;
	private $plugin_data;
	private $base_name;
	private $bootstrap;
	private $i18n_controller;
	private $asset_controller;
	private $settings_controller;

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

		$this->base_name           = 'sequra/sequra.php';
		$this->bootstrap           = $this->createMock( Interface_Bootstrap::class );
		$this->i18n_controller     = $this->createMock( Interface_I18n_Controller::class );
		$this->asset_controller    = $this->createMock( Interface_Assets_Controller::class );
		$this->settings_controller = $this->createMock( Interface_Settings_Controller::class );
	}

	public function testConstructor_happyPath_hooksAreRegistered() {
		$this->bootstrap->expects( $this->once() )->method( 'do_init' );

		$this->plugin = new Plugin( 
			$this->plugin_data, 
			$this->base_name,
			$this->bootstrap, 
			$this->i18n_controller, 
			$this->asset_controller,
			$this->settings_controller
		);
		
		$this->assertEquals( 10, \has_action( 'plugins_loaded', array( $this->i18n_controller, 'load_text_domain' ) ) );
		$this->assertEquals( 10, \has_action( 'admin_enqueue_scripts', array( $this->asset_controller, 'enqueue_admin' ) ) );
		$this->assertEquals( 10, \has_action( 'wp_enqueue_scripts', array( $this->asset_controller, 'enqueue_front' ) ) );
	}

	public function testActivate_notMeetPhpRequirements_deactivateAndDie() {

		// A very high PHP version requirement to force the failure.
		$this->plugin_data['RequiresPHP'] = '999';

		$this->plugin = new Plugin( 
			$this->plugin_data, 
			$this->base_name,
			$this->bootstrap,
			$this->i18n_controller, 
			$this->asset_controller,
			$this->settings_controller
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
			$this->bootstrap, 
			$this->i18n_controller, 
			$this->asset_controller,
			$this->settings_controller
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
			$this->bootstrap, 
			$this->i18n_controller, 
			$this->asset_controller,
			$this->settings_controller
		);

		$this->plugin->activate();

		$this->assertFalse( \is_plugin_active( $this->base_name ) );
		$this->expectOutputRegex( '/\\nwp_die\(\) called\nMessage: This plugin requires WooCommerce 999 or greater\./' );
	}
}
