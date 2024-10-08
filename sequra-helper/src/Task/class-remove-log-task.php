<?php
/**
 * Task class
 * 
 * @package SeQura/Helper
 */

namespace SeQura\Helper\Task;

// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.Security.EscapeOutput.ExceptionNotEscaped

/**
 * Task class
 */
class Remove_Log_Task extends Task {

	/**
	 * Execute the task
	 * 
	 * @throws \Exception If the task fails
	 */
	public function execute( array $args = array() ): void {
		$sequra_dir = dirname( __DIR__, 3 ) . '/_sequra';

		$files = glob( $sequra_dir . '/*.log' );

		foreach ( $files as $file ) {
			if ( is_file( $file ) && ! unlink( $file ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink
				throw new \Exception( 'Could not remove log file.' );
			}
		}
	}
}
