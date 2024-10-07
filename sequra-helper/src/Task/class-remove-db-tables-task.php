<?php
/**
 * Task class
 * 
 * @package SeQura/Helper
 */

namespace SeQura\Helper\Task;

/**
 * Task class
 */
class Remove_Db_Tables_Task extends Task {

	/**
	 * Execute the task
	 * 
	 * @throws \Exception If the task fails
	 */
	public function execute( array $args = array() ): void {
		$this->drop_tables_in_database(
			array(
				$this->get_sequra_order_table_name(),
				$this->get_sequra_entity_table_name(),
				$this->get_sequra_queue_table_name(),
			)
		);
	}
}
