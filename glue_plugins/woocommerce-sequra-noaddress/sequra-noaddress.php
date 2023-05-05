<?php
/**
 * Plugin Name: Adaptador módulo SeQura Woocommerce - LearnPress
 * Plugin URI: http://sequra.es/
 * Description: Parche para que sea SeQura quien pida las direcciones.
 * Version: 2.0.0
 * Author: SeQura Engineering
 * Author URI: http://Sequra.es/
 *
 * @package sequra-noaddress
 */

add_action( 'woocommerce_sequra_plugin_loaded', 'woocommerce_sequranoaddress_init', 120 );

/**
 * Init plugin
 */
function woocommerce_sequranoaddress_init() {
	require_once WC_SEQURA_PLG_PATH . '/lib/wp-package-updater/class-wp-package-updater.php';

	$noaddress_updater = new WP_Package_Updater(
		get_option( 'sequra_plugin_update_server', SEQURA_PLUGIN_UPDATE_SERVER ),
		wp_normalize_path( __FILE__ ),
		wp_normalize_path( plugin_dir_path( __FILE__ ) )
	);

	/**
	 * Set the builder class for no address case.
	 *
	 * @param string $builder_class the class name.
	 * */
	function sequra_noaddress_set_builder_class( $builder_class ) {
		if ( ! class_exists( 'SequraNABuilder' ) ) {
			require_once WP_PLUGIN_DIR . '/' . plugin_basename( dirname( __FILE__ ) ) . '/class-sequranabuilder.php';
		}
		return 'SequraNABuilder';
	}
	add_filter( 'sequra_set_builder_class', 'sequra_noaddress_set_builder_class' );
}
