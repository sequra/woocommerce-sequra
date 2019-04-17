<?php
/**
 * Plugin Name: Campañas Sequra
 * Plugin URI: http://sequra.es/
 * Description: Da la opción pago para campañas especiales de Sequra.
 * Version: 4.8.3
 * Author: SeQura Engineering
 * Author URI: http://Sequra.es/
 * WC tested up to: 3.5.4
 *
 * @package woocommerce-sequracampaign
 */

register_activation_hook( __FILE__, 'sequracampaign_activation' );
/**
 * Run once on plugin activation
 */
function sequracampaign_activation() {
	// Place in first place.
	$gateway_order = (array) get_option( 'woocommerce_gateway_order' );
	$order         = array(
		'sequracampaign' => 0,
	);
	if ( is_array( $gateway_order ) && count( $gateway_order ) > 0 ) {
		$loop = 1;
		foreach ( $gateway_order as $gateway_id ) {
			$order[ esc_attr( $gateway_id ) ] = $loop;
			$loop ++;
		}
	}
	update_option( 'woocommerce_gateway_order', $order );
	update_option( 'woocommerce_default_gateway', 'sequracampaign' );
}

add_action( 'sequra_upgrade_if_needed', 'sequracampaign_upgrade_if_needed' );
/**
 * Upgrade campaign plugin and conditions if needed
 */
function sequracampaign_upgrade_if_needed() {
	if ( time() > get_option( 'sequracampaign_next_update' ) || isset( $_GET['sequra_campaign_reset_conditions'] ) ) {
		$core_settings = get_option( 'woocommerce_sequra_settings', array() );
		$cost_url      = 'https://' .
						( $core_settings['env'] ? 'sandbox' : 'live' ) .
						'.sequracdn.com/scripts/' .
						$core_settings['merchantref'] . '/' .
						$core_settings['assets_secret'] .
						'/pp5_cost.json';
		$response      = wp_remote_get( $cost_url );
		if ( is_array( $response ) && ! is_wp_error( $response ) ) {
			update_option( 'sequracampaign_conditions', $response['body'] );
			do_action( 'sequracampaign_updateconditions' );
			update_option( 'sequracampaign_next_update', time() + 86400 );

		}
	}
}

add_action( 'woocommerce_sequra_plugin_loaded', 'woocommerce_sequracampaign_init', 110 );

/**
 * Init plugin
 */
function woocommerce_sequracampaign_init() {
	load_plugin_textdomain( 'wc_sequracampaign', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	if ( ! class_exists( 'SequraCampaignGateway' ) ) {
		require_once WP_PLUGIN_DIR . '/' . dirname( plugin_basename( __FILE__ ) ) . '/class-sequracampaigngateway.php';
	}

	/**
	 * Add the gateway to woocommerce
	 *
	 * @param array $methods Available methods.
	 * */
	function add_sequracapaign_gateway( $methods ) {
		$methods[] = 'SequraCampaignGateway';

		return $methods;
	}

	add_filter( 'woocommerce_payment_gateways', 'add_sequracapaign_gateway' );
	/**
	 * Short code [sequracampaign_teaser]
	 *
	 * @return void
	 */
	function sequracampaign_teaser() {
		$sequracampaign = new SequraCampaignGateway();
		$theme  = $sequracampaign->settings['widget_theme'];
		$dest   = $sequracampaign->dest_css_sel ? trim( $sequracampaign->dest_css_sel ) : '#sequra_campaign_teaser';
		include SequraCampaignGateway::template_loader( 'campaign-teaser' );
	}

	add_shortcode( 'sequracampaign_teaser', 'sequracampaign-teaser' );
	/**
	 * Campaign teaser in product page
	 */
	add_action( 'woocommerce_after_add_to_cart_button', 'sequracampaign_teaser', 9 );
}
