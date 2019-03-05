<?php
/**
 * Plugin Name: Pasarela de pago para Sequra
 * Plugin URI: http://sequra.es/
 * Description: Da la opción a tus clientes usar los servicios de SeQura para pagar.
 * Version: 4.8.3
 * Author: SeQura Engineering
 * Author URI: http://Sequra.es/
 * WC tested up to: 3.5.5
 * Icon1x: https://live.sequracdn.com/assets/images/badges/invoicing.svg
 * Icon2x: https://live.sequracdn.com/assets/images/badges/invoicing_l.svg
 * BannerHigh: https://live.sequracdn.com/assets/images/logos/logo.svg
 * BannerLow: https://live.sequracdn.com/assets/images/logos/logo.svg
 *
 * @package woocommerce-sequra
 */

define( 'SEQURA_VERSION', '4.8.3' );

register_activation_hook( __FILE__, 'sequra_activation' );

require_once plugin_dir_path( __FILE__ ) . 'lib/wp-package-updater/class-wp-package-updater.php';

$prefix_updater = new WP_Package_Updater(
	'https://engineering.sequra.es',
	wp_normalize_path( __FILE__ ),
	wp_normalize_path( plugin_dir_path( __FILE__ ) )
);

/**
 * Run once on plugin activation
 */
function sequra_activation() {
	// Place in first place.
	$gateway_order = (array) get_option( 'woocommerce_gateway_order' );
	$order         = array(
		'sequra_i'  => 0,
		'sequra_pp' => 1,
		'sequra'    => 2,
	);
	if ( is_array( $gateway_order ) && count( $gateway_order ) > 0 ) {
		$loop = 3;
		foreach ( $gateway_order as $gateway_id ) {
			$order[ esc_attr( $gateway_id ) ] = $loop;
			$loop++;
		}
	}
	update_option( 'woocommerce_gateway_order', $order );
	update_option( 'woocommerce_default_gateway', 'sequra_i' );
	// Schedule a daily event for sending delivery report on plugin activation.
	$random_offset = rand( 0, 25200 ); // 60*60*7 seconds from 2AM to 8AM.
	$tomorrow      = date( 'Y-m-d 02:00', strtotime( 'tomorrow' ) );
	$time          = $random_offset + strtotime( $tomorrow );
	add_option( 'woocommerce-sequra-deliveryreport-time', $time );
	wp_schedule_event( $time, 'daily', 'sequra_send_daily_delivery_report' );
	// Set version as an option get_plugin_data function is not availiable at cron.
	do_action( 'sequra_upgrade_if_needed' );
}

add_action( 'sequra_upgrade_if_needed', 'sequra_upgrade_if_needed' );

/**
 * Check if it needs aupgrade
 *
 * @return void
 */
function sequra_upgrade_if_needed() {
	$current = get_option( 'sequra_version' );
	if ( version_compare( $current, SEQURA_VERSION, '<' ) ) {
		foreach ( glob( dirname( __FILE__ ) . '/upgrades/*.php' ) as $filename ) {
			include $filename;
		}
		do_action(
			'sequra_upgrade',
			array(
				'from' => $current,
				'to'   => SEQURA_VERSION,
			)
		);
		update_option( 'sequra_version', (string) SEQURA_VERSION );
	}
}

add_action( 'sequra_send_daily_delivery_report', 'sequra_send_daily_delivery_report' );
/**
 * Send delivery report
 *
 * @return void
 */
function sequra_send_daily_delivery_report() {
	if ( ! class_exists( 'SequraReporter' ) ) {
		require_once WP_PLUGIN_DIR . '/' . dirname( plugin_basename( __FILE__ ) ) . '/class-sequrareporter.php';
	}
	if ( SequraReporter::send_daily_delivery_report() === false ) {
		die( 'KO' );
	}
	http_response_code( 599 );
	die( 'OK' );
}

add_action( 'init', 'sequra_triggerreport_check' );
/**
 * Undocumented function
 *
 * @return void
 */
function sequra_triggerreport_check() {
	if ( isset( $_GET['sequra_triggerreport'] ) && 'true' === $_GET['sequra_triggerreport'] ) {
		do_action( 'sequra_send_daily_delivery_report' );
	}
}

register_deactivation_hook( __FILE__, 'sequra_deactivation' );
/**
 * Run once on plugin deactivation
 */
function sequra_deactivation() {
	// Remove daily schedule.
	$timestamp = wp_next_scheduled( 'sequra_send_daily_delivery_report' );
	wp_unschedule_event( $timestamp, 'sequra_send_daily_delivery_report' );
}

add_action( 'woocommerce_loaded', 'woocommerce_sequra_init', 100 );

/**
 * SeQura banner short code.
 * usage: [sequra_banner product='i1'] [sequra_banner product='pp3'].
 *
 * @param array $atts short code attribute.
 * @return void
 */
function sequra_banner( $atts ) {
	wp_enqueue_style( 'sequra-banner' );
	$product = $atts['product'];
	$pm      = null;
	if ( 'i1' === $product ) {
		$pm = new SequraInvoiceGateway();
	} elseif ( 'pp3' === $product ) {
		$pm = new SequraPartPaymentGateway();
		wp_enqueue_script( 'sequra-pp-cost-js', $pm->pp_cost_url, array(), true, true );
	}
	$pm->is_available();
	if ( ! $pm || ! $pm->is_available() ) {
		return;
	}
	ob_start();
	include SequraHelper::template_loader( 'banner-' . $product );

	return ob_get_clean();
}

add_shortcode( 'sequra_banner', 'sequra_banner' );

add_action( 'sequra_upgrade_if_needed', 'sequrapartpayment_upgrade_if_needed' );
/**
 * Check if it needs aupgrade
 *
 * @return void
 */
function sequrapartpayment_upgrade_if_needed() {
	if ( time() > get_option( 'sequrapartpayment_next_update', 0 ) ||
		isset( $_GET['sequra_partpayment_reset_conditions'] ) ) {
		$core_settings = get_option( 'woocommerce_sequra_settings', null );
		if ( is_null( $core_settings ) ) {
			return;
		}
		$cost_url = 'https://' .
						( $core_settings['env'] ? 'sandbox' : 'live' ) .
						'.sequracdn.com/scripts/' .
						$core_settings['merchantref'] . '/' .
						$core_settings['assets_secret'] .
						'/pp3_cost.json';
		$json_get = wp_remote_get( $cost_url );
		update_option( 'sequrapartpayment_conditions', $json_get['body'] );
		do_action( 'sequrapartpayment_updateconditions' );
		update_option( 'sequrapartpayment_next_update', time() + 86400 );
	}
}

/**
 * Init
 *
 * @return mixed
 */
function woocommerce_sequra_init() {
	do_action( 'sequra_upgrade_if_needed' );
	load_plugin_textdomain( 'wc_sequra', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	if ( ! class_exists( 'SequraHelper' ) ) {
		require_once dirname( __FILE__ ) . '/class-sequrahelper.php';
	}

	if ( ! class_exists( 'SequraPaymentGateway' ) ) {
		require_once dirname( __FILE__ ) . '/class-sequrapaymentgateway.php';
	}

	if ( ! class_exists( 'SequraInvoicGateway' ) ) {
		require_once dirname( __FILE__ ) . '/class-sequrainvoicegateway.php';
	}

	if ( ! class_exists( 'SequraPartPaymentGateway' ) ) {
		require_once dirname( __FILE__ ) . '/class-sequrapartpaymentgateway.php';
	}

	/**
	 * Add the gateway to woocommerce
	 *
	 * @param array $methods available methods.
	 * @return array
	 */
	function add_sequra_gateway( $methods ) {
		$methods[] = 'SequraPaymentGateway';
		$methods[] = 'SequraPartPaymentGateway';

		return $methods;
	}

	add_filter( 'woocommerce_payment_gateways', 'add_sequra_gateway' );

	$core_settings = get_option( 'woocommerce_sequra_settings', array() );
	if ( ! isset( $core_settings['enable_for_virtual'] ) || 'no' === $core_settings['enable_for_virtual'] ) {
		/**
		 * Add the invoice gateway to woocommerce
		 *
		 * @param array $methods available methods.
		 * @return array
		 * */
		function add_sequra_invoice_gateway( $methods ) {
			$methods[] = 'SequraInvoiceGateway';

			return $methods;
		}

		add_filter( 'woocommerce_payment_gateways', 'add_sequra_invoice_gateway' );
	} else {
		if ( ! class_exists( 'Sequra_Meta_Box_Service_End_Date' ) ) {
			require_once WP_PLUGIN_DIR . '/' . dirname( plugin_basename( __FILE__ ) ) . '/includes/admin/meta-boxes/class-sequra-meta-box-service-end-date.php';
		}
		add_action( 'woocommerce_process_product_meta', 'Sequra_Meta_Box_Service_End_Date::save', 20, 2 );
		add_action( 'add_meta_boxes', 'Sequra_Meta_Box_Service_End_Date::add_meta_box' );
	}

	/**
	 * Enqueue plugin style-file
	 */
	function sequra_add_stylesheet_cdn_js() {
		// Respects SSL, Style.css is relative to the current file.
		wp_register_style( 'sequra-banner', plugins_url( 'assets/css/banner.css', __FILE__ ), array(), SEQURA_VERSION );
	}

	add_action( 'wp_enqueue_scripts', 'sequra_add_stylesheet_cdn_js' );
	/**
	 * Undocumented function
	 *
	 * @return string
	 */
	function sequra_get_script_basesurl() {
		$core_settings = get_option( 'woocommerce_sequra_settings', SequraHelper::get_empty_core_settings() );

		return 'https://' . ( 1 === (int) $core_settings['env'] ? 'sandbox' : 'live' ) . '.sequracdn.com/assets/';
	}
	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	function sequra_head_js() {
		/* @todo: Load only if necessary */
		$available_products = apply_filters( 'sequra_available_products', array() );
		$core_settings      = get_option( 'woocommerce_sequra_settings', array() );
		$script_base_uri    = sequra_get_script_basesurl();
		ob_start();
		include SequraHelper::template_loader( 'header-js' );
		// Could have any html disable phpcs.
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo ob_get_clean();
		// phpcs:enable
	}

	add_action( 'wp_head', 'sequra_head_js' );
	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	function sequra_add_cart_info_to_session() {
		$sequra_cart_info = WC()->session->get( 'sequra_cart_info' );
		if ( ! $sequra_cart_info ) {
			$sequra_cart_info = array(
				'ref'        => uniqid(),
				'created_at' => date( 'c' ),
			);
			WC()->session->set( 'sequra_cart_info', $sequra_cart_info );
		}
	}

	add_action( 'woocommerce_add_to_cart', 'sequra_add_cart_info_to_session' );
	/**
	 * Undocumented function
	 *
	 * @param WC_Cart $cart The cart.
	 * @return void
	 */
	function sequra_calculate_order_totals( WC_Cart $cart ) {
		$sequra = new SequraInvoiceGateway();
		$sequra->calculate_order_totals( $cart );
	}

	add_action( 'woocommerce_cart_calculate_fees', 'sequra_calculate_order_totals' );

	/*
	 * Add instalment simulator in product page
	 */

	add_filter( 'woocommerce_get_price_html', 'woocommerce_sequra_add_simulator_to_product_page', 999, 2 );
	/**
	 * Undocumented function
	 *
	 * @param string     $price   the price string.
	 * @param WC_Product $product the product on page.
	 * @return string
	 */
	function woocommerce_sequra_add_simulator_to_product_page( $price, WC_Product $product ) {
		global $wp_query, $sequra_simulator_added;
		$sequra_pp = new SequraPartPaymentGateway();
		if (
			! ( $product instanceof WC_Product ) ||
			! is_product() ||
			$wp_query->posts[0]->ID !== $product->get_id() ||
			! $sequra_pp->is_available( $product->get_id() ) ||
			$sequra_simulator_added ) {
			return $price;
		}
		// Could have any html disable phpcs.
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo sequra_pp_simulator(
			array(
				'price' => trim( $sequra_pp->price_css_sel ),
				'dest'  => trim( $sequra_pp->dest_css_sel ),
			)
		);
		// phpcs:enable
		$sequra_simulator_added = true;

		return $price .
			"<div id='sequra_partpayment_teaser_default_container'></div>";
	}
	/**
	 * SeQura pp simulator short code
	 * usage: [sequra_pp_simulator price='#product_price']
	 *
	 * @param array    $atts       Attributes.
	 * @param int|null $product_id Product id.
	 * @return void
	 */
	function sequra_pp_simulator( $atts, $product_id = null ) {
		$sequra_pp = new SequraPartPaymentGateway();
		if ( ! $sequra_pp->is_available( $product_id ) ) {
			return;
		}
		wp_enqueue_script( 'sequra-pp-cost-js', $sequra_pp->pp_cost_url, array(), true, true );
		$price_container = isset( $atts['price'] ) ? $atts['price'] : '#product_price';
		$theme           = $sequra_pp->settings['widget_theme'];
		ob_start();
		include SequraHelper::template_loader( 'partpayment-teaser' );

		return ob_get_clean();
	}

	add_shortcode( 'sequra_pp_simulator', 'sequra_pp_simulator' );

	/*
	 * Invoice teaser in product page
	 */
	add_action( 'woocommerce_after_add_to_cart_button', 'woocommerce_sequra_after_add_to_cart_button', 999 );
	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	function woocommerce_sequra_after_add_to_cart_button() {
		global $product;
		$sequra = new SequraInvoiceGateway();
		$theme  = $sequra->settings['widget_theme'];
		$dest   = $sequra->dest_css_sel ? trim( $sequra->dest_css_sel ) : '#sequra_invoice_teaser';
		include SequraHelper::template_loader( 'invoice-teaser' );
	}

	do_action( 'woocommerce_sequra_plugin_loaded' );
}
