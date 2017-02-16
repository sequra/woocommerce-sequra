<?php
function sequra_upgrade_from_less_than_400( $version ) {
	if ( version_compare( '4.0.0', $version['from'] ) < 1 ) {
		return;
	}
	$pp_settings = get_option( 'woocommerce_sequra_pp_settings' );
	$settings    = get_option( 'woocommerce_sequra_settings' );
	if ( preg_match( '/scripts\/' . $settings['merchantref'] . '\/([^\/]*)\/pp3_cost.json/', $pp_settings['pp_cost_url'], $m ) ) {
		$settings['assets_secret'] = $m[1];
		update_option( 'woocommerce_sequra_settings', $settings );
	}
}

add_action( 'sequra_upgrade', 'sequra_upgrade_from_less_than_400' );