<?php
/**
 * SeQura Helper plugin
 *
 * @package SeQura/WC
 *
 * @wordpress-plugin
 * Plugin Name:       seQura Helper
 * Plugin URI:        https://sequra.es/
 * Description:       Provides helper functions for seQura plugin development and testing
 * Version:           1.0.0
 * Author:            "seQura Tech" <wordpress@sequra.com>
 * Author URI:        https://sequra.com/
 * License:           GPL-3.0+
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       sequra-helper
 * Domain Path:       /languages
 * Requires PHP:      7.3
 * Requires at least: 5.9
 * Tested up to:      6.5.2
 * WC requires at least: 4.7.0
 * WC tested up to: 8.2.2
 * Requires Plugins:  woocommerce
 */

defined( 'WPINC' ) || die;

require_once __DIR__ . '/src/Task/class-task.php';
require_once __DIR__ . '/src/Task/class-configure-dummy-service-task.php';
require_once __DIR__ . '/src/Task/class-configure-dummy-task.php';
require_once __DIR__ . '/src/Task/class-clear-configuration-task.php';
require_once __DIR__ . '/src/Task/class-force-order-failure-task.php';
require_once __DIR__ . '/src/Task/class-remove-log-task.php';
require_once __DIR__ . '/src/class-plugin.php';
new SeQura\Helper\Plugin();
