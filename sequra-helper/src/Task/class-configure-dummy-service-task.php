<?php
/**
 * Configure Dummy Service Task class
 * 
 * @package SeQura/Helper
 */

namespace SeQura\Helper\Task;

/**
 * Configure Dummy Service Task class
 */
class Configure_Dummy_Service_Task extends Configure_Sequra_Entities_Task {

	/**
	 * Check if dummy merchant configuration is in use
	 * 
	 * @param bool $widgets Whether to include widget settings.
	 */
	protected function is_merchant_configured( bool $widgets ): bool {
		global $wpdb;
		$table_name = $this->get_sequra_entity_table_name();
		$query      = "SELECT * FROM $table_name WHERE type = 'ConnectionData' AND `data` LIKE '%\"username\":\"dummy_services_automated_tests\"%'";
		$result     = $wpdb->get_results( $query );
		return is_array( $result ) && ! empty( $result );
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( self::DUMMY_SERVICES );
	}
}
