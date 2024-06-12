<?php
/**
 * Logger service
 * 
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services;

use Exception;
use SeQura\Core\Infrastructure\Logger\LogContextData;
use SeQura\Core\Infrastructure\Logger\Logger;
use SeQura\Core\Infrastructure\Logger\LoggerConfiguration;
use Throwable;

/**
 * Logger service
 */
class Logger_Service implements Interface_Logger_Service {

	/**
	 * Logger configuration.
	 *
	 * @var LoggerConfiguration
	 */
	private $configuration;

	/**
	 * The log file.
	 *
	 * @var Interface_Log_File
	 */
	private $log_file;

	/**
	 * Constructor.
	 * 
	 * @param LoggerConfiguration $configuration The logger configuration.
	 * @param Interface_Log_File $log_file The log file.
	 */
	public function __construct( LoggerConfiguration $configuration, Interface_Log_File $log_file ) {
		$this->configuration = $configuration;
		$this->log_file      = $log_file;
	}

	/**
	 * Get the content of the log file.
	 *
	 * @param string $store_id The store ID.
	 * @throws \Exception If something goes wrong. The exception message will contain the error message.
	 *
	 * @return string[] Each element is a line of the log file.
	 */
	public function get_content( $store_id ): array {
		return $this->log_file->get_content( $store_id );
	}

	/**
	 * Clear the log file.
	 *
	 * @param string $store_id The store ID.
	 * @throws \Exception If something goes wrong. The exception message will contain the error message.
	 */
	public function clear( $store_id ): void {
		$this->log_file->clear( $store_id );
	}

	/**
	 * Log a message with the severity "WARNING".
	 *
	 * @param string      $message The message to log.
	 * @param string|null $func The method name.
	 * @param string|null $class_name The class name.
	 * @param LogContextData[] $context The context.
	 */
	public function log_warning( $message, $func = null, $class_name = null, $context = array() ): void {
		Logger::logWarning( $this->format_msg( $message, $func, $class_name ), 'Plugin', $context );
	}

	/**
	 * Log a message with the severity "INFO".
	 *
	 * @param string      $message The message to log.
	 * @param string|null $func The method name.
	 * @param string|null $class_name The class name.
	 * @param LogContextData[] $context The context.
	 */
	public function log_info( $message, $func = null, $class_name = null, $context = array() ): void {
		Logger::logInfo( $this->format_msg( $message, $func, $class_name ), 'Plugin', $context );
	}

	/**
	 * Log a message with the severity "DEBUG".
	 *
	 * @param string      $message The message to log.
	 * @param string|null $func The method name.
	 * @param string|null $class_name The class name.
	 * @param LogContextData[] $context The context.
	 */
	public function log_debug( $message, $func = null, $class_name = null, $context = array() ): void {
		Logger::logDebug( $this->format_msg( $message, $func, $class_name ), 'Plugin', $context );
	}

	/**
	 * Log a message with the severity "ERROR".
	 *
	 * @param string      $message The message to log.
	 * @param string|null $func The method name.
	 * @param string|null $class_name The class name.
	 * @param LogContextData[] $context The context.
	 */
	public function log_error( $message, $func = null, $class_name = null, $context = array() ): void {
		Logger::logError( $this->format_msg( $message, $func, $class_name ), 'Plugin', $context );
	}

	/**
	 * Log a message with the severity "ERROR".
	 *
	 * @param Throwable      $throwable The throwable to log.
	 * @param string|null $func The method name.
	 * @param string|null $class_name The class name.
	 * @param LogContextData[] $context Additional context.
	 */
	public function log_throwable( Throwable $throwable, $func = null, $class_name = null, $context = array() ): void {
		$message       = get_class( $throwable );
		$error_context = array(
			new LogContextData( 'message', $throwable->getMessage() ),
			new LogContextData( 'code', $throwable->getCode() ),
			new LogContextData( 'file', $throwable->getFile() ),
			new LogContextData( 'line', $throwable->getLine() ),
			new LogContextData( 'trace', $throwable->getTraceAsString() ),
		);
		Logger::logError( $this->format_msg( $message, $func, $class_name ), 'Plugin', array_merge( $context, $error_context ) );
	}

	/**
	 * Check if logging is enabled.
	 */
	public function is_enabled(): bool {
		return $this->configuration->isDefaultLoggerEnabled();
	}

	/**
	 * Set on/off the logger.
	 * 
	 * @param bool $is_enabled True to enable the logger, false to disable it.
	 */
	public function enable( $is_enabled ): void {
		$this->configuration->setDefaultLoggerEnabled( $is_enabled );
	}

	/**
	 * Get the minimum log level.
	 */
	public function get_min_log_level(): int {
		return $this->configuration->getMinLogLevel();
	}

	/**
	 * Set the minimum log level.
	 *
	 * @param int $level The minimum log level.
	 */
	public function set_min_log_level( $level ): void {
		$this->configuration->setMinLogLevel( $level );
	}

	/**
	 * Helper function to format the message.
	 *
	 * @param string $msg The message to log.
	 * @param string $func The method name.
	 * @param string $class_name_name The class name.
	 *
	 * @return string
	 */
	private function format_msg( $msg, $func = null, $class_name_name = null ) {
		$message = '';

		if ( ! empty( $func ) ) {
			$message .= $func . '()';
		}
		if ( ! empty( $class_name_name ) ) {
			$message = $class_name_name . '::' . $message;
		}

		if ( ! empty( $message ) ) {
			$message .= ' - ';
		}

		return $message . $msg;
	}
}
