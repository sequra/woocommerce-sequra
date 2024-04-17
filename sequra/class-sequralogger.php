<?php
/**
 * Sequra Logger class.
 * 
 * @package woocommerce-sequra
 */

// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_error_log
/**
 * Sequra Logger class.
 */
class SequraLogger {

	private const DEBUG   = 0;
	private const INFO    = 1;
	private const WARNING = 2;
	private const ERROR   = 3;

	private const LEVEL_MAP = array(
		self::DEBUG   => 'DEBUG',
		self::INFO    => 'INFO',
		self::WARNING => 'WARNING',
		self::ERROR   => 'ERROR',
	);

	/**
	 * The path to the log file.
	 *
	 * @var string
	 */
	private $log_file_path;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->log_file_path = WC_SEQURA_PLG_PATH . 'sequra.log';
	}

	/**
	 * Make sure the log file exists and is writable.
	 *
	 * @return bool
	 */
	private function setup() {
		if ( ! file_exists( $this->log_file_path ) ) {
			$dir = dirname( $this->log_file_path );
			if ( ! is_dir( $dir ) && file_exists( $dir ) ) {
				return false;
			}
			// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_mkdir
			if ( ! file_exists( $dir ) && ! mkdir( $dir, 0755, true ) ) {
				return false;
			}

			if ( ! file_put_contents( $this->get_formatted_message( "Log file created" ), $this->log_file_path ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
				return false;
			}
		}

		if ( ! is_writable( dirname( $this->log_file_path ) ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_is_writable
			return false;
		}

		return true;
	}

	/**
	 * Get the content of the log file.
	 *
	 * @throws \Exception If something goes wrong. The exception message will contain the error message.
	 *
	 * @return string The content of the log file
	 */
	public function get_log_content() {
		if ( ! $this->setup() ) {
			throw new \Exception( 'Could not setup log file.' );
		}

		$content = file_get_contents( $this->log_file_path ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
		if ( false === $content ) {
			throw new \Exception( 'Could not read log file.' );
		}

		return $content;
	}

	/**
	 * Clear the log file.
	 *
	 * @throws \Exception If something goes wrong. The exception message will contain the error message.
	 */
	public function clear_log() {
		if ( file_exists( $this->log_file_path ) && ! unlink( $this->log_file_path ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink
			throw new \Exception( 'Could not clear log file.' );
		}
		if ( ! $this->setup() ) {
			throw new \Exception( 'Could not setup log file.' );
		}
	}

	/**
	 * Log a message with the severity "WARNING".
	 *
	 * @param string      $message The message to log.
	 * @param string|null $func The method name.
	 * @param string|null $class_name The class name.
	 */
	public function log_warning( $message, $func = null, $class_name = null ) {
		$this->log( $message, $func, $class_name, self::WARNING );
	}

	/**
	 * Log a message with the severity "INFO".
	 *
	 * @param string      $message The message to log.
	 * @param string|null $func The method name.
	 * @param string|null $class_name The class name.
	 */
	public function log_info( $message, $func = null, $class_name = null ) {
		$this->log( $message, $func, $class_name, self::INFO );
	}

	/**
	 * Log a message with the severity "DEBUG".
	 *
	 * @param string      $message The message to log.
	 * @param string|null $func The method name.
	 * @param string|null $class_name The class name.
	 */
	public function log_debug( $message, $func = null, $class_name = null ) {
		$this->log( $message, $func, $class_name, self::DEBUG );
	}

	/**
	 * Log a message with the severity "ERROR".
	 *
	 * @param string      $message The message to log.
	 * @param string|null $func The method name.
	 * @param string|null $class_name The class name.
	 */
	public function log_error( $message, $func = null, $class_name = null ) {
		$this->log( $message, $func, $class_name, self::ERROR );
	}

	/**
	 * Log a message with the severity "ERROR".
	 *
	 * @param string      $message The message to log.
	 * @param string|null $func The method name.
	 * @param string|null $class_name The class name.
	 * @param int         $level The log level. Use self::DEBUG, self::INFO, self::WARNING, self::ERROR.
	 * @throws \Exception If something goes wrong. The exception message will contain the error message.
	 */
	private function log( $message, $func = null, $class_name = null, $level = self::DEBUG ) {
		if ( ! $this->is_log_enabled() ) {
			return;
		}
		if ( $this->setup() ) {
			$formatted_message = $this->get_formatted_message( $message, $func, $class_name, $level );
			file_put_contents( $this->log_file_path, $formatted_message, FILE_APPEND ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
		}
	}

	private function get_formatted_message( $message, $func = null, $class_name = null, $level = self::DEBUG ) {
		return sprintf(
			'*%s*\tv%s\t%s: %s\r\n',
			self::LEVEL_MAP[ $level ],
			get_bloginfo( 'version' ),
			date( 'Y-m-d H:i:s' ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			$this->format_msg( $message, $func, $class_name )
		);
	}

	/**
	 * Check if the log is enabled.
	 *
	 * @return bool
	 */
	private function is_log_enabled() {
		// Move this responsibility to a settings service when proper class autoloading is working.
		// Current implementation of loading classes by using require_once might be problematic.
		$opts = (array) get_option( 'woocommerce_sequra_settings', false );
		return isset( $opts['debug'] ) && 'yes' === $opts['debug'];
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

		if ( is_array( $msg ) || is_object( $msg ) ) {
			$msg = wp_json_encode( $msg );
		}

		return $message . $msg;
	}
}
