<?php
/**
 * Post install migration for version 3.0.0 of the plugin.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Repositories
 */

namespace SeQura\WC\Repositories\Migrations;

use Exception;
use SeQura\Core\BusinessLogic\AdminAPI\AdminAPI;
use SeQura\Core\BusinessLogic\AdminAPI\Connection\Requests\OnboardingRequest;
use Throwable;

/**
 * Post install migration for version 3.0.0 of the plugin.
 */
class Migration_Install_300 extends Migration {

	/**
	 * Get the plugin version when the changes were made.
	 */
	public function get_version(): string {
		return '3.0.0';
	}

	/**
	 * Run the migration.
	 *
	 * @throws Throwable|Critical_Migration_Exception
	 */
	public function run(): void {
		$this->add_new_tables_to_database();
		// TODO: Migrate old settings to new settings.
		$woocommerce_sequra_settings = (array) get_option( 'woocommerce_sequra_settings', array() );
		if ( ! empty( $woocommerce_sequra_settings ) ) {
			$this->migrate_connection_configuration( $woocommerce_sequra_settings );
			$this->migrate_country_configuration( $woocommerce_sequra_settings );
			$this->migrate_widget_configuration( $woocommerce_sequra_settings );
			$this->migrate_logger_configuration( $woocommerce_sequra_settings );
		}
	}

	/**
	 * Check if the table exists in the database
	 *
	 * @throws Critical_Migration_Exception
	 */
	private function check_if_table_exists( string $table_name ): void {
		if ( $this->db->get_var( "SHOW TABLES LIKE '{$table_name}'" ) !== $table_name ) {
			throw new Critical_Migration_Exception( esc_html( "Could not create the table \"$table_name\"" ) );
		}
	}

	/**
	 * Add new tables to the database.
	 * 
	 * @throws Throwable|Critical_Migration_Exception
	 */
	private function add_new_tables_to_database(): void {
		$charset_collate = $this->db->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		foreach ( array( 'sequra_entity', 'sequra_order' ) as $table ) {
			$table_name = $this->db->prefix . $table;
			dbDelta(
				"CREATE TABLE IF NOT EXISTS $table_name (
				`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				`type` VARCHAR(255),
				`index_1` VARCHAR(127),
				`index_2` VARCHAR(127),
				`index_3` VARCHAR(127),
				`index_4` VARCHAR(127),
				`index_5` VARCHAR(127),
				`index_6` VARCHAR(127),
				`index_7` VARCHAR(127),
				`data` LONGTEXT,
				PRIMARY KEY  (id)
			) $charset_collate;" 
			);
			$this->check_if_table_exists( $table_name );
		}

		$table_name = $this->db->prefix . 'sequra_queue';
		dbDelta(
			"CREATE TABLE IF NOT EXISTS $table_name (
			`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`type` VARCHAR(255),
			`index_1` VARCHAR(127),
			`index_2` VARCHAR(127),
			`index_3` VARCHAR(127),
			`index_4` VARCHAR(127),
			`index_5` VARCHAR(127),
			`index_6` BIGINT UNSIGNED,
			`index_7` BIGINT UNSIGNED,
			`index_8` BIGINT UNSIGNED,
			`index_9` BIGINT UNSIGNED,
			`data` LONGTEXT,
			PRIMARY KEY  (id)
		) $charset_collate;" 
		);
		$this->check_if_table_exists( $table_name );
	}

	/** Migrate connection settings from v2
	 *
	 * @param string[] $settings
	 * @throws Throwable|Exception
	 */
	private function migrate_connection_configuration( array $settings ): void {
		$env_mapping = array(
			'0' => 'live',
			'1' => 'sandbox',
		);

		if ( ! isset( $settings['env'], $settings['user'], $settings['password'] ) 
		|| ! array_key_exists( $settings['env'], $env_mapping ) ) {
			// Skip this migration if the environment is not set or is not valid.
			return;
		}

		$response = AdminAPI::get()
		->connection( $this->configuration->get_store_id() )
		->saveOnboardingData(
			new OnboardingRequest(
				$env_mapping[ $settings['env'] ],
				strval( $settings['user'] ),
				strval( $settings['password'] ),
				true
			)
		);

		if ( ! $response->isSuccessful() ) {
			throw new Exception( esc_html( $response['errorMessage'] ) );
		}
	}

	/** Migrate country settings from v2 */
	private function migrate_country_configuration( array $settings ): void {
	}

	/** Migrate widget settings from v2 */
	private function migrate_widget_configuration( array $settings ): void {
	}
	
	/** Migrate logger settings from v2 */
	private function migrate_logger_configuration( array $settings ): void {
	}
}
