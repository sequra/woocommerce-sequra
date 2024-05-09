<?php
/**
 * Assets Controller
 *
 * @package    Sequra/WC
 * @subpackage Sequra/WC/Controllers
 */

namespace Sequra\WC\Controllers;

/**
 * Define the assets related functionality
 */
class Assets_Controller implements Interface_Assets_Controller {

	private const HANDLE_ADMIN = 'sequra-admin';

	/**
	 * URL to the assets directory
	 *
	 * @var string
	 */
	private $assets_dir_url;

	/**
	 * Version of the assets
	 *
	 * @var string
	 */
	private $assets_version;

	/**
	 * Constructor
	 *
	 * @param string $assets_dir_url URL to the assets directory.
	 * @param string $assets_version Version of the assets.
	 */
	public function __construct( $assets_dir_url, $assets_version ) {
		$this->assets_dir_url = $assets_dir_url;
		$this->assets_version = $assets_version;
	}

	/**
	 * Enqueue styles and scripts in WP-Admin
	 */
	public function enqueue_admin() {
		// // Styles.
		// wp_enqueue_style( self::HANDLE_ADMIN, "$this->assets_dir_url/css/admin.css", array(), $this->assets_version );
		
		// // Scripts.
		// wp_register_script( self::HANDLE_ADMIN, "$this->assets_dir_url/js/admin.js", array(), $this->assets_version, true );
		// $l10n = array(
		// 	// TODO: Add localization strings here.
		// );
		// wp_localize_script( self::HANDLE_ADMIN, 'demoAdmin', $l10n );
		// wp_enqueue_script( self::HANDLE_ADMIN );
	}

	/**
	 * Enqueue styles and scripts in Front-End
	 */
	public function enqueue_front() {
	}
}
