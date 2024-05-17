<?php
/**
 * The plugin bootstrap file
 *
 * @package SeQura/WC
 *
 * @wordpress-plugin
 * Plugin Name:       seQura
 * Plugin URI:        https://sequra.es/
 * Description:       seQura payment gateway for WooCommerce
 * Version:           3.0.0
 * Author:            "seQura Tech" <wordpress@sequra.com>
 * Author URI:        https://sequra.com/
 * License:           GPL-3.0+
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       sequra
 * Domain Path:       /languages
 * Requires PHP:      7.3
 * Requires at least: 5.9
 * Tested up to:      6.5.2
 * WC requires at least: 4.7.0
 * WC tested up to: 8.2.2
 * Requires Plugins:  woocommerce
 */

defined( 'WPINC' ) || die;

require plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

call_user_func(
	function () {

		// $builder = new \DI\ContainerBuilder();
		// $builder->useAutowiring( true );
		// $builder->useAnnotations( false );
		// $definitions = include_once plugin_dir_path( __FILE__ ) . 'di-config.php';
		// $builder->addDefinitions( $definitions );
		// $container = $builder->build();

		// $plugin = $container->get( \SeQura\WC\Plugin::class );

		\SeQura\WC\Bootstrap::init();
		$plugin = \SeQura\Core\Infrastructure\ServiceRegister::getService( \SeQura\WC\Plugin::class );

		register_activation_hook( __FILE__, array( $plugin, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $plugin, 'deactivate' ) );
	}
);
