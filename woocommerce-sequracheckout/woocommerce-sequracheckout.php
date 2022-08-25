<?php
/**
 * Plugin Name: Checkout con SeQura
 * Plugin URI: http://sequra.es/
 * Description: Ofrece las opciones de pago de SeQura
 * Version: 1.1.13
 * Author: SeQura Engineering
 * Author URI: http://SeQura es/
 * WC requires at least: 4.0
 * WC tested up to: 6.8.0
 * Icon1x: https://live.sequracdn.com/assets/images/badges/invoicing.svg
 * Icon2x: https://live.sequracdn.com/assets/images/badges/invoicing_l.svg
 * BannerHigh: https://live.sequracdn.com/assets/images/logos/logo.svg
 * BannerLow: https://live.sequracdn.com/assets/images/logos/logo.svg
 * Text Domain: wc_sequra
 * Domain Path: /i18n/languages/
 *
 * @package woocommerce-sequra
 */

// Make sure old WooCommerce SeQura is not installed.
if ( ! defined( 'WC_SEQURA_PLG_PATH' ) && ! file_exists( WP_PLUGIN_DIR . '/woocommerce-sequra' ) ) {
	define( 'SEQURACHECKOUT_VERSION', '1.1.13' );
	define( 'WC_SEQURA_PLG_PATH', WP_PLUGIN_DIR . '/' . basename( plugin_dir_path( __FILE__ ) ) . '/' );
	define( 'SEQURA_PLUGIN_UPDATE_SERVER', 'https://engineering.sequra.es' );
	require_once WC_SEQURA_PLG_PATH . 'lib/wp-package-updater/class-wp-package-updater.php';

	$prefix_updater = new WP_Package_Updater(
		get_option( 'sequra_plugin_update_server', SEQURA_PLUGIN_UPDATE_SERVER ),
		wp_normalize_path( __FILE__ ),
		wp_normalize_path( WC_SEQURA_PLG_PATH )
	);
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'sequrapayment_action_links' );
	register_activation_hook( __FILE__, 'sequra_activation' );
	require_once WC_SEQURA_PLG_PATH . 'gateway-sequra.php';
} else {
	add_action( 'admin_notices',
		function() {
			echo '<div id="message" class="error"><p>' . __('Por favor, desinstale y elimine primero el plugin "Pasarela de pago para Sequra" para poder usar el nuevo "Checkout con SeQura"', 'wc_sequra' ) . '</p></div>';
		}
	);
}
