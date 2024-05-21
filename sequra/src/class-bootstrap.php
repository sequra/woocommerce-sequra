<?php
/**
 * Implementation for the core bootstrap class.
 *
 * @package    SeQura/WC
 */

namespace SeQura\WC;

use SeQura\Core\BusinessLogic\BootstrapComponent;
use SeQura\Core\BusinessLogic\DataAccess\ConnectionData\Entities\ConnectionData;
use SeQura\Core\BusinessLogic\DataAccess\OrderSettings\Entities\OrderStatusSettings;
use SeQura\Core\BusinessLogic\DataAccess\PromotionalWidgets\Entities\WidgetSettings;
use SeQura\Core\BusinessLogic\DataAccess\SendReport\Entities\SendReport;
use SeQura\Core\BusinessLogic\DataAccess\StatisticalData\Entities\StatisticalData;
use SeQura\Core\BusinessLogic\DataAccess\TransactionLog\Entities\TransactionLog;
use SeQura\Core\BusinessLogic\DataAccess\CountryConfiguration\Entities\CountryConfiguration;
use SeQura\Core\BusinessLogic\DataAccess\GeneralSettings\Entities\GeneralSettings;
use SeQura\Core\BusinessLogic\Domain\Order\Models\SeQuraOrder;
use SeQura\Core\Infrastructure\Configuration\ConfigEntity;
use SeQura\Core\Infrastructure\Configuration\Configuration;
use SeQura\Core\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use SeQura\Core\Infrastructure\ORM\RepositoryRegistry;
use SeQura\Core\Infrastructure\ServiceRegister;
use SeQura\Core\Infrastructure\TaskExecution\Process;
use SeQura\Core\Infrastructure\TaskExecution\QueueItem;
use SeQura\WC\Repositories\Base_Repository;
use SeQura\WC\Repositories\Queue_Item_Repository;
use SeQura\WC\Repositories\SeQura_Order_Repository;
use SeQura\WC\Services\Core\Config_Service;
use SeQura\WC\Services\Core\Logger_Service;

/**
 * Implementation for the core bootstrap class.
 */
class Bootstrap extends BootstrapComponent {

	/**
	 * Cache for Service instances.
	 */
	private static $cache = array();

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
		ServiceRegister::registerService(
			'plugin.dir_path',
			static function () {
				if ( ! isset( self::$cache['plugin.dir_path'] ) ) {
					self::$cache['plugin.dir_path'] = trailingslashit( dirname( __DIR__, 1 ) );
				}
				return self::$cache['plugin.dir_path'];
			}
		);
		ServiceRegister::registerService(
			'plugin.file_path',
			static function () {
				if ( ! isset( self::$cache['plugin.file_path'] ) ) {
					self::$cache['plugin.file_path'] = ServiceRegister::getService( 'plugin.dir_path' ) . 'sequra.php';
				}
				return self::$cache['plugin.file_path'];
			}
		);

		ServiceRegister::registerService(
			'plugin.basename',
			static function () {
				if ( ! isset( self::$cache['plugin.basename'] ) ) {
					self::$cache['plugin.basename'] = plugin_basename( ServiceRegister::getService( 'plugin.dir_path' ) . 'sequra.php' );
				}
				return self::$cache['plugin.basename'];
			}
		);

		ServiceRegister::registerService(
			'plugin.dir_url',
			static function () {
				if ( ! isset( self::$cache['plugin.dir_url'] ) ) {
					self::$cache['plugin.dir_url'] = plugin_dir_url( ServiceRegister::getService( 'plugin.file_path' ) );
				}
				return self::$cache['plugin.dir_url'];
			}
		);

		ServiceRegister::registerService(
			'plugin.rest_namespace',
			static function () {
				if ( ! isset( self::$cache['plugin.rest_namespace'] ) ) {
					self::$cache['plugin.rest_namespace'] = 'sequra/v1';
				}
				return self::$cache['plugin.rest_namespace'];
			}
		);

		ServiceRegister::registerService(
			'plugin.data',
			static function () {
				if ( ! isset( self::$cache['plugin.data'] ) ) {
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
					$data               = get_plugin_data( ServiceRegister::getService( 'plugin.file_path' ) );
					$data['RequiresWC'] = $data['WC requires at least'];
					unset( $data['WC requires at least'] );

					self::$cache['plugin.data'] = $data;
				}
				return self::$cache['plugin.data'];
			}
		);

		ServiceRegister::registerService(
			'plugin.templates_path',
			static function () {
				if ( ! isset( self::$cache['plugin.templates_path'] ) ) {
					self::$cache['plugin.templates_path'] = ServiceRegister::getService( 'plugin.dir_path' ) . 'templates/';
				}
				return self::$cache['plugin.templates_path'];
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
				if ( ! isset( self::$cache[ ShopLoggerAdapter::CLASS_NAME ] ) ) {
					self::$cache[ ShopLoggerAdapter::CLASS_NAME ] = new Logger_Service();
				}
				return self::$cache[ ShopLoggerAdapter::CLASS_NAME ];
			}
		);

		ServiceRegister::registerService(
			Configuration::CLASS_NAME,
			static function () {
				if ( ! isset( self::$cache[ Configuration::CLASS_NAME ] ) ) {
					self::$cache[ Configuration::CLASS_NAME ] = Config_Service::getInstance();
				}
				return self::$cache[ Configuration::CLASS_NAME ];
			}
		);

		// Plugin services.
		ServiceRegister::registerService(
			Services\Interface_Settings::class,
			static function () {
				if ( ! isset( self::$cache[ Services\Interface_Settings::class ] ) ) {
					self::$cache[ Services\Interface_Settings::class ] = new Services\Settings(
						ServiceRegister::getService( Repositories\Interface_Settings_Repo::class )
					);
				}
				return self::$cache[ Services\Interface_Settings::class ];
			}
		);
		ServiceRegister::registerService(
			Services\Interface_I18n::class,
			static function () {
				if ( ! isset( self::$cache[ Services\Interface_I18n::class ] ) ) {
					self::$cache[ Services\Interface_I18n::class ] = new Services\I18n();
				}
				return self::$cache[ Services\Interface_I18n::class ];
			}
		);
		ServiceRegister::registerService(
			Services\Interface_Logger::class,
			static function () {
				if ( ! isset( self::$cache[ Services\Interface_Logger::class ] ) ) {
					self::$cache[ Services\Interface_Logger::class ] = new Services\Logger(
						ServiceRegister::getService( 'plugin.dir_path' ),
						ServiceRegister::getService( Services\Interface_Settings::class )
					);
				}
				return self::$cache[ Services\Interface_Logger::class ];
			}
		);
	}

	/**
	 * Initializes repositories.
	 */
	protected static function initRepositories(): void {

		ServiceRegister::registerService(
			\wpdb::class,
			static function () {
				if ( ! isset( self::$cache[ \wpdb::class ] ) ) {
					global $wpdb;
					self::$cache[ \wpdb::class ] = $wpdb;
				}
				return self::$cache[ \wpdb::class ];
			}
		);

		parent::initRepositories();

		// TODO: add sequra-core repositories implementations here...

		RepositoryRegistry::registerRepository( ConfigEntity::class, Base_Repository::class );
		RepositoryRegistry::registerRepository( QueueItem::class, Queue_Item_Repository::class );
		RepositoryRegistry::registerRepository( Process::class, Base_Repository::class );
		RepositoryRegistry::registerRepository( ConnectionData::class, Base_Repository::class );
		RepositoryRegistry::registerRepository( OrderStatusSettings::class, Base_Repository::class );
		RepositoryRegistry::registerRepository( StatisticalData::class, Base_Repository::class );
		RepositoryRegistry::registerRepository( CountryConfiguration::class, Base_Repository::class );
		RepositoryRegistry::registerRepository( GeneralSettings::class, Base_Repository::class );
		RepositoryRegistry::registerRepository( SeQuraOrder::class, SeQura_Order_Repository::class );
		RepositoryRegistry::registerRepository( WidgetSettings::class, Base_Repository::class );
		RepositoryRegistry::registerRepository( SendReport::class, Base_Repository::class );
		RepositoryRegistry::registerRepository( StatisticalData::class, Base_Repository::class );
		RepositoryRegistry::registerRepository( TransactionLog::class, Base_Repository::class );

		// ServiceRegister::registerService(
		// OrderStatusSettingsRepositoryInterface::class,
		// static function () {
		// return new OrderStatusMappingRepositoryOverride();
		// }
		// );

		// Plugin repositories.
		ServiceRegister::registerService(
			Repositories\Interface_Settings_Repo::class,
			static function () {
				if ( ! isset( self::$cache[ Repositories\Interface_Settings_Repo::class ] ) ) {
					self::$cache[ Repositories\Interface_Settings_Repo::class ] = new Repositories\Settings_Repo();
				}
				return self::$cache[ Repositories\Interface_Settings_Repo::class ];
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
				if ( ! isset( self::$cache[ Controllers\Interface_I18n_Controller::class ] ) ) {
					$data   = ServiceRegister::getService( 'plugin.data' );
					$domain = $data['TextDomain'];
					self::$cache[ Controllers\Interface_I18n_Controller::class ] = new Controllers\I18n_Controller( $domain . $data['DomainPath'], $domain );
				}
				return self::$cache[ Controllers\Interface_I18n_Controller::class ];
			}
		);
		ServiceRegister::registerService(
			Controllers\Interface_Assets_Controller::class,
			static function () {
				if ( ! isset( self::$cache[ Controllers\Interface_Assets_Controller::class ] ) ) {
					self::$cache[ Controllers\Interface_Assets_Controller::class ] = new Controllers\Assets_Controller(
						ServiceRegister::getService( 'plugin.dir_url' ) . '/assets', 
						ServiceRegister::getService( 'plugin.dir_path' ) . 'assets', 
						ServiceRegister::getService( 'plugin.data' )['Version'],
						ServiceRegister::getService( Services\Interface_I18n::class ),
						ServiceRegister::getService( Services\Interface_Settings::class )
					);
				}
				return self::$cache[ Controllers\Interface_Assets_Controller::class ];
			}
		);
		ServiceRegister::registerService(
			Controllers\Interface_Settings_Controller::class,
			static function () {
				if ( ! isset( self::$cache[ Controllers\Interface_Settings_Controller::class ] ) ) {
					self::$cache[ Controllers\Interface_Settings_Controller::class ] = new Controllers\Settings_Controller(
						ServiceRegister::getService( 'plugin.templates_path' ),
						ServiceRegister::getService( Services\Interface_Settings::class )
					);
				}
				return self::$cache[ Controllers\Interface_Settings_Controller::class ];
			}
		);
		ServiceRegister::registerService(
			Controllers\Rest\Onboarding_REST_Controller::class,
			static function () {
				if ( ! isset( self::$cache[ Controllers\Rest\Onboarding_REST_Controller::class ] ) ) {
					self::$cache[ Controllers\Rest\Onboarding_REST_Controller::class ] = new Controllers\Rest\Onboarding_REST_Controller(
						ServiceRegister::getService( 'plugin.rest_namespace' )
					);
				}
				return self::$cache[ Controllers\Rest\Onboarding_REST_Controller::class ];
			}
		);
		ServiceRegister::registerService(
			Controllers\Rest\Payment_REST_Controller::class,
			static function () {
				if ( ! isset( self::$cache[ Controllers\Rest\Payment_REST_Controller::class ] ) ) {
					self::$cache[ Controllers\Rest\Payment_REST_Controller::class ] = new Controllers\Rest\Payment_REST_Controller(
						ServiceRegister::getService( 'plugin.rest_namespace' )
					);
				}
				return self::$cache[ Controllers\Rest\Payment_REST_Controller::class ];
			}
		);
		ServiceRegister::registerService(
			Controllers\Rest\Settings_REST_Controller::class,
			static function () {
				if ( ! isset( self::$cache[ Controllers\Rest\Settings_REST_Controller::class ] ) ) {
					self::$cache[ Controllers\Rest\Settings_REST_Controller::class ] = new Controllers\Rest\Settings_REST_Controller(
						ServiceRegister::getService( 'plugin.rest_namespace' )
					);
				}
				return self::$cache[ Controllers\Rest\Settings_REST_Controller::class ];
			}
		);
	}
}
