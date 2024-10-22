<?php
/**
 * The plugin bootstrap file
 *
 * @package SeQura/WC/VariationsEndDate
 *
 * @wordpress-plugin
 * Plugin Name:       seQura - Variations end date Addon
 * Plugin URI:        https://sequra.es/
 * Description:       Allows to set an end date for each variation in a product.
 * Version:           3.0.0-rc.3
 * Author:            "seQura Tech" <wordpress@sequra.com>
 * Author URI:        https://sequra.com/
 * License:           GPL-3.0+
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       sequra
 * Domain Path:       /languages
 * Requires PHP:      7.3
 * Requires at least: 5.9
 * Tested up to:      6.6.2
 * seQura requires at least: 3.0.0-rc.3
 * Requires Plugins:  woocommerce
 */

defined( 'WPINC' ) || die;

require plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

call_user_func(
	function () {   
		$plugin = new \SeQura\WC\VariationsEndDate\Plugin();
		register_deactivation_hook( __FILE__, array( $plugin, 'deactivate' ) );
	}
);
