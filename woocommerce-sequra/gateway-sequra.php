<?php
/*
  Plugin Name: Pasarela de pago para SeQura
  Plugin URI: http://sequra.es/
  Description: Da la opción a tus clientes usar los servicios de SeQura para pagar.
  Version: 4.4.3
  Author: SeQura Engineering
  Author URI: http://SeQura.es/
 */
define( 'SEQURA_VERSION', '4.4.3' );

register_activation_hook( __FILE__, 'sequra_activation' );
/**
 * Run once on plugin activation
 */
function sequra_activation() {
	// Place in first place
	$gateway_order = (array) get_option( 'woocommerce_gateway_order' );
	$order         = array(
	        'sequra_i' => 0,
	        'sequra_pp' => 1,
	        'sequra' => 2
	);
	if ( is_array( $gateway_order ) && sizeof( $gateway_order ) > 0 ) {
		$loop = 3;
		foreach ( $gateway_order as $gateway_id ) {
			$order[ esc_attr( $gateway_id ) ] = $loop;
			$loop ++;
		}
	}
	update_option( 'woocommerce_gateway_order', $order );
	update_option( 'woocommerce_default_gateway', 'sequra_i' );
	// Schedule a daily event for sending delivery report on plugin activation
	$random_offset = rand( 0, 25200 ); //60*60*7 seconds from 2AM to 8AM
	$tomorrow      = date( "Y-m-d 02:00", strtotime( 'tomorrow' ) );
	$time          = $random_offset + strtotime( $tomorrow );
	add_option( 'woocommerce-sequra-deliveryreport-time', $time );
	wp_schedule_event( $time, 'daily', 'sequra_send_daily_delivery_report' );
	//Set version as an option get_plugin_data function is not availiable at cron
	do_action( 'sequra_upgrade_if_needed' );
}

add_action( 'sequra_upgrade_if_needed', 'sequra_upgrade_if_needed' );
function sequra_upgrade_if_needed() {
	$current = get_option( 'sequra_version' );
	if ( version_compare( $current, SEQURA_VERSION, '<' ) ) {
		foreach ( glob( dirname( __FILE__ ) . '/upgrades/*.php' ) as $filename ) {
			include $filename;
		}
		do_action( 'sequra_upgrade', array( 'from' => $current, 'to' => SEQURA_VERSION ) );
		update_option( 'sequra_version', (string) SEQURA_VERSION );
	}
}

add_action( 'sequra_send_daily_delivery_report', 'sequra_send_daily_delivery_report' );
function sequra_send_daily_delivery_report() {
	if ( ! class_exists( 'SequraReporter' ) ) {
		require_once( WP_PLUGIN_DIR . "/" . dirname( plugin_basename( __FILE__ ) ) . '/SequraReporter.php' );
	}
	SequraReporter::sendDailyDeliveryReport();
}

add_action( 'init', 'sequra_triggerreport_check' );
function sequra_triggerreport_check() {
	if ( isset( $_GET['sequra_triggerreport'] ) && $_GET['sequra_triggerreport'] == 'true' ) {
		do_action( 'sequra_send_daily_delivery_report' );
	}

	return;
}

register_deactivation_hook( __FILE__, 'sequra_deactivation' );
/**
 * Run once on plugin deactivation
 */
function sequra_deactivation() {
	// Remove daily schedule
	$timestamp = wp_next_scheduled( 'sequra_send_daily_delivery_report' );
	wp_unschedule_event( $timestamp, 'sequra_send_daily_delivery_report' );
}

add_action( 'woocommerce_loaded', 'woocommerce_sequra_init', 100 );

// [sequra_banner product='i1'] [sequra_banner product='pp3']
function sequra_banner( $atts ) {
	wp_enqueue_style( 'sequra-banner' );	
	$product = $atts['product'];
	$pm      = null;
	if ( $product == 'i1' ) {
		$pm = new SequraInvoicePaymentGateway();
	} elseif ( $product == 'pp3' ) {		
		$pm = new SequraPartPaymentGateway();
		wp_enqueue_script( 'sequra-pp-cost-js', $pm->pp_cost_url );
	}
	$pm->is_available();
	if ( ! $pm || ! $pm->is_available() ) {
		return;
	}
	ob_start();
	include( SequraHelper::template_loader( 'banner_' . $product ) );

	return ob_get_clean();
}

add_shortcode( 'sequra_banner', 'sequra_banner' );

function woocommerce_sequra_init() {
	do_action( 'sequra_upgrade_if_needed' );
	load_plugin_textdomain( 'wc_sequra', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	if ( ! class_exists( 'SequraHelper' ) ) {
		require_once( WP_PLUGIN_DIR . "/" . dirname( plugin_basename( __FILE__ ) ) . '/SequraHelper.php' );
	}

	if ( ! class_exists('SequraPaymentGateway') ) {
		require_once( WP_PLUGIN_DIR . "/" . dirname( plugin_basename( __FILE__ ) ) . '/SequraPaymentGateway.php' );
	}

	if ( ! class_exists('SequraInvoicePaymentGateway') ) {
		require_once( WP_PLUGIN_DIR . "/" . dirname( plugin_basename( __FILE__ ) ) . '/SequraInvoicePaymentGateway.php' );
	}

	if ( ! class_exists( 'SequraPartPaymentGateway' ) ) {
		require_once( WP_PLUGIN_DIR . "/" . dirname( plugin_basename( __FILE__ ) ) . '/SequraPartPaymentGateway.php' );
	}


	/**
	 * Add the gateway to woocommerce
	 * */
	function add_sequra_gateway( $methods ) {
		$methods[] = 'SequraPaymentGateway';
		$methods[] = 'SequraPartPaymentGateway';
		return $methods;
	}
	add_filter( 'woocommerce_payment_gateways', 'add_sequra_gateway' );

	$coresettings = get_option( 'woocommerce_sequra_settings', array() );
	if(!isset($coresettings['enable_for_virtual']) || $coresettings['enable_for_virtual'] == 'no'){
		/**
		 * Add the invoice gateway to woocommerce
		 * */
		function add_sequra_invoice_gateway( $methods ) {
			$methods[] = 'SequraInvoicePaymentGateway';
			return $methods;
		}
		add_filter( 'woocommerce_payment_gateways', 'add_sequra_invoice_gateway' );
	} else {
		if ( ! class_exists( 'Sequra_Meta_Box_Service_End_Date' ) ) {
			require_once( WP_PLUGIN_DIR . "/" . dirname( plugin_basename( __FILE__ ) ) . '/includes/admin/meta-boxes/Sequra_Meta_Box_Service_End_Date.php' );
		}
		add_action( 'woocommerce_process_product_meta', 'Sequra_Meta_Box_Service_End_Date::save', 20, 2 );
		add_action('add_meta_boxes','Sequra_Meta_Box_Service_End_Date::add_meta_box');
    }

	/**
	 * Enqueue plugin style-file
	 */
	function sequra_add_stylesheet_adn_js() {
		/*@TODO: Load only if necessary */
		// Respects SSL, Style.css is relative to the current file
		wp_register_style( 'sequra-style', plugins_url( 'assets/css/sequrapayment.css', __FILE__ ),SEQURA_VERSION );
		wp_enqueue_style( 'sequra-style' );
		wp_register_style( 'sequra-custom-style', plugins_url( 'assets/css/wordpress.css', __FILE__ ),SEQURA_VERSION );
		wp_enqueue_style( 'sequra-custom-style' );
		wp_enqueue_script( 'sequra-js', plugins_url( 'assets/js/sequrapayment.js', __FILE__ ),SEQURA_VERSION );
		wp_register_style( 'sequra-banner', plugins_url( 'assets/css/banner.css', __FILE__ ),SEQURA_VERSION );
		if ( is_page( wc_get_page_id( 'checkout' ) ) ) {
			$pm = WC_Payment_Gateways::instance()->payment_gateways()['sequra_pp'];
			wp_enqueue_script( 'sequra-pp-cost-js', $pm->pp_cost_url,SEQURA_VERSION );
		}
	}

	add_action( 'wp_enqueue_scripts', 'sequra_add_stylesheet_adn_js' );

	function sequra_add_cart_info_to_session() {
		$sequra_cart_info = WC()->session->get( 'sequra_cart_info' );
		if ( ! $sequra_cart_info ) {
			$sequra_cart_info = array(
				'ref'        => uniqid(),
				'created_at' => date( 'c' )
			);
			WC()->session->set( 'sequra_cart_info', $sequra_cart_info );
		}
	}

	add_action( 'woocommerce_add_to_cart', 'sequra_add_cart_info_to_session' );

	function sequra_calculate_order_totals( $cart ) {
		$sequra = new SequraInvoicePaymentGateway();
		$sequra->calculate_order_totals( $cart );
	}

	add_action( 'woocommerce_cart_calculate_fees', 'sequra_calculate_order_totals' );

	/*
	 * Add instalment simulator in product page
	 */

	add_filter( 'woocommerce_get_price_html', 'woocommerce_sequra_add_simulator_to_product_page', 10, 2 );
	function woocommerce_sequra_add_simulator_to_product_page( $price, $product ) {
		global $wp_query,$sequra_simulator_added;
		$sequra_pp = new SequraPartPaymentGateway();
		if ( 
			! is_product() ||
			! $sequra_pp->is_available() ||
			$wp_query->posts[0]->ID != $product->id || 
			$sequra_simulator_added) {
			return $price;
		}
		echo sequra_pp_simulator( array(
			'price' => trim($sequra_pp->price_css_sel),
			'dest'  => trim($sequra_pp->dest_css_sel)
		) );
		$sequra_simulator_added = true;
		return $price;
	}

	//[sequra_pp_simulator price='#product_price']
	function sequra_pp_simulator( $atts ) {
		$sequra_pp = new SequraPartPaymentGateway();
		if ( ! $sequra_pp->is_available() ) {
			return;
		}
		wp_enqueue_script( 'sequra-pp-cost-js', $sequra_pp->pp_cost_url );
		$price_container = isset( $atts['price'] ) ? $atts['price'] : '#product_price';
		ob_start();
		include( SequraHelper::template_loader( 'partpayment_teaser') );
		return ob_get_clean();
	}

	add_shortcode( 'sequra_pp_simulator', 'sequra_pp_simulator' );

	/*
	 * Invoice teaser in product page
	 */
	add_action( 'woocommerce_after_add_to_cart_button', 'woocommerce_sequra_after_add_to_cart_button', 10 );
	function woocommerce_sequra_after_add_to_cart_button() {
		global $product;
		$sequra = new SequraInvoicePaymentGateway();
		include( SequraHelper::template_loader( 'invoice_teaser') );
	}
}