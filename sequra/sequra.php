<?php
/**
 * The plugin bootstrap file
 *
 * @package SeQura/WC
 *
 * @wordpress-plugin
 * Plugin Name:       seQura
 * Plugin URI:        https://sequra.es/
 * Description:       seQura payment gateway for WooCommerce
 * Version:           3.0.2
 * Author:            "seQura Tech" <wordpress@sequra.com>
 * Author URI:        https://sequra.com/
 * License:           GPL-3.0+
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       sequra
 * Domain Path:       /languages
 * Requires PHP:      7.3
 * Requires at least: 5.9
 * Tested up to:      6.6.2
 * WC requires at least: 4.7.0
 * WC tested up to: 9.3.3
 * Requires Plugins:  woocommerce
 */

defined( 'WPINC' ) || die;

require plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

call_user_func(
	function () {
		\SeQura\WC\Bootstrap::init();
		/**
		 * The instance of the plugin.
		 *
		 * @var \SeQura\WC\Plugin $plugin
		 */
		$plugin = \SeQura\Core\Infrastructure\ServiceRegister::getService( \SeQura\WC\Plugin::class );

		register_activation_hook( __FILE__, array( $plugin, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $plugin, 'deactivate' ) );
	}
);
