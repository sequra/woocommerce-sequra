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
use SeQura\Core\BusinessLogic\Domain\Integration\Disconnect\DisconnectServiceInterface;
use SeQura\Core\BusinessLogic\Domain\Order\Models\SeQuraOrder;
use SeQura\Core\BusinessLogic\Utility\EncryptorInterface;
use SeQura\Core\Infrastructure\Configuration\ConfigEntity;
use SeQura\Core\Infrastructure\Configuration\ConfigurationManager;
use SeQura\Core\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use SeQura\Core\Infrastructure\ORM\RepositoryRegistry;
use SeQura\Core\Infrastructure\ServiceRegister as Reg;
use SeQura\Core\Infrastructure\TaskExecution\Process;
use SeQura\Core\Infrastructure\TaskExecution\QueueItem;
use SeQura\WC\Repositories\Entity_Repository;
use SeQura\WC\Repositories\Queue_Item_Repository;
use SeQura\WC\Repositories\SeQura_Order_Repository;
use SeQura\WC\Services\Core\Configuration;
use SeQura\WC\Services\Core\Configuration_Service;
use SeQura\WC\Services\Core\Disconnect_Service;
use SeQura\WC\Services\Core\Encryptor;
use SeQura\WC\Services\Core\Logger;

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

		Reg::registerService(
			Plugin::class,
			static function () {
				return new Plugin(
					Reg::getService( 'plugin.data' ),
					Reg::getService( 'plugin.basename' ),
					Reg::getService( Services\Interface_Migration_Manager::class ),
					Reg::getService( Controllers\Interface_I18n_Controller::class ),
					Reg::getService( Controllers\Interface_Assets_Controller::class ),
					Reg::getService( Controllers\Interface_Settings_Controller::class ),
					Reg::getService( Controllers\Rest\Settings_REST_Controller::class ),
					Reg::getService( Controllers\Rest\Onboarding_REST_Controller::class ),
					Reg::getService( Controllers\Rest\Payment_REST_Controller::class )
				);
			}
		);
	}

	/**
	 * Initializes constants.
	 */
	public static function initConstants(): void {
		Reg::registerService(
			'plugin.dir_path',
			static function () {
				if ( ! isset( self::$cache['plugin.dir_path'] ) ) {
					self::$cache['plugin.dir_path'] = trailingslashit( dirname( __DIR__, 1 ) );
				}
				return self::$cache['plugin.dir_path'];
			}
		);
		Reg::registerService(
			'plugin.file_path',
			static function () {
				if ( ! isset( self::$cache['plugin.file_path'] ) ) {
					self::$cache['plugin.file_path'] = Reg::getService( 'plugin.dir_path' ) . 'sequra.php';
				}
				return self::$cache['plugin.file_path'];
			}
		);

		Reg::registerService(
			'plugin.basename',
			static function () {
				if ( ! isset( self::$cache['plugin.basename'] ) ) {
					self::$cache['plugin.basename'] = plugin_basename( Reg::getService( 'plugin.dir_path' ) . 'sequra.php' );
				}
				return self::$cache['plugin.basename'];
			}
		);

		Reg::registerService(
			'plugin.dir_url',
			static function () {
				if ( ! isset( self::$cache['plugin.dir_url'] ) ) {
					self::$cache['plugin.dir_url'] = plugin_dir_url( Reg::getService( 'plugin.file_path' ) );
				}
				return self::$cache['plugin.dir_url'];
			}
		);

		Reg::registerService(
			'plugin.rest_namespace',
			static function () {
				if ( ! isset( self::$cache['plugin.rest_namespace'] ) ) {
					self::$cache['plugin.rest_namespace'] = 'sequra/v1';
				}
				return self::$cache['plugin.rest_namespace'];
			}
		);

		Reg::registerService(
			'woocommerce.data',
			static function () {
				if ( ! isset( self::$cache['woocommerce.data'] ) ) {
					if ( ! function_exists( 'get_plugin_data' ) ) {
						require_once ABSPATH . 'wp-admin/includes/plugin.php';
					}
					self::$cache['woocommerce.data'] = get_plugin_data( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php' );
				}
				return self::$cache['woocommerce.data'];
			}
		);

		Reg::registerService(
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
					$data               = get_plugin_data( Reg::getService( 'plugin.file_path' ) );
					$data['RequiresWC'] = $data['WC requires at least'];
					unset( $data['WC requires at least'] );

					self::$cache['plugin.data'] = $data;
				}
				return self::$cache['plugin.data'];
			}
		);

		Reg::registerService(
			'plugin.templates_path',
			static function () {
				if ( ! isset( self::$cache['plugin.templates_path'] ) ) {
					self::$cache['plugin.templates_path'] = Reg::getService( 'plugin.dir_path' ) . 'templates/';
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
		Reg::registerService(
			EncryptorInterface::class,
			static function () {
				if ( ! isset( self::$cache[ EncryptorInterface::class ] ) ) {
					self::$cache[ EncryptorInterface::class ] = new Encryptor();
				}
				return self::$cache[ EncryptorInterface::class ];
			}
		);

		Reg::registerService(
			DisconnectServiceInterface::class,
			static function () {
				if ( ! isset( self::$cache[ DisconnectServiceInterface::class ] ) ) {
					self::$cache[ DisconnectServiceInterface::class ] = new Disconnect_Service(
						array(
							RepositoryRegistry::getRepository( ConnectionData::class ),
							RepositoryRegistry::getRepository( StatisticalData::class ),
							RepositoryRegistry::getRepository( SendReport::class ),
							RepositoryRegistry::getRepository( QueueItem::class ),
							RepositoryRegistry::getRepository( SeQuraOrder::class ), // TODO: why is this here?
						)
					);
				}
				return self::$cache[ DisconnectServiceInterface::class ];
			}
		);

		Reg::registerService(
			ShopLoggerAdapter::CLASS_NAME,
			static function () {
				if ( ! isset( self::$cache[ ShopLoggerAdapter::CLASS_NAME ] ) ) {
					self::$cache[ ShopLoggerAdapter::CLASS_NAME ] = new Logger();
				}
				return self::$cache[ ShopLoggerAdapter::CLASS_NAME ];
			}
		);

		Reg::registerService(
			ConfigurationManager::CLASS_NAME,
			static function () {
				if ( ! isset( self::$cache[ ConfigurationManager::CLASS_NAME ] ) ) {
					self::$cache[ ConfigurationManager::CLASS_NAME ] = Services\Core\Configuration_Manager::getInstance();
				}
				return self::$cache[ ConfigurationManager::CLASS_NAME ];
			}
		);

		Reg::registerService(
			Configuration::class,
			static function () {
				if ( ! isset( self::$cache[ Configuration::class ] ) ) {
					self::$cache[ Configuration::class ] = Configuration_Service::getInstance();
				}
				return self::$cache[ Configuration::class ];
			}
		);
		Reg::registerService(
			\SeQura\Core\Infrastructure\Configuration\Configuration::CLASS_NAME,
			static function () {
				return Reg::getService( Configuration::class );
			}
		);

		// Plugin services.
		Reg::registerService(
			Services\Interface_Migration_Manager::class,
			static function () {
				if ( ! isset( self::$cache[ Services\Interface_Migration_Manager::class ] ) ) {
					self::$cache[ Services\Interface_Migration_Manager::class ] = new Services\Migration_Manager(
						Reg::getService( Configuration::CLASS_NAME ),
						Reg::getService( 'plugin.data' )['Version'],
						array(
							new Repositories\Migrations\Migration_Install_300( Reg::getService( \wpdb::class ) ),
						)
					);
				}
				return self::$cache[ Services\Interface_Migration_Manager::class ];
			}
		);

		Reg::registerService(
			Services\Interface_I18n::class,
			static function () {
				if ( ! isset( self::$cache[ Services\Interface_I18n::class ] ) ) {
					self::$cache[ Services\Interface_I18n::class ] = new Services\I18n();
				}
				return self::$cache[ Services\Interface_I18n::class ];
			}
		);
	}

	/**
	 * Initializes repositories.
	 */
	protected static function initRepositories(): void {

		Reg::registerService(
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

		RepositoryRegistry::registerRepository( ConfigEntity::class, Entity_Repository::class );
		RepositoryRegistry::registerRepository( QueueItem::class, Queue_Item_Repository::class );
		RepositoryRegistry::registerRepository( Process::class, Entity_Repository::class );
		RepositoryRegistry::registerRepository( ConnectionData::class, Entity_Repository::class );
		RepositoryRegistry::registerRepository( OrderStatusSettings::class, Entity_Repository::class );
		RepositoryRegistry::registerRepository( StatisticalData::class, Entity_Repository::class );
		RepositoryRegistry::registerRepository( CountryConfiguration::class, Entity_Repository::class );
		RepositoryRegistry::registerRepository( GeneralSettings::class, Entity_Repository::class );
		RepositoryRegistry::registerRepository( SeQuraOrder::class, SeQura_Order_Repository::class );
		RepositoryRegistry::registerRepository( WidgetSettings::class, Entity_Repository::class );
		RepositoryRegistry::registerRepository( SendReport::class, Entity_Repository::class );
		RepositoryRegistry::registerRepository( StatisticalData::class, Entity_Repository::class );
		RepositoryRegistry::registerRepository( TransactionLog::class, Entity_Repository::class );
	}

	/**
	 * Initializes controllers.
	 */
	protected static function initControllers(): void {
		parent::initControllers();

		// TODO: add sequra-core repositories implementations here...

		// Plugin controllers.
		Reg::registerService(
			Controllers\Interface_I18n_Controller::class,
			static function () {
				if ( ! isset( self::$cache[ Controllers\Interface_I18n_Controller::class ] ) ) {
					$data   = Reg::getService( 'plugin.data' );
					$domain = $data['TextDomain'];
					self::$cache[ Controllers\Interface_I18n_Controller::class ] = new Controllers\I18n_Controller( $domain . $data['DomainPath'], $domain );
				}
				return self::$cache[ Controllers\Interface_I18n_Controller::class ];
			}
		);
		Reg::registerService(
			Controllers\Interface_Assets_Controller::class,
			static function () {
				if ( ! isset( self::$cache[ Controllers\Interface_Assets_Controller::class ] ) ) {
					self::$cache[ Controllers\Interface_Assets_Controller::class ] = new Controllers\Assets_Controller(
						Reg::getService( 'plugin.dir_url' ) . '/assets', 
						Reg::getService( 'plugin.dir_path' ) . 'assets', 
						Reg::getService( 'plugin.data' )['Version'],
						Reg::getService( Services\Interface_I18n::class ),
						Reg::getService( Configuration::class )
					);
				}
				return self::$cache[ Controllers\Interface_Assets_Controller::class ];
			}
		);
		Reg::registerService(
			Controllers\Interface_Settings_Controller::class,
			static function () {
				if ( ! isset( self::$cache[ Controllers\Interface_Settings_Controller::class ] ) ) {
					self::$cache[ Controllers\Interface_Settings_Controller::class ] = new Controllers\Settings_Controller(
						Reg::getService( 'plugin.templates_path' ),
						Reg::getService( Configuration::class )
					);
				}
				return self::$cache[ Controllers\Interface_Settings_Controller::class ];
			}
		);
		Reg::registerService(
			Controllers\Rest\Onboarding_REST_Controller::class,
			static function () {
				if ( ! isset( self::$cache[ Controllers\Rest\Onboarding_REST_Controller::class ] ) ) {
					self::$cache[ Controllers\Rest\Onboarding_REST_Controller::class ] = new Controllers\Rest\Onboarding_REST_Controller(
						Reg::getService( 'plugin.rest_namespace' ),
						Reg::getService( Configuration::class )
					);
				}
				return self::$cache[ Controllers\Rest\Onboarding_REST_Controller::class ];
			}
		);
		Reg::registerService(
			Controllers\Rest\Payment_REST_Controller::class,
			static function () {
				if ( ! isset( self::$cache[ Controllers\Rest\Payment_REST_Controller::class ] ) ) {
					self::$cache[ Controllers\Rest\Payment_REST_Controller::class ] = new Controllers\Rest\Payment_REST_Controller(
						Reg::getService( 'plugin.rest_namespace' )
					);
				}
				return self::$cache[ Controllers\Rest\Payment_REST_Controller::class ];
			}
		);
		Reg::registerService(
			Controllers\Rest\Settings_REST_Controller::class,
			static function () {
				if ( ! isset( self::$cache[ Controllers\Rest\Settings_REST_Controller::class ] ) ) {
					self::$cache[ Controllers\Rest\Settings_REST_Controller::class ] = new Controllers\Rest\Settings_REST_Controller(
						Reg::getService( 'plugin.rest_namespace' ),
						Reg::getService( Configuration::class )
					);
				}
				return self::$cache[ Controllers\Rest\Settings_REST_Controller::class ];
			}
		);
	}
}
