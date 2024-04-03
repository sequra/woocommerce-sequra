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
	// Place in first place.
	$gateway_order = (array) get_option( 'woocommerce_gateway_order' );
	$order         = array(
		'sequracheckout' => 0,
	);
	if ( is_array( $gateway_order ) && count( $gateway_order ) > 0 ) {
		$loop = 3;
		foreach ( $gateway_order as $gateway_id ) {
			$order[ esc_attr( $gateway_id ) ] = $loop;
			++$loop;
		}
	}
	update_option( 'woocommerce_gateway_order', $order );
	// Schedule a daily event for sending delivery report on plugin activation.
	$random_offset = wp_rand( 0, 25200 ); // 60*60*7 seconds from 2AM to 8AM.
	$tomorrow      = gmdate( 'Y-m-d 02:00', strtotime( 'tomorrow' ) );
	$time          = $random_offset + strtotime( $tomorrow );
	add_option( 'woocommerce-sequra-deliveryreport-time', $time );
	wp_schedule_event( $time, 'daily', 'sequra_send_daily_delivery_report' );
}

add_action( 'sequra_upgrade_if_needed', 'sequra_upgrade_if_needed' );

/**
 * Check if it needs aupgrade
 *
 * @return void
 */
function sequra_upgrade_if_needed() {
	$current = get_option( 'SEQURA_VERSION' );
	if ( version_compare( $current, SEQURA_VERSION, '<' ) ) {
		// phpcs:disable WordPressVIPMinimum.Files.IncludingFile.UsingVariable
		foreach ( glob( __DIR__ . '/upgrades/*.php' ) as $filename ) {
			include $filename;
		}
		// phpcs:enable WordPressVIPMinimum.Files.IncludingFile.UsingVariable
		/**
		 * Fires when the plugin is upgraded.
		 *
		 * @since 2.0.0
		 */
		do_action(
			'sequracheckout_upgrade',
			array(
				'from' => $current,
				'to'   => SEQURA_VERSION,
			)
		);
		update_option( 'SEQURA_VERSION', (string) SEQURA_VERSION );
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
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['sequra_triggerreport'] ) && 'true' === $_GET['sequra_triggerreport'] ) {
		/**
		 * Fires when the delivery report is triggered.
		 * 
		 * @since 2.0.0
		 */
		do_action( 'sequra_send_daily_delivery_report' );
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
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
	// phpcs:disable WordPressVIPMinimum.Files.IncludingFile.UsingCustomConstant
	include SequraHelper::template_loader( 'banner-' . $product );
	// phpcs:enable WordPressVIPMinimum.Files.IncludingFile.UsingCustomConstant
	return ob_get_clean();
}

add_shortcode( 'sequra_banner', 'sequra_banner' );

/**
 * Check if it needs aupgrade
 *
 * @return void
 */
function sequrapayment_upgrade_if_needed() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	if (
		time() > get_option( 'sequrapayment_next_update', 0 ) ||
		isset( $_GET['RESET_SEQURA_ACTIVE_PAYMENTS'] ) ||
		isset( $_GET['RESET_SEQURA_ACTIVE_METHODS'] )
	) {
		$pm = SequraPaymentGateway::get_instance();
		$pm->get_remote_config()->update_active_payment_methods();
		/**
		 * Fires when the plugin needs to update payment methods information.
		 * 
		 * @since 2.0.0
		 */
		do_action( 'sequrapayment_updateconditions' );
		update_option( 'sequrapayment_next_update', time() + 86400 );
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
}

add_action( 'sequra_upgrade_if_needed', 'sequrapayment_upgrade_if_needed' );

add_action(
	'admin_enqueue_scripts',
	function () {
		wp_enqueue_script( 'sequra_configuration_script', plugin_dir_url( __FILE__ ) . 'assets/js/sequra_config.js', array(), SEQURA_VERSION, true );

		wp_register_style( 'sequra_logs', plugin_dir_url( __FILE__ ) . 'assets/css/logs.css', array(), SEQURA_VERSION );
		wp_register_script( 'sequra_logs', plugin_dir_url( __FILE__ ) . 'assets/js/logs.js', array(), SEQURA_VERSION, true );
		wp_localize_script(
			'sequra_logs',
			'sequraLogs',
			array(
				'nonce' => wp_create_nonce( 'wp_rest' ),
				'url'   => rest_url( 'sequra/v1/logs' ),
			)
		);
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
			'docs'    => '<a href="' . esc_url(
				/**
				 * Filters the URL of the plugin documentation.
				 *
				 * @since 2.0.0
				 */
				apply_filters( 'sequrapayment_docs_url', 'https://sequra.atlassian.net/wiki/spaces/DOC/pages/1334280489/WOOCOMMERCE' )
			) . '" aria-label="' . esc_attr__( 'View WooCommerce documentation', 'sequra' ) . '">' . esc_html__( 'Docs', 'woocommerce' ) . '</a>',
			'apidocs' => '<a href="' . esc_url(
				/**
				 * Filters the URL of the plugin API documentation.
				 *
				 * @since 2.0.0
				 */
				apply_filters( 'sequrapayment_apidocs_url', 'https://docs.sequrapi.com/' )
			) . '" aria-label="' . esc_attr__( 'View WooCommerce API docs', 'sequra' ) . '">' . esc_html__( 'API docs', 'sequra' ) . '</a>',
			'support' => '<a href="' . esc_url(
				/**
				 * Filters the URL of the plugin support.
				 *
				 * @since 2.0.0
				 */
				apply_filters( 'sequrapayment_support_url', 'mailto:sat@sequra.es' )
			) . '" aria-label="' . esc_attr__( 'Support', 'sequra' ) . '">' . esc_html__( 'Support', 'sequra' ) . '</a>',
		);

		return array_merge( $links, $row_meta );
	}

	return (array) $links;
}
/**
 * Add links to plugin in installed plugin list
 *
 * @param array $links Plugin Row Meta.
 * @return array
 */
function sequrapayment_action_links( $links ) {
	return array_merge(
		array(
			'comf' => '<a href="' . esc_url(
				/**
				 * Filters the URL of the plugin configuration.
				 *
				 * @since 2.0.0
				 */
				apply_filters( 'sequrapayment_conf_url', admin_url( 'admin.php?page=wc-settings&tab=checkout&section=sequra' ) )
			) . '" aria-label="' . esc_attr__( 'View WooCommerce documentation', 'sequra' ) . '">' . esc_html__( 'Settings', 'woocommerce' ) . '</a>',
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
	if ( did_action( 'woocommerce_loaded' ) >= 2 ) {
		// avoid double execution.
		return;
	}

	if ( ! class_exists( 'SequraHelper' ) ) {
		require_once WC_SEQURA_PLG_PATH . 'class-sequrahelper.php';
	}
	if ( ! class_exists( 'SequraLogger' ) ) {
		require_once WC_SEQURA_PLG_PATH . 'class-sequralogger.php';
	}
	if ( ! class_exists( 'SequraRemoteConfig' ) ) {
		require_once WC_SEQURA_PLG_PATH . 'class-sequraremoteconfig.php';
	}
	if ( ! class_exists( 'SequraPaymentGateway' ) ) {
		require_once WC_SEQURA_PLG_PATH . 'class-sequrapaymentgateway.php';
	}
	( new \SequraLogger() )->log_info( 'Hook executed', __FUNCTION__ );
	/**
	 * Fires when the plugin needs to update payment methods information.
	 *
	 * @since 2.0.0
	 */
	do_action( 'sequra_upgrade_if_needed' );
	load_plugin_textdomain( 'sequra', false, dirname( plugin_basename( __FILE__ ) ) . '/i18n/languages' );
	add_filter( 'plugin_row_meta', 'sequrapayment_plugin_row_meta', 10, 2 );
	/**
	 * Add the gateway to woocommerce
	 *
	 * @param array $methods available methods.
	 * @return array
	 */
	function add_sequra_gateway( $methods ) {
		( new SequraLogger() )->log_info( 'Hook executed', __FUNCTION__ );
		$methods[] = 'SequraPaymentGateway';
		return $methods;
	}
	add_filter( 'woocommerce_payment_gateways', 'add_sequra_gateway' );

	if ( ! class_exists( 'Sequra_Meta_Box_Settings' ) ) {
		require_once WP_PLUGIN_DIR . '/' . dirname( plugin_basename( __FILE__ ) ) . '/includes/admin/meta-boxes/class-sequra-meta-box-settings.php';
	}
	add_action( 'woocommerce_process_product_meta', 'Sequra_Meta_Box_Settings::save', 20, 2 );
	add_action( 'add_meta_boxes', 'Sequra_Meta_Box_Settings::add_meta_box' );

	$core_settings = get_option( 'woocommerce_sequra_settings', SequraHelper::get_empty_core_settings() );
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
		( new SequraLogger() )->log_info( 'Hook executed', __FUNCTION__ );
		// Respects SSL, Style.css is relative to the current file.
		wp_register_style( 'sequra-banner', plugins_url( 'assets/css/banner.css', __FILE__ ), array(), SEQURA_VERSION );
		wp_register_style( 'sequra-widget', plugins_url( 'assets/css/widget.css', __FILE__ ), array(), SEQURA_VERSION );
		wp_register_style( 'sequracheckout', plugins_url( 'assets/css/sequracheckout.css', __FILE__ ), array(), SEQURA_VERSION );
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
	 * Enqueue plugin scripts and styles for Sequra Helper initialization
	 */
	function sequra_head_js() {
		( new SequraLogger() )->log_info( 'Hook executed', __FUNCTION__ );
		wp_enqueue_style( 'sequra-head', plugins_url( 'assets/css/sequra_head.css', __FILE__ ), array(), SEQURA_VERSION );
		wp_register_script( 'sequra-head', plugins_url( 'assets/js/sequra_head.js', __FILE__ ), array(), SEQURA_VERSION, false );
		// Add required object for Sequra Helper initialization.
		$available_products = get_option( 'SEQURA_ACTIVE_METHODS' );
		if ( is_serialized( $available_products ) ) {
			$available_products = unserialize( $available_products ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
		}
		$available_products = array_map( 'esc_js', (array) $available_products );
		$core_settings      = get_option( 'woocommerce_sequra_settings', SequraHelper::get_empty_core_settings() );
		$script_base_uri    = sequra_get_script_baseurl();

		/**
		 * Filter the asset key used to load the Sequra assets.
		 * 
		 * @since 2.1.0
		 */
		$asset_key = apply_filters( 'woocommerce_sequra_get_assetkey', $core_settings['assets_secret'] );
		/**
		 * Filter the price decimal separator used to load the Sequra assets.
		 * 
		 * @since 2.1.0
		 */
		$price_decimal_separator = apply_filters( 'woocommerce_sequra_price_decimal_separator', wc_get_price_decimal_separator() );
		/**
		 * Filter the price thousand separator used to load the Sequra assets.
		 * 
		 * @since 2.1.0
		 */
		$price_thousand_separator = apply_filters( 'woocommerce_sequra_price_thousand_separator', wc_get_price_thousand_separator() );

		/**
		 * Filter the locale used to load the Sequra assets.
		 * 
		 * @since 2.1.0
		 */
		$locale = apply_filters( 'woocommerce_sequra_locale', str_replace( '_', '-', get_locale() ) );

		wp_localize_script(
			'sequra-head',
			'sequraConfigParams',
			array(
				'merchant'          => esc_js( SequraHelper::get_instance()->get_merchant_ref() ),
				'assetKey'          => esc_js( $asset_key ),
				'products'          => $available_products,
				'scriptUri'         => esc_js( $script_base_uri ) . 'sequra-checkout.min.js',
				'decimalSeparator'  => esc_js( $price_decimal_separator ),
				'thousandSeparator' => esc_js( $price_thousand_separator ),
				'locale'            => esc_js( $locale ),
				'widgets'           => array(), // Store widgets to be drawn.
			)
		);
		wp_enqueue_script( 'sequra-head' );
	}
	add_action( 'wp_enqueue_scripts', 'sequra_head_js' );

	/**
	 * Register plugin scripts used for teaser widgets
	 */
	function sequra_teaser_js() {
		wp_register_script( 'sequra-widget', plugins_url( 'assets/js/sequra_widget.js', __FILE__ ), array( 'sequra-head', 'jquery' ), SEQURA_VERSION, true );
	}
	add_action( 'wp_enqueue_scripts', 'sequra_teaser_js' );

	/**
	 * Create something similar to a cart_ref that could be used during the session.
	 *
	 * @return void
	 */
	function sequra_add_cart_info_to_session() {
		( new SequraLogger() )->log_info( 'Hook executed', __FUNCTION__ );
		$sequra_cart_info = WC()->session->get( 'sequra_cart_info' );
		if ( ! $sequra_cart_info ) {
			$sequra_cart_info = array(
				'ref'        => uniqid(),
				'created_at' => gmdate( 'c' ),
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
	 * @return void
	 */
	function woocommerce_sequra_add_widget_to_product_page() {
		if ( did_action( 'before_woocommerce_sequra_add_widget_to_product_page' ) ) {
			return; // Only execute once.
		}

		if ( ! is_product() ) {
			return;
		}

		/**
		 * Fires before the SeQura widget is added to the product page to prevent multiple executions.
		 *
		 * @since 2.1.0.
		 */
		do_action( 'before_woocommerce_sequra_add_widget_to_product_page' );

		/**
		 * The current product.
		 *
		 * @var WC_Product $product
		 */
		global $product;

		$sequra = SequraPaymentGateway::get_instance();
		if ( ! $sequra->is_available( $product->get_id() ) ) {
			return;
		}
		// Could have any html disable phpcs.
		$methods = array_reverse( // So that the expected order is mantained if different widgets are chained to the same selector.
			$sequra->get_remote_config()->get_merchant_payment_methods()
		);
		$price   = is_numeric( $product->get_price() ) ? $product->get_price() : 0;
		foreach ( $methods as $method ) {
			$sq_product = $sequra->get_remote_config()->build_unique_product_code( $method );
			$too_high   = isset( $method['max_amount'] ) && $method['max_amount'] < $price * 100;
			$too_early  = isset( $method['starts_at'] ) && time() < strtotime( $method['starts_at'] );
			$too_late   = isset( $method['ends_at'] ) && strtotime( $method['ends_at'] ) < time();
			if (
				! $too_early &&
				! $too_late &&
				! $too_high &&
				isset( $sequra->settings[ 'enabled_in_product_' . $sq_product ] ) &&
				'yes' === $sequra->settings[ 'enabled_in_product_' . $sq_product ]
			) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$att_product    = isset( $method['product'] ) ? $method['product'] : '';
				$att_campaign   = isset( $method['campaign'] ) ? $method['campaign'] : '';
				$att_dest       = trim( $sequra->settings[ 'dest_css_sel_' . $sq_product ] );
				$att_product_id = $product->get_id();
				
				echo do_shortcode( "[sequra_widget product=\"$att_product\" campaign=\"$att_campaign\" dest=\"$att_dest\" product_id=\"$att_product_id\"]" );
			}
		}
	}
	/**
	 * SeQura widget short code
	 * usage: [sequra_widget product='pp5' campaign='temporary' price='#product_price' variation_price='#variation_price' dest='.price_container' product_id='123']
	 *
	 * @param array    $atts       Attributes.
	 * @param int|null $product_id Product id.
	 * @return void
	 */
	function sequra_widget( $atts, $product_id = null ) {
		( new SequraLogger() )->log_info( 'Shortcode called', __FUNCTION__ );
		if ( ! isset( $atts['product'] ) ) {
			( new SequraLogger() )->log_error( '"product" attribute is required', __FUNCTION__ );
			return;
		}

		$sequra = SequraPaymentGateway::get_instance();
		// Set default values to avoid errors. Added product_id to the shortcode_atts 
		// because 2nd parameter in shortcode functions is reserved for $content.
		// See https://developer.wordpress.org/reference/functions/add_shortcode/ .
		$atts = shortcode_atts(
			array(
				'product'         => '',
				'campaign'        => '',
				'price'           => trim( $sequra->settings['price_css_sel'] ),
				'variation_price' => trim( $sequra->settings['variation_price_css_sel'] ),
				'dest'            => '',
				'product_id'      => '',
			),
			$atts,
			'sequra_widget'
		);

		$registration_amount = 0;

		if ( ! empty( $atts['product_id'] ) ) {
			$product_id = (int) $atts['product_id'];
			
			if ( ! $sequra->is_available( $product_id ) ) {
				return;
			}

			$meta_value = get_post_meta( $product_id, 'sequra_registration_amount', true );
			if ( ! empty( $meta_value ) ) {
				$registration_amount = $sequra->helper->get_builder()->integerPrice( $meta_value );
			}
		}

		$sq_product = $sequra->get_remote_config()->build_unique_product_code( $atts );
		$reverse    = 0;
		$theme      = isset( $sequra->settings[ 'widget_theme_' . $sq_product ] ) ? $sequra->settings[ 'widget_theme_' . $sq_product ] : '';
		
		! wp_style_is( 'sequra-widget' ) && wp_enqueue_style( 'sequra-widget' );
		! wp_script_is( 'sequra-widget' ) && wp_enqueue_script( 'sequra-widget' );

		
		ob_start();
		include SequraHelper::template_loader( 'widget' ); // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingCustomConstant
		
		// Next is only for compatibility purposes.
		if ( 'PARTPAYMENT' === SequraRemoteConfig::get_family_for( $atts ) ) {
			$product         = $atts['product'];
			$campaign        = $atts['campaign'];
			$price_container = $atts['price'];
			// phpcs:disable WordPressVIPMinimum.Files.IncludingFile.UsingCustomConstant
			include SequraHelper::template_loader( 'partpayment-teaser' );
			include SequraHelper::template_loader( 'widget-compat' );
			// phpcs:enable WordPressVIPMinimum.Files.IncludingFile.UsingCustomConstant
		}

		return ob_get_clean();
	}

	add_shortcode( 'sequra_widget', 'sequra_widget' );

	/**
	 * Register logs page
	 */
	function sequra_register_logs_page() {
		( new SequraLogger() )->log_info( 'Hook executed', __FUNCTION__ );
		add_submenu_page(
			'woocommerce',
			'seQura Logs',
			'seQura Logs',
			'manage_options',
			'sequra-logs',
			'render_sequra_logs_page'
		);
	}
	add_action( 'admin_menu', 'sequra_register_logs_page' );

	/**
	 * Remove logs page from submenu
	 *
	 * @param string $submenu_file Submenu file.
	 * @param string $parent_file Parent file.
	 * @return string
	 */
	function sequra_remove_submenu_logs_page( $submenu_file, $parent_file ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		( new \SequraLogger() )->log_info( 'Hook executed', __FUNCTION__ );
		remove_submenu_page( 'woocommerce', 'sequra-logs' );
		return $submenu_file;
	}
	add_filter( 'submenu_file', 'sequra_remove_submenu_logs_page', 10, 2 );

	/**
	 * Render logs page
	 */
	function render_sequra_logs_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		wp_enqueue_style( 'sequra_logs' );
		wp_enqueue_script( 'sequra_logs' );
		$is_debug_enabled = SequraHelper::get_instance()->is_debug_enabled();
		ob_start();     
		include SequraHelper::template_loader( 'logs' ); // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingCustomConstant
		echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Register the REST API endpoints
	 */
	add_action(
		'rest_api_init',
		function () {
			( new \SequraLogger() )->log_info( 'API endpoints registered' );
			register_rest_route(
				'sequra/v1',
				'/logs',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => 'sequra_api_get_logs',
					'permission_callback' => 'sequra_api_permission_callback',
				)
			);

			register_rest_route(
				'sequra/v1',
				'/logs',
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => 'sequra_api_delete_logs',
					'permission_callback' => 'sequra_api_permission_callback',
				)
			);
		} 
	);

	/**
	 * API REST: Get logs
	 */
	function sequra_api_get_logs() {
		$logger = new \SequraLogger();
		$logger->log_info( 'API request received', __FUNCTION__ );

		$logs = false;
		try {
			$logs = $logger->get_log_content();
		} catch ( Exception $e ) {
			return rest_ensure_response( new WP_Error( 'error', $e->getMessage() ) );
		}
		echo esc_textarea( $logs );
		exit;
	}

	/**
	 * API REST: Delete logs
	 */
	function sequra_api_delete_logs() {
		$logger = new \SequraLogger();
		$logger->log_info( 'API request received', __FUNCTION__ );
	
		$response = array( 'message' => 'Logs deleted' );
		try {
			$logger->clear_log();
		} catch ( \Exception $e ) {
			$response = new WP_Error( 'error', $e->getMessage() );
		}
		return rest_ensure_response( $response );
	}

	/**
	 * API REST: Permission callback
	 */
	function sequra_api_permission_callback() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Hook to plugin loaded event.
	 *
	 * @since 2.0.0 
	 */
	do_action( 'woocommerce_sequra_plugin_loaded' );
}
