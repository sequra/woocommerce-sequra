<?php
/*
  Plugin Name: Pasarela de pago para SeQura
  Plugin URI: http://sequra.es/
  Description: Da la opción a tus clientes de recibir y luego pagar.
  Version: 1.0.2
  Author: Mikel Martin
  Author URI: http://SeQura.es/
 */
define('SEQURA_ID', 'sequra');

register_activation_hook(__FILE__, 'sequra_activation');
/**
 * Run once on plugin activation
 */
function sequra_activation()
{
	// Place in first place
	$gateway_order = (array)get_option('woocommerce_gateway_order');
	$order = array(SEQURA_ID => 0);
	if (is_array($gateway_order) && sizeof($gateway_order) > 0) {
		$loop = 1;
		foreach ($gateway_order as $gateway_id) {
			$order[esc_attr($gateway_id)] = $loop;
			$loop++;
		}
	}
	update_option('woocommerce_gateway_order', $order);
	update_option('woocommerce_default_gateway', SEQURA_ID);
	// Schedule a daily event for sending delivery report on plugin activation
	$random_offset = rand(0, 25200); //60*60*7 seconds from 2AM to 8AM
	$tomorrow = date("Y-m-d 02:00", strtotime('tomorrow'));
	$time = $random_offset + strtotime($tomorrow);
	add_option('woocommerce-sequra-deliveryreport-time', $time);
	wp_schedule_event($time, 'daily', 'sequra_send_daily_delivery_report');
	//Set version as an option get_plugin_data function is not availiable at cron
	do_action('sequra_upgrade_if_needed');
}

add_action('sequra_upgrade_if_needed', 'sequra_upgrade_if_needed');
function sequra_upgrade_if_needed()
{
	if (!function_exists('get_plugin_data'))
		return;
	$current = get_option('sequra_version');
	$plugin_data = get_plugin_data(dirname(__FILE__) . '/gateway-sequra.php');
	if (version_compare($current, $plugin_data['Version'], '<')) {
		do_action('sequra_upgrade', array('from' => $current, 'to' => $plugin_data['Version']));
		update_option('sequra_version', (string)$plugin_data['Version']);
	}
}

add_action('sequra_send_daily_delivery_report', 'sequra_send_daily_delivery_report');
function sequra_send_daily_delivery_report()
{
	if (!class_exists('SequraReporter'))
		require_once(WP_PLUGIN_DIR . "/" . dirname(plugin_basename(__FILE__)) . '/SequraReporter.php');
	SequraReporter::sendDailyDeliveryReport();
}

register_deactivation_hook(__FILE__, 'sequra_deactivation');
/**
 * Run once on plugin deactivation
 */
function sequra_deactivation()
{
	// Remove daily schedule
	wp_unschedule_event(get_option('woocommerce-sequra-deliveryreport-time'), 'sequra_send_daily_delivery_report');
}

add_action('woocommerce_loaded', 'woocommerce_sequra_init', 100);

function woocommerce_sequra_init()
{
	do_action('sequra_upgrade_if_needed');
	load_plugin_textdomain('wc_sequra', false, dirname(plugin_basename(__FILE__)) . '/languages');

	if (!class_exists('SequraHelper'))
		require_once(WP_PLUGIN_DIR . "/" . dirname(plugin_basename(__FILE__)) . '/SequraHelper.php');

	if (!class_exists('SequraPaymentGateway'))
		require_once(WP_PLUGIN_DIR . "/" . dirname(plugin_basename(__FILE__)) . '/SequraPaymentGateway.php');

	/**
	 * Add the gateway to woocommerce
	 * */
	function add_sequra_gateway($methods)
	{
		$methods[] = 'SequraPaymentGateway';
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

	function sequra_calculate_order_totals($cart)
	{
		$sequra = new SequraPaymentGateway();
		$sequra->calculate_order_totals($cart);
	}

	add_action('woocommerce_cart_calculate_fees', 'sequra_calculate_order_totals');
}