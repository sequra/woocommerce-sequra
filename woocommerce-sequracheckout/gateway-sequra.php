<?php
/**
 * SeQura gateway file.
 *
 * @package woocommerce-sequra
 */

/**
 * Run once on plugin activation
 */
function sequra_activation() {
	//Place in first place.
	$gateway_order = (array) get_option( 'woocommerce_gateway_order' );
	$order         = array(
		'sequracheckout'  => 0,
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
	$current = get_option( 'sequracheckout_version' );
	if ( version_compare( $current, SEQURACHECKOUT_VERSION, '<' ) ) {
		foreach ( glob( dirname( __FILE__ ) . '/upgrades/*.php' ) as $filename ) {
			include $filename;
		}
		do_action(
			'sequracheckout_upgrade',
			array(
				'from' => $current,
				'to'   => SEQURACHECKOUT_VERSION,
			)
		);
		update_option( 'sequracheckout_version', (string) SEQURACHECKOUT_VERSION );
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

/**
 * SeQura banner short code.
 * usage: [sequra_banner product='i1'] [sequra_banner product='pp3'] [sequra_banner product='pp6'].
 *
 * @param array $atts short code attribute.
 * @return void
 */
function sequra_banner( $atts ) {
	wp_enqueue_style( 'sequra-banner' );
	$product = $atts['product'];
	$pm      = SequraPaymentGateway::get_instance();
	$pm->is_available();
	if ( ! $pm || ! $pm->is_available() ) {
		return;
	}
	ob_start();
	include SequraHelper::template_loader( 'banner-' . $product );

	return ob_get_clean();
}

add_shortcode( 'sequra_banner', 'sequra_banner' );

/**
 * Check if it needs aupgrade
 *
 * @return void
 */
function sequrapayment_upgrade_if_needed() {
	if ( time() > get_option( 'sequrapayment_next_update', 0 ) ||
		isset( $_GET['RESET_SEQURA_ACTIVE_PAYMENTS'] ) ) {
		$pm = SequraPaymentGateway::get_instance();
		$pm->remote_config->update_active_payment_methods();
		do_action( 'sequrapayment_updateconditions' );
		update_option( 'sequrapayment_next_update', time() + 86400 );
	}
}

add_action( 'sequra_upgrade_if_needed', 'sequrapayment_upgrade_if_needed' );

add_action(
	'admin_enqueue_scripts',
	function() {
		wp_enqueue_script( 'sequra_configuration_script', plugin_dir_url( __FILE__ ) . 'assets/js/sequra_config.js' );
	}
);

/**
 * Show row meta on the plugin screen.
 *
 * @param mixed $links Plugin Row Meta.
 * @param mixed $file  Plugin Base file.
 *
 * @return array
 */
function sequrapayment_plugin_row_meta( $links, $file ) {
	if ( plugin_basename( __FILE__ ) === $file ) {
		$row_meta = array(
			'docs'    => '<a href="' . esc_url( apply_filters( 'sequrapayment_docs_url', 'https://sequra.atlassian.net/wiki/spaces/DOC/pages/1334280489/WOOCOMMERCE' ) ) . '" aria-label="' . esc_attr__( 'View WooCommerce documentation', 'wc_sequra' ) . '">' . esc_html__( 'Docs', 'woocommerce' ) . '</a>',
			'apidocs' => '<a href="' . esc_url( apply_filters( 'sequrapayment_apidocs_url', 'https://docs.sequrapi.com/' ) ) . '" aria-label="' . esc_attr__( 'View WooCommerce API docs', 'wc_sequra' ) . '">' . esc_html__( 'API docs', 'wc_sequra' ) . '</a>',
			'support' => '<a href="' . esc_url( apply_filters( 'sequrapayment_support_url', 'mailto:sat@sequra.es' ) ) . '" aria-label="' . esc_attr__( 'Soporte', 'woocommerce' ) . '">' . esc_html__( 'Soporte', 'wc_sequra' ) . '</a>',
		);

		return array_merge( $links, $row_meta );
	}

	return (array) $links;
}
/**
 * Add links to plugin in installed plugin list
 *
 * @param array $links
 * @return array
 */
function sequrapayment_action_links( $links ) {
	return array_merge(
		array(
			'comf'    => '<a href="' . esc_url( apply_filters( 'sequrapayment_conf_url', admin_url( 'admin.php?page=wc-settings&tab=checkout&section=sequra' ) ) ) . '" aria-label="' . esc_attr__( 'View WooCommerce documentation', 'wc_sequra' ) . '">' . esc_html__( 'Settings', 'woocommerce' ) . '</a>',
		),
		$links
	);
}

add_action( 'woocommerce_loaded', 'woocommerce_sequra_init', 200 );
/**
 * Init
 *
 * @return mixed
 */
function woocommerce_sequra_init() {
	if ( ! class_exists( 'SequraHelper' ) ) {
		require_once WC_SEQURA_PLG_PATH . 'class-sequrahelper.php';
	}
	if ( ! class_exists( 'SequraRemoteConfig' ) ) {
		require_once WC_SEQURA_PLG_PATH . 'class-sequraremoteconfig.php';
	}
	if ( ! class_exists( 'SequraPaymentGateway' ) ) {
		require_once WC_SEQURA_PLG_PATH . 'class-sequrapaymentgateway.php';
	}
	do_action( 'sequra_upgrade_if_needed' );
	load_plugin_textdomain( 'wc_sequra', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	add_filter( 'plugin_row_meta', 'sequrapayment_plugin_row_meta', 10, 2 );
	/**
	 * Add the gateway to woocommerce
	 *
	 * @param array $methods available methods.
	 * @return array
	 */
	function add_sequra_gateway( $methods ) {
		$methods[] = 'SequraPaymentGateway';
		return $methods;
	}
	add_filter( 'woocommerce_payment_gateways', 'add_sequra_gateway' );

	if ( ! class_exists( 'Sequra_Meta_Box_Settings' ) ) {
		require_once WP_PLUGIN_DIR . '/' . dirname( plugin_basename( __FILE__ ) ) . '/includes/admin/meta-boxes/class-sequra-meta-box-settings.php';
	}
	add_action( 'woocommerce_process_product_meta', 'Sequra_Meta_Box_Settings::save', 20, 2 );
	add_action( 'add_meta_boxes', 'Sequra_Meta_Box_Settings::add_meta_box' );

	$core_settings = get_option( 'woocommerce_sequra_settings', array() );
	if ( isset( $core_settings['enable_for_virtual'] ) && 'yes' === $core_settings['enable_for_virtual'] ) {
		if ( ! class_exists( 'Sequra_Meta_Box_Service_Options' ) ) {
			require_once WP_PLUGIN_DIR . '/' . dirname( plugin_basename( __FILE__ ) ) . '/includes/admin/meta-boxes/class-sequra-meta-box-service-options.php';
		}
		add_action( 'woocommerce_process_product_meta', 'Sequra_Meta_Box_Service_Options::save', 20, 2 );
		add_action( 'add_meta_boxes', 'Sequra_Meta_Box_Service_Options::add_meta_box' );
	}

	/**
	 * Enqueue plugin style-file
	 */
	function sequra_add_stylesheet_cdn_js() {
		// Respects SSL, Style.css is relative to the current file.
		wp_register_style( 'sequra-banner', plugins_url( 'assets/css/banner.css', __FILE__ ), array(), SEQURACHECKOUT_VERSION );
		wp_register_style( 'sequra-widget', plugins_url( 'assets/css/widget.css', __FILE__ ), array(), SEQURACHECKOUT_VERSION );
		wp_register_style( 'sequracheckout', plugins_url( 'assets/css/sequracheckout.css', __FILE__ ), array(), SEQURACHECKOUT_VERSION );
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
		$available_products = unserialize( get_option( 'SEQURA_ACTIVE_METHODS' ) );
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
	 * Create something similar to a cart_ref that could be used during the session.
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

	/*
	 * Add widgets product page
	 */

	add_action( 'woocommerce_after_main_content', 'woocommerce_sequra_add_widget_to_product_page', 999 );
	add_action( 'wp_footer', 'woocommerce_sequra_add_widget_to_product_page', 999 );

	/**
	 * Undocumented function
	 *
	 * @param string     $price   the price string.
	 * @param WC_Product $product the product on page.
	 * @return string
	 */
	function woocommerce_sequra_add_widget_to_product_page() {
		global $product;
		if ( ! is_product() ) {
			return;
		}
		$sequra = SequraPaymentGateway::get_instance();
		if ( ! $sequra->is_available( $product->get_id() ) ) {
			return;
		}
		// Could have any html disable phpcs.
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		$methods = array_reverse(//So that the expected order is mantained if different widgets are chained to the same selector
			$sequra->remote_config->get_merchant_payment_methods()
		);
		foreach ( $methods as $method ) {
			$sq_product = $sequra->remote_config->build_unique_product_code( $method );
			//$too_low = isset( $method['min_amount'] ) && $method['min_amount'] > $product->get_price() * 100;
			$too_high= isset( $method['max_amount'] ) && $method['max_amount'] < $product->get_price() * 100;
			if( /*( ! $too_low && ! $too_high ) &&*/
				! $too_high &&
				isset( $sequra->settings['enabled_in_product_' . $sq_product ] ) &&
				$sequra->settings['enabled_in_product_' . $sq_product ] == 'yes'
			) {
				echo sequra_widget(
					array(
						'product'  => isset( $method['product'] ) ? $method['product'] : '',
						'campaign' => isset( $method['campaign'] ) ? $method['campaign'] : '',
						'price'    => trim( $sequra->settings['price_css_sel'] ),
						'dest'     => trim( $sequra->settings[ 'dest_css_sel_' . $sq_product ] ),
					),
					$product->get_id()
				);
			}
		}
		// phpcs:enable
		// Once executed make sure it is not executed again
		remove_action( 'woocommerce_after_main_content', 'woocommerce_sequra_add_widget_to_product_page');
		remove_action( 'wp_footer', 'woocommerce_sequra_add_widget_to_product_page');
	}
	/**
	 * SeQura pp simulator short code
	 * usage: [sequra_widget product='pp5' campaign='temporary' price='#product_price' dest='.price_container']
	 *
	 * @param array    $atts       Attributes.
	 * @param int|null $product_id Product id.
	 * @return void
	 */
	function sequra_widget( $atts, $product_id = null ) {
		if ( ! isset( $atts['product'] ) ) {
			return;
		}
		$sequra = SequraPaymentGateway::get_instance();
		if ( ! $sequra->is_available( $product_id ) ) {
			return;
		}
		$registration_amount = 0;
		if ( ! is_null( $product_id ) && get_post_meta( $product_id, 'sequra_registration_amount', true )){
			$registration_amount = $sequra->helper->get_builder()->integerPrice(
				get_post_meta( $product_id, 'sequra_registration_amount', true )
			);
		}
		$product         = $atts['product'];
		$dest            = $atts['dest'];
		$campaign        = isset( $atts['campaign'] ) ? $atts['campaign'] : '';
		$price_container = isset( $atts['price'] ) ? $atts['price'] : '#product_price';
		$sq_product      = $sequra->remote_config->build_unique_product_code( $atts );
		$theme           = isset( $sequra->settings[ 'widget_theme_'.$sq_product ] ) ? $sequra->settings[ 'widget_theme_'.$sq_product ] : '';
		wp_enqueue_style( 'sequra-widget' );
		ob_start();
		if ( SequraRemoteConfig::get_family_for( $atts ) == 'PARTPAYMENT' ) {
			include SequraHelper::template_loader( 'partpayment-teaser' );
		} else {
			include SequraHelper::template_loader( 'invoice-teaser' );
		}
		return ob_get_clean();
	}

	add_shortcode( 'sequra_widget', 'sequra_widget' );

	do_action( 'woocommerce_sequra_plugin_loaded' );
}