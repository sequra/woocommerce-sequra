<?php // phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase, WordPress.Files.FileName.NotHyphenatedLowercase, WordPress.Files.FileName.NotHyphenatedLowercase, Squiz.Commenting.FileComment.Missing, WordPress.Files.FileName.NotHyphenatedLowercase, Squiz.PHP.CommentedOutCode.Found, Squiz.Commenting.InlineComment.InvalidEndChar
declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

// You can do your own things here, e.g. collecting symbols to expose dynamically
// or files to exclude.
// However beware that this file is executed by PHP-Scoper, hence if you are using
// the PHAR it will be loaded by the PHAR. So it is highly recommended to avoid
// to auto-load any code here: it can result in a conflict or even corrupt
// the PHP-Scoper analysis.

// Example of collecting files to include in the scoped build but to not scope
// leveraging the isolated finder.
$excludedFiles = array_map(
	static function ( SplFileInfo $fileInfo ) {
		return $fileInfo->getPathName(); 
	},
	iterator_to_array(
		Finder::create()->files()->in( __DIR__ . '/vendor/sequra/php-client' ),
		false
	)
);

return array(
	// The prefix configuration. If a non-null value is used, a random prefix
	// will be generated instead.
	//
	// For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#prefix.
	'prefix'                  => 'Sequra\WC',

	// The base output directory for the prefixed files.
	// This will be overridden by the 'output-dir' command line option if present.
	'output-dir'              => 'build',

	// By default when running php-scoper add-prefix, it will prefix all relevant code found in the current working
	// directory. You can however define which files should be scoped by defining a collection of Finders in the
	// following configuration key.
	//
	// This configuration entry is completely ignored when using Box.
	//
	// For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#finders-and-paths.
	'finders'                 => array(
		Finder::create()
		->files()
		->ignoreVCS( true )
		->notName(
			array(
				'scoper.inc.php',
				'LICENCE',
				// '*.js',
				// '*.css',
				'*.yml',
				'*.xml',
				'*.cache',
				'*.log',
				// '*.txt',
				'*.md',
				'*.dist',
				'*.zip',
				'*.json',
				// '*.po',
				// '*.pot',
				// '*.mo',
				'*.lock',
				'*.lst',
			)
		)
		->exclude(
			array(
				'assets',
				'bin',
				'i18n',
				'templates',
				'tests',
				'vendor-bin',
				'build',
				'*.txt',
			)
		)
		->in( __DIR__ ),
	),

	// List of excluded files, i.e. files for which the content will be left untouched.
	// Paths are relative to the configuration file unless if they are already absolute
	//
	// For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#patchers
	'exclude-files'           => $excludedFiles,

	// When scoping PHP files, there will be scenarios where some of the code being scoped indirectly references the
	// original namespace. These will include, for example, strings or string manipulations. PHP-Scoper has limited
	// support for prefixing such strings. To circumvent that, you can define patchers to manipulate the file to your
	// heart contents.
	//
	// For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#patchers
	'patchers'                => array(),

	// List of symbols to consider internal i.e. to leave untouched.
	//
	// For more information see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#excluded-symbols
	'exclude-namespaces'      => array(
		'~^$~',                        // The root namespace only
		'Sequra\WC',
		'Sequra\PhpClient',
		'Automattic\WooCommerce',
		// 'Acme\Foo'                     // The Acme\Foo namespace (and sub-namespaces)
		// '~^PHPUnit\\\\Framework$~',    // The whole namespace PHPUnit\Framework (but not sub-namespaces)
		// '',                            // Any namespace
	),
	'exclude-classes'         => array(
		// 'ReflectionClassConstant',
	),
	'exclude-functions'       => array(
		// 'mb_str_split',
		'define',
		'add_filter',
		'add_action',
		'do_action',
		'apply_filters',
		'register_activation_hook',
		'class_exists',
	),
	'exclude-constants'       => array(
		// 'STDIN',
		// 'ABSPATH',
		// 'WPINC',
		// 'WP_PLUGIN_DIR',
		// 'WP_PLUGIN_URL',
		// 'WP_CONTENT_DIR',
		// 'WP_CONTENT_URL',
		// 'DOING_AJAX',
		// 'DOING_CRON',
		// 'AUTH_COOKIE',
		// 'WP_DEBUG',
	),

	// List of symbols to expose.
	//
	// For more information see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#exposed-symbols
	'expose-global-constants' => true,
	'expose-global-classes'   => true,
	'expose-global-functions' => true,
	'expose-namespaces'       => array(
		// 'Acme\Foo'                     // The Acme\Foo namespace (and sub-namespaces)
		// '~^PHPUnit\\\\Framework$~',    // The whole namespace PHPUnit\Framework (but not sub-namespaces)
		// '~^$~',                        // The root namespace only
		// '',                            // Any namespace
	),
	'expose-classes'          => array(),
	'expose-functions'        => array(),
	'expose-constants'        => array(),
);
