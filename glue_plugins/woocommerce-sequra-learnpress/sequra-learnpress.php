<?php
/**
 * Plugin Name: Adaptador módulo SeQura Woocommerce - LearnPress
 * Plugin URI: http://sequra.es/
 * Description: Da la opción a tus clientes usar los servicios de SeQura para pagar.
 * Version: 2.0.0
 * Author: SeQura Engineering
 * Author URI: http://Sequra.es/
 *
 * @package sequra-learnpress
 */

add_action( 'woocommerce_sequra_plugin_loaded', 'woocommerce_sequralearnpress_init', 130 );

/**
 * Init plugin
 */
function woocommerce_sequralearnpress_init() {
	/**
	 * Set the builder class for LearnPress.
	 *
	 * @param string $classname the class name.
	 * */
	function sequra_set_builder_class_learnpress( $classname ) {
		if ( ! class_exists( 'SequraBuilderLP' ) ) {
			require_once WP_PLUGIN_DIR . '/' . plugin_basename( dirname( __FILE__ ) ) . '/class-sequrabuilderlp.php';
		}
		return 'SequraBuilderLP';
	}
	add_filter( 'sequra_set_builder_class', 'sequra_set_builder_class_learnpress' );
}
