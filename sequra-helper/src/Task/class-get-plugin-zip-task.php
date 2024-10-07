<?php
/**
 * Task class
 * 
 * @package SeQura/Helper
 */

namespace SeQura\Helper\Task;

// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.Security.EscapeOutput.ExceptionNotEscaped, WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec

/**
 * Task class
 */
class Get_Plugin_Zip_Task extends Task {

	/**
	 * Make a zip file with the content of the current sequra plugin
	 * 
	 * @throws \Exception If the task fails
	 */
	public function execute( array $args = array() ): void {
		exec(
			'cd /var/www/html/wp-content/plugins &&\
			rm -rf sequra-helper/zip/sequra &&\
			mkdir -p sequra-helper/zip/sequra &&\
			cp -R _sequra/assets sequra-helper/zip/sequra &&\
			cp -R _sequra/languages sequra-helper/zip/sequra &&\
			cp -R _sequra/src sequra-helper/zip/sequra &&\
			cp -R _sequra/templates sequra-helper/zip/sequra &&\
			cp -R _sequra/vendor sequra-helper/zip/sequra &&\
			cp _sequra/composer.json sequra-helper/zip/sequra &&\
			cp _sequra/LICENSE.txt sequra-helper/zip/sequra &&\
			cp _sequra/readme.txt sequra-helper/zip/sequra &&\
			cp _sequra/sequra.php sequra-helper/zip/sequra &&\
			rm -rf sequra-helper/zip/sequra/assets/css/scss &&\
			rm -rf sequra-helper/zip/sequra/assets/js/src &&\
			cd sequra-helper/zip &&\
			zip -r9 sequra.zip sequra &&\
			rm -rf sequra',
			$output,
			$result_code 
		);

		if ( 0 !== $result_code ) {
			throw new \Exception( print_r( $output, true ), $result_code ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		}
	}

	/**
	 * Send the zip file as a response
	 */
	public function http_success_response(): void {
		$filepath = '/var/www/html/wp-content/plugins/sequra-helper/zip/sequra.zip';
		$filename = basename( $filepath );
		header( 'Content-Type: application/zip' );
		header( "Content-Disposition: attachment; filename=$filename" );
		header( 'Content-Length: ' . filesize( $filepath ) );
		readfile( $filepath ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		exit;
	}
}
