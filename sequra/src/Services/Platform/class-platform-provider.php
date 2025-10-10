<?php
/**
 * Platform Provider Interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Platform;

use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Platform;
use SeQura\WC\Services\Constants\Interface_Constants;

/**
 * Provide the current platform
 */
class Platform_Provider implements Interface_Platform_Provider {

	/**
	 * Constants service
	 * 
	 * @var Interface_Constants
	 */
	private $constants;

	/**
	 * Platform instance
	 * 
	 * @var Platform
	 */
	private $platform;

	/**
	 * Constructor
	 */
	public function __construct( Interface_Constants $constants ) {
		$this->constants = $constants;
	}

	/**
	 * Get the current platform
	 */
	public function get(): Platform {
		if ( ! $this->platform ) {
			/**
			 * WooCommerce data
			 *
			 * @var array<string, string>
			 */
			$woo = $this->constants->get_woocommerce_data();
		
			/**
			 * Environment data
			 * 
			 * @var array<string, string>
			 */
			$env = $this->constants->get_environment_data();

			/**
			 * Plugin data
			 * 
			 * @var array<string, string>
			 */
			$sq = $this->constants->get_plugin_data();

			/**
			* Filter the module version to be used in the platform options.
			* 
			* @since 3.0.0
			*/
			$version = \apply_filters(
				'sequra_platform_options_version',
				$sq['Version'] ?? ''
			);
		
			$platform_version = ( $woo['Version'] ?? '' ) . ( isset( $env['wp_version'] ) ? " + WordPress {$env['wp_version']}" : '' );

			/**
			 * Filter the platform options.
			 *
			 * @since 3.0.0
			 */
			return \apply_filters(
				'sequra_platform_options',
				new Platform(
					$this->constants->get_integration_name(),
					$platform_version,
					$env['uname'],
					$env['db_name'],
					$env['db_version'],
					$version,
					$env['php_version']
				) 
			);
		}
		return $this->platform;
	}
}
