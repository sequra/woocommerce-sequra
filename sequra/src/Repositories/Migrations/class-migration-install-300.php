<?php
/**
 * Post install migration for version 3.0.0 of the plugin.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Repositories
 */

namespace SeQura\WC\Repositories\Migrations;

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
	 */
	public function run(): void {
		$charset_collate = $this->db->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		foreach ( array( 'sequra_entity', 'sequra_order' ) as $table ) {
			$table_name = $this->db->prefix . $table;
			\dbDelta(
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
		}

		$table_name = $this->db->prefix . 'sequra_queue';
		\dbDelta(
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
	}
}
