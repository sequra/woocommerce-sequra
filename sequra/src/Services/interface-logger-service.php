<?php
/**
 * Logger service
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services;

use SeQura\Core\Infrastructure\Logger\LogContextData;
use Throwable;

/**
 * Logger service
 */
interface Interface_Logger_Service {

	/**
	 * Log a message with the severity "WARNING".
	 *
	 * @param string      $message The message to log.
	 * @param string|null $func The method name.
	 * @param string|null $class_name The class name.
	 * @param LogContextData[] $context The context.
	 */
	public function log_warning( $message, $func = null, $class_name = null, $context = array() ): void;

	/**
	 * Log a message with the severity "INFO".
	 *
	 * @param string      $message The message to log.
	 * @param string|null $func The method name.
	 * @param string|null $class_name The class name.
	 * @param LogContextData[] $context The context.
	 */
	public function log_info( $message, $func = null, $class_name = null, $context = array() ): void;

	/**
	 * Log a message with the severity "DEBUG".
	 *
	 * @param string      $message The message to log.
	 * @param string|null $func The method name.
	 * @param string|null $class_name The class name.
	 * @param LogContextData[] $context The context.
	 */
	public function log_debug( $message, $func = null, $class_name = null, $context = array() ): void;

	/**
	 * Log a message with the severity "ERROR".
	 *
	 * @param string      $message The message to log.
	 * @param string|null $func The method name.
	 * @param string|null $class_name The class name.
	 * @param LogContextData[] $context The context.
	 */
	public function log_error( $message, $func = null, $class_name = null, $context = array() ): void;

	/**
	 * Log a message with the severity "ERROR".
	 *
	 * @param Throwable      $throwable The throwable to log.
	 * @param string|null $func The method name.
	 * @param string|null $class_name The class name.
	 * @param LogContextData[] $context Additional context.
	 */
	public function log_throwable( Throwable $throwable, $func = null, $class_name = null, $context = array() ): void;

	/**
	 * Get the content of the log file.
	 *
	 * @param string $store_id The store ID.
	 * @throws \Exception If something goes wrong. The exception message will contain the error message.
	 *
	 * @return string[] Each element is a line of the log file.
	 */
	public function get_content( $store_id ): array;

	/**
	 * Clear the log file.
	 *
	 * @param string $store_id The store ID.
	 * @throws \Exception If something goes wrong. The exception message will contain the error message.
	 */
	public function clear( $store_id ): void;

	/**
	 * Check if logging is enabled.
	 */
	public function is_enabled(): bool;

	/**
	 * Set on/off the logger.
	 * 
	 * @param bool $is_enabled True to enable the logger, false to disable it.
	 */
	public function enable( $is_enabled ): void;

	/**
	 * Get the minimum log level.
	 */
	public function get_min_log_level(): int;

	/**
	 * Set the minimum log level.
	 *
	 * @param int $level The minimum log level.
	 */
	public function set_min_log_level( $level ): void;
}
