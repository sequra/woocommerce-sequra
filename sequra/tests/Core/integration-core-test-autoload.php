<?php
/**
 * Autoloader for the Integration Core utilities.
 * 
 * @package Sequra\WC\Tests\Core
 */

call_user_func(
	function () {
		$core_dir = __DIR__ . '/../../vendor/sequra/integration-core/';

		$registry = array(
			'SeQura\\Core\\Tests\\Infrastructure\\' => $core_dir . 'tests/Infrastructure/',
			'SeQura\\Core\\Tests\\BusinessLogic\\'  => $core_dir . 'tests/BusinessLogic/',
		);

		spl_autoload_register(
			function ( $class_name ) use ( $registry ) {
				foreach ( $registry as $prefix => $base_dir ) {
					$len = strlen( $prefix );
					if ( strncmp( $prefix, $class_name, $len ) !== 0 ) {
						continue;
					}

					$relative_class = substr( $class_name, $len );
					$file_name      = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

					if ( file_exists( $file_name ) ) {
                        require $file_name; // phpcs:ignore
					}
				}
			}
		);
	}
);
