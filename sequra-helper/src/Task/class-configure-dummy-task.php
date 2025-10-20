<?php
/**
 * Configure Dummy Task class
 * 
 * @package SeQura/Helper
 */

namespace SeQura\Helper\Task;

/**
 * Configure Dummy Task class
 */
class Configure_Dummy_Task extends Configure_Sequra_Entities_Task {

	/**
	 * Check if dummy merchant configuration is in use
	 * 
	 * @param bool $widgets Whether to include widget settings.
	 */
	protected function is_merchant_configured( bool $widgets ): bool {
		$expected_rows = $widgets ? 3 : 2;
		global $wpdb;
		$table_name = $this->get_sequra_entity_table_name();
		$query      = "SELECT * FROM $table_name WHERE (`type` = 'ConnectionData' AND `data` LIKE '%\"username\":\"dummy_automated_tests\"%') OR (`type` = 'WidgetSettings' AND `data` LIKE '%\"displayOnProductPage\":true%')";
		$result     = $wpdb->get_results( $query );
		return is_array( $result ) && count( $result ) === $expected_rows;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( self::DUMMY );
	}
}
