<?php
/*
  Plugin Name: Pasarela de pago para SeQura
  Plugin URI: http://sequra.es/
  Description: Da la opción a tus clientes de recibir y luego pagar.
  Version: 0.1
  Author: Mikel Martin
  Author URI: http://SeQura.es/
 */

add_action('woocommerce_loaded', 'woocommerce_sequra_init', 100);

function woocommerce_sequra_init()
{
	load_plugin_textdomain('wc_sequra', false, dirname(plugin_basename(__FILE__)) . '/languages');

	if (!class_exists('SequraHelper'))
		require_once(WP_PLUGIN_DIR . "/" . dirname(plugin_basename(__FILE__)) . '/SequraHelper.php');

	if (!class_exists('woocommerce_sequra'))
		require_once(WP_PLUGIN_DIR . "/" . dirname(plugin_basename(__FILE__)) . '/SequraPaymentGateway.php');

	/**
	 * Add the gateway to woocommerce
	 * */
	function add_sequra_gateway($methods)
	{
		$methods[] = 'woocommerce_sequra';
		return $methods;
	}

	add_filter('woocommerce_payment_gateways', 'add_sequra_gateway');

	/**
	 * Enqueue plugin style-file
	 */
	function sequra_add_stylesheet()
	{
		// Respects SSL, Style.css is relative to the current file
		wp_register_style('sequra-style', plugins_url('assets/css/style.css', __FILE__));
		wp_enqueue_style('sequra-style');
	}

	add_action('wp_enqueue_scripts', 'sequra_add_stylesheet');

	function sequra_add_cart_info_to_session()
	{
		$sequra_cart_info = WC()->session->get('sequra_cart_info');
		if ($sequra_cart_info)
			return $sequra_cart_info;
		$sequra_cart_info = array(
			'ref' => uniqid(),
			'created_at' => date('c')
		);
		WC()->session->set('sequra_cart_info', $sequra_cart_info);
	}

	add_action('woocommerce_add_to_cart', 'sequra_add_cart_info_to_session');
}