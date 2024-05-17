<?php
/**
 * Implementation for the core bootstrap class.
 *
 * @package    SeQura/WC
 */

namespace SeQura\WC;

use SeQura\Core\BusinessLogic\BootstrapComponent;
use SeQura\Core\Infrastructure\Configuration\Configuration;
use SeQura\Core\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use SeQura\Core\Infrastructure\ServiceRegister;
use SeQura\WC\Services\Core\Config_Service;
use SeQura\WC\Services\Core\Logger_Service;

/**
 * Implementation for the core bootstrap class.
 */
class Bootstrap extends BootstrapComponent {

	/**
	 * Initialize the bootstrap.
	 */
	public static function init(): void {
		self::initConstants();
		parent::init();

		ServiceRegister::registerService(
			Plugin::class,
			static function () {
				return new Plugin(
					ServiceRegister::getService( 'plugin.data' ),
					ServiceRegister::getService( 'plugin.basename' ),
					ServiceRegister::getService( Controllers\Interface_I18n_Controller::class ),
					ServiceRegister::getService( Controllers\Interface_Assets_Controller::class ),
					ServiceRegister::getService( Controllers\Interface_Settings_Controller::class ),
					ServiceRegister::getService( Controllers\Rest\Settings_REST_Controller::class ),
					ServiceRegister::getService( Controllers\Rest\Onboarding_REST_Controller::class ),
					ServiceRegister::getService( Controllers\Rest\Payment_REST_Controller::class )
				);
			}
		);
	}

	/**
	 * Initializes constants.
	 */
	public static function initConstants(): void {
		$plugin_dir_path  = trailingslashit( dirname( __DIR__, 1 ) );
		$plugin_file_path = $plugin_dir_path . 'sequra.php';

		ServiceRegister::registerService(
			'plugin.basename',
			static function () use ( $plugin_file_path ) {
				return plugin_basename( $plugin_file_path );
			}
		);

		ServiceRegister::registerService(
			'plugin.dir_path',
			static function () use ( $plugin_dir_path ) {
				return $plugin_dir_path;
			}
		);

		ServiceRegister::registerService(
			'plugin.dir_url',
			static function () use ( $plugin_file_path ) {
				return plugin_dir_url( $plugin_file_path );
			}
		);

		ServiceRegister::registerService(
			'plugin.rest_namespace',
			static function () {
				return 'sequra/v1';
			}
		);

		ServiceRegister::registerService(
			'plugin.data',
			static function () use ( $plugin_file_path ) {
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
				$data               = get_plugin_data( $plugin_file_path );
				$data['RequiresWC'] = $data['WC requires at least'];
				unset( $data['WC requires at least'] );
				return $data;
			}
		);

		ServiceRegister::registerService(
			'plugin.templates_path',
			static function () use ( $plugin_dir_path ) {
				return $plugin_dir_path . 'templates/';
			}
		);
	}

	/**
	 * Initializes services and utilities.
	 */
	protected static function initServices(): void {
		parent::initServices();

		// TODO: add sequra-core services implementations here...
		ServiceRegister::registerService(
			ShopLoggerAdapter::CLASS_NAME,
			static function () {
				return new Logger_Service();
			}
		);

		ServiceRegister::registerService(
			Configuration::CLASS_NAME,
			static function () {
				return Config_Service::getInstance();
			}
		);

		// Plugin services.
		ServiceRegister::registerService(
			Services\Interface_Settings::class,
			static function () {
				return new Services\Settings(
					ServiceRegister::getService( Repositories\Interface_Settings_Repo::class )
				);
			}
		);
		ServiceRegister::registerService(
			Services\Interface_I18n::class,
			static function () {
				return new Services\I18n();
			}
		);
		ServiceRegister::registerService(
			Services\Interface_Logger::class,
			static function () {
				return new Services\Logger(
					ServiceRegister::getService( 'plugin.dir_path' ),
					ServiceRegister::getService( Services\Interface_Settings::class )
				);
			}
		);
	}

	/**
	 * Initializes repositories.
	 */
	protected static function initRepositories(): void {
		parent::initRepositories();

		// TODO: add sequra-core repositories implementations here...

		// Plugin repositories.
		ServiceRegister::registerService(
			Repositories\Interface_Settings_Repo::class,
			static function () {
				return new Repositories\Settings_Repo();
			}
		);
	}

	/**
	 * Initializes controllers.
	 */
	protected static function initControllers(): void {
		parent::initControllers();

		// TODO: add sequra-core repositories implementations here...

		// Plugin controllers.
		ServiceRegister::registerService(
			Controllers\Interface_I18n_Controller::class,
			static function () {
				$data   = ServiceRegister::getService( 'plugin.data' );
				$domain = $data['TextDomain'];

				return new Controllers\I18n_Controller( $domain . $data['DomainPath'], $domain );
			}
		);
		ServiceRegister::registerService(
			Controllers\Interface_Assets_Controller::class,
			static function () {
				return new Controllers\Assets_Controller(
					ServiceRegister::getService( 'plugin.dir_url' ) . '/assets', 
					ServiceRegister::getService( 'plugin.dir_path' ) . 'assets', 
					ServiceRegister::getService( 'plugin.data' )['Version'],
					ServiceRegister::getService( Services\Interface_I18n::class ),
					ServiceRegister::getService( Services\Interface_Settings::class )
				);
			}
		);
		ServiceRegister::registerService(
			Controllers\Interface_Settings_Controller::class,
			static function () {
				return new Controllers\Settings_Controller(
					ServiceRegister::getService( 'plugin.templates_path' ),
					ServiceRegister::getService( Services\Interface_Settings::class )
				);
			}
		);
		ServiceRegister::registerService(
			Controllers\Rest\Onboarding_REST_Controller::class,
			static function () {
				return new Controllers\Rest\Onboarding_REST_Controller(
					ServiceRegister::getService( 'plugin.rest_namespace' )
				);
			}
		);
		ServiceRegister::registerService(
			Controllers\Rest\Payment_REST_Controller::class,
			static function () {
				return new Controllers\Rest\Payment_REST_Controller(
					ServiceRegister::getService( 'plugin.rest_namespace' )
				);
			}
		);
		ServiceRegister::registerService(
			Controllers\Rest\Settings_REST_Controller::class,
			static function () {
				return new Controllers\Rest\Settings_REST_Controller(
					ServiceRegister::getService( 'plugin.rest_namespace' )
				);
			}
		);
	}
}
