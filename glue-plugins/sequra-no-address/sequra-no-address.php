<?php
/**
 * The plugin bootstrap file
 *
 * @package SeQura/WC
 *
 * @wordpress-plugin
 * Plugin Name:       seQura - No Address Addon
 * Plugin URI:        https://sequra.es/
 * Description:       Allow customers to pay with seQura without providing an address.
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
 * seQura requires at least: 3.0.0-rc.3
 * Requires Plugins:  woocommerce
 */

defined( 'WPINC' ) || die;

require plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

new \SeQura\WC\NoAddress\Plugin( __FILE__ );
