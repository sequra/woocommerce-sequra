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
class Clear_Configuration_Task extends Task {

	/**
	 * Execute the task
	 * 
	 * @param string[] $args Arguments for the task.
	 * 
	 * @throws \Exception If the task fails.
	 */
	public function execute( array $args = array() ): void {
		$this->recreate_entity_table_in_database();
	}
}
