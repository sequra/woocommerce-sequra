<?php
/**
 * Log File Interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services;

use Exception;
use Throwable;
use WP_Filesystem_Base;

/**
 * Provides methods to perform read and write operations on a log file.
 */
class Log_File implements Interface_Log_File {

	private const MAX_LOG_SIZE = 2 * 1024 * 1024; // 2MB

	/**
	 * The path to the log file.
	 *
	 * @var string
	 */
	private $log_file_path;

	/**
	 * Constructor.
	 * 
	 * @param string $log_file_path The path to the log file.
	 */
	public function __construct( $log_file_path ) {
		$this->log_file_path = $log_file_path;
	}

	/**
	 * Append content at the end of the log file.
	 *
	 * @param string      $content The content to append.
	 * @throws \Exception If something goes wrong. The exception message will contain the error message.
	 */
	public function append_content( $content ): void {
		// WP_Filesystem does not support FILE_APPEND flag, so we need to use file_put_contents.
		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
		file_put_contents( $this->get_log_file_path(), $content, FILE_APPEND );
	}

	/**
	 * Get the WP Filesystem object.
	 * 
	 * @throws \Exception If something goes wrong. The exception message will contain the error message.
	 */
	private function get_file_system(): WP_Filesystem_Base {
		global $wp_filesystem;
		require_once ABSPATH . '/wp-admin/includes/file.php';
		if ( ! WP_Filesystem() ) {
			throw new Exception( 'Could not load WP Filesystem.' );
		}
		return $wp_filesystem;
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
		if ( ! $this->setup() ) {
			throw new Exception( 'Could not setup log file.' );
		}

		$wp_filesystem = $this->get_file_system();
		$content       = array();
		$path          = $this->get_log_file_path( $store_id );
		if ( ! $wp_filesystem->exists( $path ) ) {
			throw new Exception( 'Could not read log file.' );
		}

		$content = $wp_filesystem->get_contents_array( $path );

		if ( false === $content ) {
			throw new Exception( 'Could not read log file.' );
		}
		
		return $content;
	}

	/**
	 * Clear the log file.
	 *
	 * @param string $store_id The store ID.
	 * @throws \Exception If something goes wrong. The exception message will contain the error message.
	 */
	public function clear( $store_id = null ): void {
		if ( null === $store_id ) {
			$store_id = $this->current_store_id();
		}
		$log_file_path = $this->get_log_file_path( $store_id );
		$wp_filesystem = $this->get_file_system();
		if ( $wp_filesystem->exists( $log_file_path ) && ! $wp_filesystem->delete( $log_file_path, false, 'f' ) ) {
			throw new Exception( 'Could not clear log file.' );
		}

		if ( ! $this->setup() ) {
			throw new Exception( 'Could not setup log file.' );
		}
	}

	/**
	 * Make sure the log file exists and is writable.
	 */
	public function setup(): bool {
		try {
			$log_file_path = $this->get_log_file_path();
			$wp_filesystem = $this->get_file_system();

			if ( ! $wp_filesystem->exists( $log_file_path ) ) {
				$dir = dirname( $log_file_path );
				if ( ! $wp_filesystem->is_dir( $dir ) && $wp_filesystem->exists( $dir ) ) {
					return false;
				}

				if ( ! $wp_filesystem->exists( $dir ) && ! $wp_filesystem->mkdir( $dir, 0755 ) ) {
					return false;
				}

				if ( ! $wp_filesystem->put_contents( $log_file_path, '' ) ) {
					return false;
				}
			}

			if ( ! $wp_filesystem->is_writable( dirname( $log_file_path ) ) ) {
				return false;
			}

			// check if log file exceed 2MB and if so, clear it.
			if ( filesize( $log_file_path ) >= self::MAX_LOG_SIZE ) {
				$this->clear();
				return $this->setup();
			}

			return true;
		} catch ( Throwable $e ) {
			return false;
		}
	}

	/**
	 * Get the log file path.
	 * 
	 * @param string|null $store_id The store ID.
	 */
	private function get_log_file_path( $store_id = null ): string {
		$path = $this->log_file_path;
		if ( null === $store_id ) {
			$store_id = $this->current_store_id();
		}
		return str_replace( '{storeId}', $store_id, $path );
	}

	/**
	 * Get the current store ID.
	 */
	private function current_store_id(): string {
		return (string) get_current_blog_id();
	}
}
