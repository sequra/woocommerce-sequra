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
use SeQura\WC\NoAddress\Services\Constants;
use SeQura\WC\NoAddress\Services\Interface_Constants;
use SeQura\WC\Services\Interface_Constants as Interface_Base_Constants;
use SeQura\WC\Services\Log\Interface_Logger_Service;

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
		self::init_controllers();
	}

	/**
	 * Initializes constants.
	 */
	private static function init_constants(): void {
		ServiceRegister::registerService(
			Interface_Constants::class,
			static function () {
				if ( ! isset( self::$cache[ Interface_Constants::class ] ) ) {
					$dir_path         = \trailingslashit( dirname( __DIR__, 1 ) );
					$plugin_file_path = $dir_path . 'sequra-no-address.php';

					if ( ! function_exists( 'get_plugin_data' ) ) {
						require_once ABSPATH . 'wp-admin/includes/plugin.php';
					}
					$add_sq_headers = function ( $headers ) {
						$headers['seQura requires at least'] = 'seQura requires at least';
						return $headers;
					};
					\add_filter( 'extra_plugin_headers', $add_sq_headers );
					$data = \get_plugin_data( $plugin_file_path, true, false );
					\remove_filter( 'extra_plugin_headers', $add_sq_headers );
					$data['RequiresSQ'] = '';
					if ( isset( $data['seQura requires at least'] ) ) {
						$data['RequiresSQ'] = $data['seQura requires at least'];
						unset( $data['seQura requires at least'] );
					}

					self::$cache[ Interface_Constants::class ] = new Constants(
						$dir_path,
						$plugin_file_path,
						\plugin_basename( $plugin_file_path ),
						$data
					);
				}
				return self::$cache[ Interface_Constants::class ];
			}
		);
	}

	/**
	 * Initializes controllers.
	 */
	private static function init_controllers(): void {
		ServiceRegister::registerService(
			Interface_Order_Controller::class,
			static function () {
				if ( ! isset( self::$cache[ Interface_Order_Controller::class ] ) ) {
					self::$cache[ Interface_Order_Controller::class ] = new Order_Controller(
						ServiceRegister::getService( Interface_Logger_Service::class ),
						ServiceRegister::getService( Interface_Base_Constants::class )->get_plugin_templates_path()
					);
				}
				return self::$cache[ Interface_Order_Controller::class ];
			}
		);
	}
}
