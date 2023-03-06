<?php
/**
 * Plugin Name: Checkout con SeQura
 * Plugin URI: http://sequra.es/
 * Description: Ofrece las opciones de pago de SeQura
 * Version: 2.0.0
 * Author: SeQura Engineering
 * Author URI: http://SeQura es/
 * WC requires at least: 4.0
 * WC tested up to: 7.4.1
 * Icon1x: https://live.sequracdn.com/assets/images/badges/invoicing.svg
 * Icon2x: https://live.sequracdn.com/assets/images/badges/invoicing_l.svg
 * BannerHigh: https://live.sequracdn.com/assets/images/logos/logo.svg
 * BannerLow: https://live.sequracdn.com/assets/images/logos/logo.svg
 * Text Domain: wc_sequra
 * Domain Path: /i18n/languages/
 *
 * @package woocommerce-sequraacheckout
 */

// Make sure old WooCommerce SeQura is not installed.
if ( ! defined( 'WC_SEQURA_PLG_PATH' ) && ! file_exists( WP_PLUGIN_DIR . '/woocommerce-sequra' ) ) {
	define( 'SEQURACHECKOUT_VERSION', '2.0.0' );
	define( 'WC_SEQURA_PLG_PATH', WP_PLUGIN_DIR . '/' . basename( plugin_dir_path( __FILE__ ) ) . '/' );
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
