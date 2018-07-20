<?php
/*
  Plugin Name: Campañas SeQura
  Plugin URI: http://sequra.es/
  Description: Da la opción pago para campañas especiales de SeQura.
  Version: 1.18.2
  Author: SeQura Engineering
  Author URI: http://SeQura.es/
 */

register_activation_hook( __FILE__, 'sequracampaign_activation' );
/**
 * Run once on plugin activation
 */
function sequracampaign_activation() {
	// Place in first place
	$gateway_order = (array) get_option( 'woocommerce_gateway_order' );
	$order         = array(
		'sequracampaign' => 0
	);
	if ( is_array( $gateway_order ) && sizeof( $gateway_order ) > 0 ) {
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
function sequracampaign_upgrade_if_needed() {
	if ( time() > get_option( 'sequracampaign_next_update' ) || isset( $_GET['sequra_campaign_reset_conditions'] ) ) {
		$coresettings = get_option( 'woocommerce_sequra_settings', array() );
		$cost_url     = 'https://' .
		                ( $coresettings['env'] ? 'sandbox' : 'live' ) .
		                '.sequracdn.com/scripts/' .
		                $coresettings['merchantref'] . '/' .
		                $coresettings['assets_secret'] .
		                '/pp5_cost.json';
		$json         = file_get_contents( $cost_url );
		update_option( 'sequracampaign_conditions', $json );
		do_action( 'sequracampaign_updateconditions' );
		update_option( 'sequracampaign_next_update', time() + 86400 );
	}
}

add_action( 'woocommerce_sequra_plugin_loaded', 'woocommerce_sequracampaign_init', 110 );

function woocommerce_sequracampaign_init() {
	//@todo langages
	//load_plugin_textdomain( 'wc_sequracampaign', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	if ( ! class_exists( 'SequraCampaignPaymentGateway' ) ) {
		require_once( WP_PLUGIN_DIR . "/" . dirname( plugin_basename( __FILE__ ) ) . '/SequraCampaignPaymentGateway.php' );
	}

	/**
	 * Add the gateway to woocommerce
	 * */
	function add_sequracapaign_gateway( $methods ) {
		$methods[] = 'SequraCampaignPaymentGateway';

		return $methods;
	}

	add_filter( 'woocommerce_payment_gateways', 'add_sequracapaign_gateway' );

	//[sequracampaign_teaser]
	function sequracampaign_teaser( ) {
		$sequracampaign = WC_Payment_Gateways::instance()->payment_gateways()['sequracampaign'];
		include( SequraCampaignPaymentGateway::template_loader( 'campaign_teaser' ) );
	}

	add_shortcode( 'sequracampaign_teaser', 'sequracampaign_teaser' );
	/*
	 * Campaign teaser in product page
	 */
	add_action( 'woocommerce_after_add_to_cart_button', 'sequracampaign_teaser', 9 );
}