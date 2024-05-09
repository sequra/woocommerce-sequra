<?php
/**
 * Dependency Injection configuration. 
 * The order of the definitions is important.
 *
 * @package Sequra/WC
 */

defined( 'WPINC' ) || die;

use DI\Container;
use Sequra\WC\Controllers\Assets_Controller;
use Sequra\WC\Controllers\I18n_Controller;
use Sequra\WC\Controllers\Interface_Assets_Controller;
use Sequra\WC\Controllers\Interface_I18n_Controller;
use Sequra\WC\Plugin;
use Sequra\WC\Services\Interface_Logger;
use Sequra\WC\Services\Interface_Settings;
use Sequra\WC\Services\Logger;
use Sequra\WC\Services\Settings;

return array(
	// Global constants definitions.
	'plugin.basename'                  => plugin_basename( plugin_dir_path( __FILE__ ) . 'sequra.php' ),
	'plugin.dir_path'                  => plugin_dir_path( __FILE__ ),
	'plugin.dir_url'                   => plugin_dir_url( __FILE__ ),
	'plugin.data'                      => function ( Container $c ) {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return get_plugin_data( $c->get( 'plugin.dir_path' ) . 'sequra.php' );
	},
	// Third party.
	// Data Mappers.
	// Repositories.
	// Services.
	Interface_Settings::class          => DI\autowire( Settings::class ),
	Interface_Logger::class            => function ( Container $c ) {
		return new Logger( $c->get( 'plugin.dir_path' ), $c->get( Interface_Settings::class ) );
	},
	// UI Adapters.
	// Controllers.
	Interface_I18n_Controller::class   => function ( Container $c ) {
		$data   = $c->get( 'plugin.data' );
		$domain = $data['TextDomain'];
		return new I18n_Controller( $domain . $data['DomainPath'], $domain );
	},
	Interface_Assets_Controller::class => function ( Container $c ) {
		return new Assets_Controller( $c->get( 'plugin.dir_url' ), $c->get( 'plugin.data' )['Version'] );
	},
	// Plugin main class.
	Plugin::class                      => function ( Container $c ) {
		return new Plugin( 
			$c->get( 'plugin.data' ),
			$c->get( 'plugin.basename' ),
			$c->get( Interface_I18n_Controller::class ), 
			$c->get( Interface_Assets_Controller::class )
		);
	},
);
