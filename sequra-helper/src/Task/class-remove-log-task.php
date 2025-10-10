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
class Remove_Log_Task extends Task {

	/**
	 * Execute the task
	 * 
	 * @param array<string, string> $args Arguments for the task.
	 * 
	 * @throws \Exception If the task fails.
	 */
	public function execute( array $args = array() ): void {
		$sequra_dir = dirname( __DIR__, 3 ) . '/_sequra';

		$files = glob( $sequra_dir . '/*.log' ) ?: array();

		foreach ( $files as $file ) {
			if ( is_file( $file ) && ! unlink( $file ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink
				throw new \Exception( 'Could not remove log file.' );
			}
		}
	}
}
