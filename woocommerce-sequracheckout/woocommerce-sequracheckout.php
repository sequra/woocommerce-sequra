<?php
/**
 * Plugin Name: SeQura on your checkout
 * Plugin URI: https://sequra.es/
 * Description: Ofrece las opciones de pago con SeQura
 * Version: 2.0.0
 * Author: "SeQura Tech" <dev+wordpress@sequra.es>
 * Author URI: https://engineering.sequra.es/
 * WC requires at least: 4.0
 * WC tested up to: 7.4.1
 * Text Domain: wc_sequra
 * Domain Path: /i18n/languages/
 * Requires at least: 5.9
 * Requires PHP: 7.2
 *
 * Copyright (C) 2023 SeQura Tech
 * 
 * License: GPL v3
 * 
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 * 
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 * 
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>
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
