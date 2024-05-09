<?php
/**
 * The plugin bootstrap file
 * TODO: All texts MUST be in English
 *
 * @package           Sequra/WC
 *
 * @wordpress-plugin
 * Plugin Name:       seQura
 * Plugin URI:        https://sequra.es/
 * Description:       Ofrece las opciones de pago con seQura
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
 * WC requires at least: 4.0
 * WC tested up to: 8.2.2
 */

defined( 'WPINC' ) || die;

require plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

call_user_func(
	function () {

		$builder = new \DI\ContainerBuilder();
		$builder->useAutowiring( true );
		$builder->useAnnotations( false );
		$definitions = require_once plugin_dir_path( __FILE__ ) . 'di-config.php';
		$builder->addDefinitions( $definitions );
		$container = $builder->build();

		$plugin = $container->get( \Sequra\WC\Plugin::class );

		register_activation_hook( __FILE__, array( $plugin, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $plugin, 'deactivate' ) );
	}
);
