<?php
/**
 * Post install migration for version 3.1.2 of the plugin.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Repositories
 */

namespace SeQura\WC\Repositories\Migrations;

use Exception;
use SeQura\Core\BusinessLogic\AdminAPI\AdminAPI;
use SeQura\Core\BusinessLogic\AdminAPI\Connection\Requests\OnboardingRequest;
use SeQura\Core\BusinessLogic\AdminAPI\CountryConfiguration\Requests\CountryConfigurationRequest;
use SeQura\WC\Core\Extension\BusinessLogic\AdminAPI\GeneralSettings\Requests\General_Settings_Request;
use SeQura\WC\Core\Extension\BusinessLogic\AdminAPI\PromotionalWidgets\Requests\Widget_Settings_Request;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\PromotionalWidgets\Models\Widget_Location;
use Throwable;
use SeQura\WC\Core\Extension\Infrastructure\Configuration\Configuration;

/**
 * Post install migration for version 3.1.2 of the plugin.
 */
class Migration_Install_312 extends Migration {

	/**
	 * Hook name.
	 * 
	 * @var string
	 */
	private $hook_name;

	/**
	 * Constructor
	 *
	 * @param \wpdb $wpdb Database instance.
	 * @param string $hook_name Hook name.
	 */
	public function __construct( \wpdb $wpdb, Configuration $configuration, $hook_name ) {
		$this->db            = $wpdb;
		$this->configuration = $configuration;
		$this->hook_name     = $hook_name;
	}

	/**
	 * Get the plugin version when the changes were made.
	 */
	public function get_version(): string {
		return '3.1.2';
	}

	/**
	 * Run the migration.
	 *
	 * @throws Throwable|Critical_Migration_Exception
	 */
	public function run(): void {
		$args = array( $this->hook_name );
		if ( ! \wp_next_scheduled( $this->hook_name, $args ) ) {
			\wp_schedule_event( time(), 'hourly', $this->hook_name, $args );
		}
	}
}
