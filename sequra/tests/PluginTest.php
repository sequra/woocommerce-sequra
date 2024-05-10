<?php
/**
 * Tests for the Plugin class.
 *
 * @package Sequra/WC
 * @subpackage Sequra/WC/Tests
 */

namespace Sequra\WC\Tests;

use Sequra\WC\Controllers\Interface_Assets_Controller;
use Sequra\WC\Controllers\Interface_I18n_Controller;
use Sequra\WC\Plugin;
use PHPUnit\Framework\MockObject\MockObject;
use WP_UnitTestCase;

class PluginTest extends WP_UnitTestCase {

	private $plugin;
	private $plugin_data;
	private $base_name;
	private $i18n_controller;
	private $asset_controller;

	public function set_up() {

		$this->plugin_data = array(
			'Name'        => 'seQura',
			'TextDomain'  => 'sequra',
			'DomainPath'  => '/languages',
			'Version'     => '3.0.0',
			'RequiresPHP' => '7.3',
			'RequiresWP'  => '5.9',
		);

		$this->base_name        = 'sequra/sequra.php';
		$this->i18n_controller  = $this->createMock( Interface_I18n_Controller::class );
		$this->asset_controller = $this->createMock( Interface_Assets_Controller::class );
	}

	public function testConstructor_happyPath_hooksAreRegistered() {
		$this->plugin = new Plugin( 
			$this->plugin_data, 
			$this->base_name, 
			$this->i18n_controller, 
			$this->asset_controller
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
			$this->i18n_controller, 
			$this->asset_controller
		);

		$this->plugin->activate();

		$this->assertFalse( \is_plugin_active( $this->base_name ) );
		$this->expectOutputString( "\nwp_die() called\nMessage: This plugin requires PHP " . $this->plugin_data['RequiresPHP'] . " or greater.\nTitle: WordPress &rsaquo; Error\nArgs:\n\tresponse: 500\n\tcode: wp_die\n\texit: 1\n\tback_link: \n\tlink_url: \n\tlink_text: \n\ttext_direction: ltr\n\tcharset: UTF-8\n\tadditional_errors: array (\n)\n" );
	}

	public function testActivate_notMeetWpRequirements_deactivateAndDie() {

		// A very high WP version requirement to force the failure.
		$this->plugin_data['RequiresWP'] = '999';

		$this->plugin = new Plugin( 
			$this->plugin_data, 
			$this->base_name, 
			$this->i18n_controller, 
			$this->asset_controller
		);

		$this->plugin->activate();

		$this->assertFalse( \is_plugin_active( $this->base_name ) );
		$this->expectOutputString( "\nwp_die() called\nMessage: This plugin requires WordPress " . $this->plugin_data['RequiresWP'] . " or greater.\nTitle: WordPress &rsaquo; Error\nArgs:\n\tresponse: 500\n\tcode: wp_die\n\texit: 1\n\tback_link: \n\tlink_url: \n\tlink_text: \n\ttext_direction: ltr\n\tcharset: UTF-8\n\tadditional_errors: array (\n)\n" );
	}
}
