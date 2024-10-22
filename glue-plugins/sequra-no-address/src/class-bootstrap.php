<?php
/**
 * Implementation for the core bootstrap class.
 *
 * @package    SeQura/WC/NoAddress
 */

namespace SeQura\WC\NoAddress;

use SeQura\Core\Infrastructure\ServiceRegister;
use SeQura\WC\NoAddress\Controller\Hooks\Order\Interface_Order_Controller;
use SeQura\WC\NoAddress\Controller\Hooks\Order\Order_Controller;
use SeQura\WC\Services\Interface_Logger_Service;

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
			'noaddress_addon.dir_path',
			static function () {
				if ( ! isset( self::$cache['noaddress_addon.dir_path'] ) ) {
					self::$cache['noaddress_addon.dir_path'] = trailingslashit( dirname( __DIR__, 1 ) );
				}
				return self::$cache['noaddress_addon.dir_path'];
			}
		);
		ServiceRegister::registerService(
			'noaddress_addon.file_path',
			static function () {
				if ( ! isset( self::$cache['noaddress_addon.file_path'] ) ) {
					self::$cache['noaddress_addon.file_path'] = ServiceRegister::getService( 'noaddress_addon.dir_path' ) . 'sequra-no-address.php';
				}
				return self::$cache['noaddress_addon.file_path'];
			}
		);
		ServiceRegister::registerService(
			'noaddress_addon.basename',
			static function () {
				if ( ! isset( self::$cache['noaddress_addon.basename'] ) ) {
					self::$cache['noaddress_addon.basename'] = plugin_basename( ServiceRegister::getService( 'noaddress_addon.dir_path' ) . 'sequra-no-address.php' );
				}
				return self::$cache['noaddress_addon.basename'];
			}
		);
		ServiceRegister::registerService(
			'noaddress_addon.data',
			static function () {
				if ( ! isset( self::$cache['noaddress_addon.data'] ) ) {
					if ( ! function_exists( 'get_plugin_data' ) ) {
						require_once ABSPATH . 'wp-admin/includes/plugin.php';
					}
					$add_wc_headers = function ( $headers ) {
						$headers['seQura requires at least'] = 'seQura requires at least';
						return $headers;
					};
					add_filter( 'extra_plugin_headers', $add_wc_headers );
					$data = get_plugin_data( ServiceRegister::getService( 'noaddress_addon.file_path' ) );
					remove_filter( 'extra_plugin_headers', $add_wc_headers );
					
					$data['RequiresSQ'] = '';
					if ( isset( $data['seQura requires at least'] ) ) {
						$data['RequiresSQ'] = $data['seQura requires at least'];
						unset( $data['seQura requires at least'] );
					}

					self::$cache['noaddress_addon.data'] = $data;
				}
				return self::$cache['noaddress_addon.data'];
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
		ServiceRegister::registerService(
			Interface_Order_Controller::class,
			static function () {
				if ( ! isset( self::$cache[Interface_Order_Controller::class] ) ) {
					self::$cache[Interface_Order_Controller::class] = new Order_Controller(
						ServiceRegister::getService( Interface_Logger_Service::class ),
						ServiceRegister::getService( 'plugin.templates_path' )
					);
				}
				return self::$cache[Interface_Order_Controller::class];
			}
		);
	}
}
