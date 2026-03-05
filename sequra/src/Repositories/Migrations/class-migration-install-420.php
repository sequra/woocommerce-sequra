<?php
/**
 * Post install migration for version 4.2.0 of the plugin.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Repositories
 */

namespace SeQura\WC\Repositories\Migrations;

use SeQura\Core\BusinessLogic\Domain\AdvancedSettings\Models\AdvancedSettings;
use SeQura\Core\BusinessLogic\Domain\AdvancedSettings\Services\AdvancedSettingsService;
use Throwable;

/**
 * Post install migration for version 4.2.0 of the plugin.
 */
class Migration_Install_420 extends Migration {

	/**
	 * Entity table.
	 *
	 * @var string
	 */
	private $entity_table;

	/**
	 * Advanced settings service.
	 *
	 * @var AdvancedSettingsService
	 */
	private $advanced_settings_service;

	/**
	 * Get the plugin version when the changes were made.
	 */
	public function get_version(): string {
		return '4.2.0';
	}

	/**
	 * Constructor
	 *
	 * @param \wpdb                  $wpdb                     Database instance.
	 * @param AdvancedSettingsService $advanced_settings_service Advanced settings service.
	 */
	public function __construct( \wpdb $wpdb, AdvancedSettingsService $advanced_settings_service ) {
		parent::__construct( $wpdb );
		$this->entity_table              = $this->db->prefix . 'sequra_entity';
		$this->advanced_settings_service = $advanced_settings_service;
	}

	/**
	 * Migrate Advanced settings to the new format
	 *
	 * @throws Throwable
	 */
	private function migrate_advanced_settings(): void {
		// Skip migration if the new Advanced settings are already set.
		if ( $this->advanced_settings_service->getAdvancedSettings() ) {
			return;
		}
	
		// @phpstan-ignore-next-line
		$query              = $this->db->prepare( 'SELECT `data` FROM ' . $this->entity_table . ' WHERE `type` = %s AND `index_1` = %s LIMIT 1', 'Configuration', 'defaultLoggerEnabled' );
		$logger_enabled_row = $this->db->get_row( $query, ARRAY_A );

		// @phpstan-ignore-next-line
		$query         = $this->db->prepare( 'SELECT `data` FROM ' . $this->entity_table . ' WHERE `type` = %s AND `index_1` = %s LIMIT 1', 'Configuration', 'minLogLevel' );
		$log_level_row = $this->db->get_row( $query, ARRAY_A );

		if ( null === $logger_enabled_row && null === $log_level_row ) {
			return;
		}

		$is_enabled = false;
		if ( null !== $logger_enabled_row && isset( $logger_enabled_row['data'] ) && is_string( $logger_enabled_row['data'] ) ) {
			$data       = json_decode( $logger_enabled_row['data'], true );
			$is_enabled = is_array( $data ) && ! empty( $data['value'] );

			$this->db->delete(
				$this->entity_table,
				array(
					'type'    => 'Configuration',
					'index_1' => 'defaultLoggerEnabled',
				)
			);
		}

		$level = 3;
		if ( null !== $log_level_row && isset( $log_level_row['data'] ) && is_string( $log_level_row['data'] ) ) {
			$data  = json_decode( $log_level_row['data'], true );
			$level = is_array( $data ) && isset( $data['value'] ) ? (int) $data['value'] : 3;

			$this->db->delete(
				$this->entity_table,
				array(
					'type'    => 'Configuration',
					'index_1' => 'minLogLevel',
				)
			);
		}

		$settings = new AdvancedSettings( $is_enabled, $level );
		$this->advanced_settings_service->setAdvancedSettings( $settings );
	}

	/**
	 * Run the migration.
	 *
	 * @throws Throwable
	 */
	public function run(): void {
		$this->migrate_advanced_settings();
	}
}
