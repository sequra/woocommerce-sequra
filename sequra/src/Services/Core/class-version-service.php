<?php
/**
 * Wrapper to ease the read and write of configuration values.
 * Delegate to the ConfigurationManager instance to access the data in the database.
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Services\Core;

use SeQura\Core\BusinessLogic\AdminAPI\AdminAPI;
use SeQura\Core\BusinessLogic\Domain\Integration\Version\VersionServiceInterface;
use SeQura\Core\BusinessLogic\Domain\Version\Models\Version;

/**
 * Wrapper to ease the read and write of configuration values.
 */
class Version_Service implements VersionServiceInterface {
	
	/**
	 * The current version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Constructor.
	 * 
	 * @param string $version The current version.
	 */
	public function __construct( $version ) {
		$this->version = $version;
	}

	/**
	 * Returns plugin version information.
	 */
	public function getVersion(): ?Version {
		$new_version = $this->get_marketplace_version();
		if ( version_compare( $this->version, $new_version, '>' ) ) {
			$new_version = $this->version;
		}

		return new Version(
			$this->version,
			$new_version,
			'https://wordpress.org/plugins/sequra/'
		);
	}

	/**
	 * Version published in the marketplace.
	 */
	private function get_marketplace_version(): string {
		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}
		$response = plugins_api(
			'plugin_information',
			array(
				'slug'   => 'sequra',
				'fields' => array( 'version' => true ),
			) 
		);
		if ( is_wp_error( $response ) || empty( $response->version ) ) {
			return '';
		}

		return $response->version;
	}
}
