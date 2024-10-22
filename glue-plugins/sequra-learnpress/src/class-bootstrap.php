<?php
/**
 * Implementation for the core bootstrap class.
 *
 * @package    SeQura/WC
 */

namespace SeQura\WC\LearnPress;

use SeQura\Core\Infrastructure\ServiceRegister;

/**
 * Implementation for the core bootstrap class.
 */
class Bootstrap {

	/**
	 * Cache for Service instances.
	 *
	 * @var array<string, mixed>
	 */
	private static $cache = array();

	/**
	 * Initialize the bootstrap.
	 */
	public static function init(): void {
		self::init_constants();
		self::init_services();
		self::init_controllers();
	}

	/**
	 * Initializes constants.
	 */
	private static function init_constants(): void {
		ServiceRegister::registerService(
			'lp_addon.dir_path',
			static function () {
				if ( ! isset( self::$cache['lp_addon.dir_path'] ) ) {
					self::$cache['lp_addon.dir_path'] = trailingslashit( dirname( __DIR__, 1 ) );
				}
				return self::$cache['lp_addon.dir_path'];
			}
		);
		ServiceRegister::registerService(
			'lp_addon.file_path',
			static function () {
				if ( ! isset( self::$cache['lp_addon.file_path'] ) ) {
					self::$cache['lp_addon.file_path'] = ServiceRegister::getService( 'lp_addon.dir_path' ) . 'sequra-learnpress.php';
				}
				return self::$cache['lp_addon.file_path'];
			}
		);
		ServiceRegister::registerService(
			'lp_addon.basename',
			static function () {
				if ( ! isset( self::$cache['lp_addon.basename'] ) ) {
					self::$cache['lp_addon.basename'] = plugin_basename( ServiceRegister::getService( 'lp_addon.dir_path' ) . 'sequra-learnpress.php' );
				}
				return self::$cache['lp_addon.basename'];
			}
		);
		ServiceRegister::registerService(
			'lp_addon.data',
			static function () {
				if ( ! isset( self::$cache['lp_addon.data'] ) ) {
					if ( ! function_exists( 'get_plugin_data' ) ) {
						require_once ABSPATH . 'wp-admin/includes/plugin.php';
					}
					$add_wc_headers = function ( $headers ) {
						$headers['seQura requires at least']     = 'seQura requires at least';
						$headers['LearnPress requires at least'] = 'LearnPress requires at least';
						return $headers;
					};
					add_filter( 'extra_plugin_headers', $add_wc_headers );
					$data = get_plugin_data( ServiceRegister::getService( 'lp_addon.file_path' ) );
					remove_filter( 'extra_plugin_headers', $add_wc_headers );
					
					$data['RequiresSQ'] = '';
					if ( isset( $data['seQura requires at least'] ) ) {
						$data['RequiresSQ'] = $data['seQura requires at least'];
						unset( $data['seQura requires at least'] );
					}

					$data['RequiresLP'] = '';
					if ( isset( $data['LearnPress requires at least'] ) ) {
						$data['RequiresLP'] = $data['LearnPress requires at least'];
						unset( $data['LearnPress requires at least'] );
					}

					self::$cache['lp_addon.data'] = $data;
				}
				return self::$cache['lp_addon.data'];
			}
		);
	}

	/**
	 * Initializes services and utilities.
	 */
	private static function init_services(): void {
		// TODO: Implement init_services() method.
	}

	
	/**
	 * Initializes controllers.
	 */
	private static function init_controllers(): void {
		// TODO: Implement init_controllers() method.
	}
}
