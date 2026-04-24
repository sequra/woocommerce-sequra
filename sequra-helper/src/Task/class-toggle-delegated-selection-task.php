<?php
/**
 * Task class
 *
 * @package SeQura/Helper
 */

namespace SeQura\Helper\Task;

/**
 * Task class to toggle the seQura delegated payment selection mode via the
 * 'sequra_delegate_payment_method_selection' filter.
 */
class Toggle_Delegated_Selection_Task extends Task {

	/**
	 * Key for the option in wp_options
	 *
	 * @var string
	 */
	public const OPTION = 'sequra_helper_delegated_selection_enabled';

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
	 * Check if delegated payment selection is enabled
	 */
	public static function is_delegated_selection_enabled(): bool {
		return strval( get_option( self::OPTION, self::FALSE ) ) === self::TRUE;
	}
}
