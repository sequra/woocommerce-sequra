<?php
/**
 * Default Logger
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Implementation\Infrastructure\Logger\Interfaces;

use SeQura\Core\Infrastructure\Logger\Interfaces\DefaultLoggerAdapter;
use SeQura\Core\Infrastructure\Logger\LogData;
use SeQura\Core\Infrastructure\Logger\Logger;
use SeQura\Core\Infrastructure\Utility\TimeProvider;
use SeQura\WC\Services\Interface_Log_File;
use Throwable;

/**
 * Default Logger
 */
class Default_Logger_Adapter implements DefaultLoggerAdapter {

	/**
	 * The log file path.
	 *
	 * @var Interface_Log_File
	 */
	private $log_file;

	/**
	 * The time provider.
	 *
	 * @var TimeProvider
	 */
	private $time_provider;

	/**
	 * Constructor.
	 */
	public function __construct( Interface_Log_File $log_file, TimeProvider $time_provider ) {
		$this->log_file      = $log_file;
		$this->time_provider = $time_provider;
	}

	/**
	 * Get the level name.
	 * 
	 * @param int $level The level.
	 */
	private function get_level_name( int $level ): string {
		switch ( $level ) {
			case Logger::DEBUG:
				return 'DEBUG';
			case Logger::INFO:
				return 'INFO';
			case Logger::WARNING:
				return 'WARNING';
			case Logger::ERROR:
				return 'ERROR';
			default:
				return '';
		}
	}

	/**
	 * Log message in system.
	 *
	 * @param LogData $data Log data.
	 * @return void
	 */
	public function logMessage( LogData $data ) {
		try {
			if ( $this->log_file->setup() ) {
				$datetime = $this->time_provider->getDateTime( $data->getTimestamp() / 1000 ); // Original timestamp is in milliseconds.
				$ctx_data = '';

				if ( ! empty( $data->getContext() ) ) {
					$ctx = array();
					foreach ( $data->getContext() as $log_context_data ) {
						$arr = $log_context_data->toArray();
						if ( isset( $arr['value'] ) && is_string( $arr['value'] ) ) {
							$arr['value'] = json_decode( $arr['value'], true );
						}
						$ctx[] = $arr;
					}
					$ctx_data .= ' ' . wp_json_encode( $ctx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_LINE_TERMINATORS );
				}

				$formatted_message = sprintf(
					"%s\t%s\t%s\t%s\r\n",// phpcs:ignore
					$this->get_level_name( $data->getLogLevel() ),
					$this->time_provider->serializeDate( $datetime, 'Y-m-d H:i:s' ),
					$data->getMessage(),
					$ctx_data
				);
				
				$this->log_file->append_content( $formatted_message );
			}
		} catch ( Throwable $e ) { // phpcs:ignore
			// Do nothing.
		}
	}
}
