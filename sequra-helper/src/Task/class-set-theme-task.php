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
class Set_Theme_Task extends Task {

	/**
	 * Execute the task
	 * 
	 * @param array<string, string> $args Arguments for the task.
	 * 
	 * @throws \Exception If the task fails.
	 */
	public function execute( array $args = array() ): void {
		if ( ! isset( $args['theme'] ) ) {
			throw new \Exception( 'Invalid theme name', 400 );
		}
		switch_theme( sanitize_text_field( wp_unslash( $args['theme'] ) ) );
	}
}
