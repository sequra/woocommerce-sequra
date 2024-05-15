<?php
/**
 * Dependency Injection configuration. 
 * The order of the definitions is important.
 *
 * @package SeQura/WC
 */

defined( 'WPINC' ) || die;

use DI\Container;
use SeQura\WC\Bootstrap;
use SeQura\WC\Controllers\Assets_Controller;
use SeQura\WC\Controllers\I18n_Controller;
use SeQura\WC\Controllers\Interface_Assets_Controller;
use SeQura\WC\Controllers\Interface_I18n_Controller;
use SeQura\WC\Controllers\Interface_Settings_Controller;
use SeQura\WC\Controllers\Settings_Controller;
use SeQura\WC\Interface_Bootstrap;
use SeQura\WC\Plugin;
use SeQura\WC\Services\Interface_Logger;
use SeQura\WC\Services\Interface_Settings;
use SeQura\WC\Services\Logger;
use SeQura\WC\Services\Settings;

return array(
	// Global constants definitions.
	'plugin.basename'                    => plugin_basename( plugin_dir_path( __FILE__ ) . 'sequra.php' ),
	'plugin.dir_path'                    => plugin_dir_path( __FILE__ ),
	'plugin.dir_url'                     => plugin_dir_url( __FILE__ ),
	'plugin.data'                        => function ( Container $c ) {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		add_filter(
			'extra_plugin_headers',
			function ( $headers ) {
				$headers['WC requires at least'] = 'WC requires at least';
				return $headers;
			} 
		);

		// $plugin_data = get_file_data( $c->get( 'plugin.dir_path' ) . 'sequra.php', array( 'WC requires at least' => '' ), 'plugin' );

		$data = get_plugin_data( $c->get( 'plugin.dir_path' ) . 'sequra.php' );
		$data['RequiresWC'] = $data['WC requires at least'];
		unset( $data['WC requires at least'] );
		return $data;
	},
	'plugin.templates_path'              => function ( Container $c ) {
		return trailingslashit( $c->get( 'plugin.dir_path' ) ) . 'templates/';
	},
	// Third party.
	// Data Mappers.
	// Repositories.
	// Services.
	Interface_Settings::class            => DI\autowire( Settings::class ),
	Interface_Logger::class              => function ( Container $c ) {
		return new Logger( $c->get( 'plugin.dir_path' ), $c->get( Interface_Settings::class ) );
	},
	// UI Adapters.
	// Controllers.
	Interface_I18n_Controller::class     => function ( Container $c ) {
		$data   = $c->get( 'plugin.data' );
		$domain = $data['TextDomain'];
		return new I18n_Controller( $domain . $data['DomainPath'], $domain );
	},
	Interface_Assets_Controller::class   => function ( Container $c ) {
		return new Assets_Controller( $c->get( 'plugin.dir_url' ) . 'assets', $c->get( 'plugin.data' )['Version'] );
	},
	Interface_Settings_Controller::class => function ( Container $c ) {
		return new Settings_Controller( $c->get( 'plugin.templates_path' ) );
	},
	// Bootstrap.
	Interface_Bootstrap::class           => function ( Container $c ) {
		// TODO: Add the required dependencies.
		return new Bootstrap( 
		);
	},
	// Plugin main class.
	Plugin::class                        => function ( Container $c ) {
		return new Plugin( 
			$c->get( 'plugin.data' ),
			$c->get( 'plugin.basename' ),
			$c->get( Interface_Bootstrap::class ), 
			$c->get( Interface_I18n_Controller::class ), 
			$c->get( Interface_Assets_Controller::class ),
			$c->get( Interface_Settings_Controller::class )
		);
	},
);
