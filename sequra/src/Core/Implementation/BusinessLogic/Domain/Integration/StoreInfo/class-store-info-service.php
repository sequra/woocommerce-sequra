<?php
/**
 * Store Info Service implementation
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Implementation\BusinessLogic\Domain\Integration\StoreInfo;

use SeQura\Core\BusinessLogic\Domain\Integration\StoreInfo\StoreInfoServiceInterface;
use SeQura\Core\BusinessLogic\Domain\Stores\Models\StoreInfo;
use SeQura\WC\Services\Platform\Interface_Platform_Provider;

/**
 * Store Info Service implementation
 */
class Store_Info_Service implements StoreInfoServiceInterface {

	/**
	 * Platform provider
	 * 
	 * @var Interface_Platform_Provider
	 */
	private $platform_provider;
	
	/**
	 * Construct
	 * 
	 * @param Interface_Platform_Provider $platform_provider The platform provider
	 */
	public function __construct( Interface_Platform_Provider $platform_provider ) {
		$this->platform_provider = $platform_provider;
	}

	/**
	 * Gets store information including platform details, versions, and installed plugins.
	 *
	 * @return StoreInfo
	 */
	public function getStoreInfo(): StoreInfo {
		$platform       = $this->platform_provider->get();
		$db_version     = trim( implode( ' ', array( ( $platform->getDbName() ?? '' ), ( $platform->getDbVersion() ?? '' ) ) ) );
		$active_plugins = array();
		$opt_value      = \get_option( 'active_plugins', array() );
		if ( \is_array( $opt_value ) ) {
			foreach ( $opt_value as $plugin ) {
				$plugin_data      = \get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin, true, false );
				$active_plugins[] = trim( implode( ' ', array( ( $plugin_data['Name'] ?? '' ), ( $plugin_data['Version'] ?? '' ) ) ) );
			}
		}

		return new StoreInfo(
			\get_bloginfo( 'name' ),
			\get_home_url(),
			$platform->getName(),
			$platform->getVersion(),
			$platform->getPluginVersion() ?? '',
			$platform->getPhpVersion() ?? '',
			$db_version,
			$platform->getUname() ?? '',
			$active_plugins
		);
	}
}
