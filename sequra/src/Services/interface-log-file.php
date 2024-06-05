<?php
/**
 * Log File Interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services;

/**
 * Provides methods to perform read and write operations on a log file.
 */
interface Interface_Log_File {

	/**
	 * Append content at the end of the log file.
	 *
	 * @param string      $content The content to append.
	 * @throws \Exception If something goes wrong. The exception message will contain the error message.
	 */
	public function append_content( $content ): void;

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
	public function clear( $store_id = null ): void;

	/**
	 * Make sure the log file exists and is writable.
	 */
	public function setup(): bool;
}
