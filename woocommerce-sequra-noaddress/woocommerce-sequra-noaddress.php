<?php
/*
  Plugin Name: SeQura No Address
  Plugin URI: http://sequra.es/
  Description: Parche para que sea SeQura quien pida las direcciones.
  Version: 1.0.0
  Author: SeQura Engineering
  Author URI: http://SeQura.es/
 */

/**
 * Add the gateway to woocommerce
 * */
function sequra_noaddress_set_builder_class( $builderClass ) {
	if ( ! class_exists( 'SequraNABuilder' ) ) {
		require_once( WP_PLUGIN_DIR . "/" . plugin_basename( dirname( __FILE__ ) ) . '/' . 'SequraNABuilder.php' );
	}
	return 'SequraNABuilder';
}
add_filter( 'sequra_set_builder_class', 'sequra_noaddress_set_builder_class' );