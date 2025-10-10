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
abstract class WC_UI_Version_Task extends Task {

	protected const CLASSIC = 'classic';
	protected const BLOCKS  = 'blocks';

	/**
	 * Get version passed in the arguments
	 * 
	 * @param array<string, string> $args The arguments.
	 * 
	 * @throws \Exception If the version is invalid.
	 */
	protected function get_version( array $args = array() ): string {
		if ( ! isset( $args['version'] ) ) {
			throw new \Exception( 'Invalid version', 400 );
		}

		$version = sanitize_text_field( wp_unslash( $args['version'] ) );

		if ( ! in_array( $version, array( self::CLASSIC, self::BLOCKS ), true ) ) {
			throw new \Exception( 'Invalid version', 400 );
		}
		return $version;
	}
}
