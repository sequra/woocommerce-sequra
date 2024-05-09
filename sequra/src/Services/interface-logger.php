<?php
/**
 * Logger interface
 *
 * @package    Sequra/WC
 * @subpackage Sequra/WC/Services
 */

namespace Sequra\WC\Services;

/**
 * Logger interface
 */
interface Interface_Logger {

	/**
	 * Get the content of the log file.
	 *
	 * @throws \Exception If something goes wrong. The exception message will contain the error message.
	 *
	 * @return string The content of the log file
	 */
	public function get_log_content();

	/**
	 * Clear the log file.
	 *
	 * @throws \Exception If something goes wrong. The exception message will contain the error message.
	 */
	public function clear_log();

	/**
	 * Log a message with the severity "WARNING".
	 *
	 * @param string      $message The message to log.
	 * @param string|null $func The method name.
	 * @param string|null $class_name The class name.
	 */
	public function log_warning( $message, $func = null, $class_name = null );

	/**
	 * Log a message with the severity "INFO".
	 *
	 * @param string      $message The message to log.
	 * @param string|null $func The method name.
	 * @param string|null $class_name The class name.
	 */
	public function log_info( $message, $func = null, $class_name = null );

	/**
	 * Log a message with the severity "DEBUG".
	 *
	 * @param string      $message The message to log.
	 * @param string|null $func The method name.
	 * @param string|null $class_name The class name.
	 */
	public function log_debug( $message, $func = null, $class_name = null );

	/**
	 * Log a message with the severity "ERROR".
	 *
	 * @param string      $message The message to log.
	 * @param string|null $func The method name.
	 * @param string|null $class_name The class name.
	 */
	public function log_error( $message, $func = null, $class_name = null );
}
