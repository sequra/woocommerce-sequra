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
class Remove_Address_Fields_Task extends Task {

	/**
	 * Key for the option in wp_options
	 *
	 * @var string
	 */
	public const OPTION = 'sequra_helper_remove_address_fields';
	
	/**
	 * Execute the task
	 * 
	 * @param array<string, string> $args Arguments for the task.
	 * 
	 * @throws \Exception If the task fails.
	 */
	public function execute( array $args = array() ): void {
		$enabled = $this->get_boolean_arg( $args, 'value' );
		update_option( self::OPTION, $enabled, true );
	}

	/**
	 * Check if the option is enabled
	 */
	public static function is_option_enabled(): bool {
		return strval( get_option( self::OPTION, self::FALSE ) ) === self::TRUE;
	}
}
