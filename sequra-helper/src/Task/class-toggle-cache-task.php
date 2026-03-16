<?php
/**
 * Task class
 *
 * @package SeQura/Helper
 */

namespace SeQura\Helper\Task;

/**
 * Task class to toggle the seQura repository cache via the 'sequra_cache_enabled' filter.
 */
class Toggle_Cache_Task extends Task {

	/**
	 * Key for the option in wp_options
	 *
	 * @var string
	 */
	public const OPTION = 'sequra_helper_cache_disabled';

	/**
	 * Execute the task
	 *
	 * @param array<string, string> $args Arguments for the task.
	 *
	 * @throws \Exception If the task fails.
	 */
	public function execute( array $args = array() ): void {
		$disabled = $this->get_boolean_arg( $args, 'value' );
		update_option( self::OPTION, $disabled, true );
		$this->flush_sequra_cache();
	}

	/**
	 * Check if the cache is disabled
	 */
	public static function is_cache_disabled(): bool {
		return strval( get_option( self::OPTION, self::FALSE ) ) === self::TRUE;
	}
}
