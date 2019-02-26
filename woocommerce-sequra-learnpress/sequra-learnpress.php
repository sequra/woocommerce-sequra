<?php
/*
  Plugin Name: Adaptador módulo Sequra Woocommerce - LearnPress
  Plugin URI: http://sequra.es/
  Description: Da la opción a tus clientes usar los servicios de Sequra para pagar.
  Version: 1.0.0
  Author: Sequra Engineering
  Author URI: http://Sequra.es/
 */

/**
 * Add the gateway to woocommerce
 * */
function sequra_set_builder_class_learnpress( $classname ) {
	if ( ! class_exists( 'SequraBuilderLP' ) ) {
		require_once( WP_PLUGIN_DIR . "/" . plugin_basename( dirname( __FILE__ ) ) . 
			'/SequraBuilderLP.php' );
	}
	return 'SequraBuilderLP';
}
add_filter( 'sequra_set_builder_class', 'sequra_set_builder_class_learnpress' );