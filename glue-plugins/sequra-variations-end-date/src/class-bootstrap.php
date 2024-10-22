<?php
/**
 * Implementation for the core bootstrap class.
 *
 * @package    SeQura/WC/VariationsEndDate
 */

namespace SeQura\WC\VariationsEndDate;

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
			'variations_end_date_addon.dir_path',
			static function () {
				if ( ! isset( self::$cache['variations_end_date_addon.dir_path'] ) ) {
					self::$cache['variations_end_date_addon.dir_path'] = trailingslashit( dirname( __DIR__, 1 ) );
				}
				return self::$cache['variations_end_date_addon.dir_path'];
			}
		);
		ServiceRegister::registerService(
			'variations_end_date_addon.file_path',
			static function () {
				if ( ! isset( self::$cache['variations_end_date_addon.file_path'] ) ) {
					self::$cache['variations_end_date_addon.file_path'] = ServiceRegister::getService( 'variations_end_date_addon.dir_path' ) . 'sequra-variations-end-date.php';
				}
				return self::$cache['variations_end_date_addon.file_path'];
			}
		);
		ServiceRegister::registerService(
			'variations_end_date_addon.basename',
			static function () {
				if ( ! isset( self::$cache['variations_end_date_addon.basename'] ) ) {
					self::$cache['variations_end_date_addon.basename'] = plugin_basename( ServiceRegister::getService( 'variations_end_date_addon.dir_path' ) . 'sequra-variations-end-date.php' );
				}
				return self::$cache['variations_end_date_addon.basename'];
			}
		);
		ServiceRegister::registerService(
			'variations_end_date_addon.data',
			static function () {
				if ( ! isset( self::$cache['variations_end_date_addon.data'] ) ) {
					if ( ! function_exists( 'get_plugin_data' ) ) {
						require_once ABSPATH . 'wp-admin/includes/plugin.php';
					}
					$add_wc_headers = function ( $headers ) {
						$headers['seQura requires at least']     = 'seQura requires at least';
						return $headers;
					};
					add_filter( 'extra_plugin_headers', $add_wc_headers );
					$data = get_plugin_data( ServiceRegister::getService( 'variations_end_date_addon.file_path' ) );
					remove_filter( 'extra_plugin_headers', $add_wc_headers );
					
					$data['RequiresSQ'] = '';
					if ( isset( $data['seQura requires at least'] ) ) {
						$data['RequiresSQ'] = $data['seQura requires at least'];
						unset( $data['seQura requires at least'] );
					}

					self::$cache['variations_end_date_addon.data'] = $data;
				}
				return self::$cache['variations_end_date_addon.data'];
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
