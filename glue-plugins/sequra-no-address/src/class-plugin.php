<?php
/**
 * The core plugin class.
 *
 * @package    SeQura/WC/NoAddress
 */

namespace SeQura\WC\NoAddress;

use SeQura\Core\Infrastructure\ServiceRegister;
use SeQura\WC\NoAddress\Controller\Hooks\Order\Interface_Order_Controller;

/**
 * The core plugin class.
 */
class Plugin {

	/**
	 * The plugin data.
	 * 
	 * @var array<string, string>
	 */
	private $plugin_data;

	/**
	 * The addon data.
	 * 
	 * @var array<string, string>
	 */
	private $addon_data;

	/**
	 * The addon base name.
	 * 
	 * @var string
	 */
	private $addon_base_name;

	/**
	 * The plugin file path.
	 * 
	 * @var string
	 */
	private $file_path;

	/**
	 * Construct the plugin.
	 * 
	 * @param string $file_path The plugin file path.
	 */
	public function __construct( string $file_path ) {
		$this->file_path = $file_path;
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		// WooCommerce Compat.
		add_action( 'before_woocommerce_init', array( $this, 'declare_woocommerce_compatibility' ) );   
	}

	/**
	 * Initialize the plugin.
	 * 
	 * @return void
	 */
	public function init() {
		if ( ! class_exists( 'SeQura\Core\Infrastructure\ServiceRegister' ) ) {
			$this->show_error( 'seQura - No Address Addon requires seQura to be installed and active.' );
			return;
		}

		\SeQura\WC\NoAddress\Bootstrap::init();

		$this->plugin_data     = (array) ServiceRegister::getService( 'plugin.data' );
		$this->addon_data      = (array) ServiceRegister::getService( 'noaddress_addon.data' );
		$this->addon_base_name = strval( ServiceRegister::getService( 'noaddress_addon.basename' ) );
		
		if ( ! $this->check_dependencies() ) {
			return;
		}
		/**
		 * Order controller
		 *
		 * @var Interface_Order_Controller $order_controller
		 */
		$order_controller = ServiceRegister::getService( Interface_Order_Controller::class );
		add_filter( 'sequra_create_order_request_merchant_options', array( $order_controller, 'add_no_address' ) );
		add_filter( 'sequra_platform_options_version', array( $this, 'update_platform_version' ) );
	}

	/**
	 * Show an admin notice with an error message.
	 * 
	 * @param string $msg The error message.
	 */
	public function show_error( string $msg ) {
		add_action(
			'admin_notices',
			function () use ( $msg ) {
				?>
			<div class="notice notice-error"><p><?php echo wp_kses_post( $msg ); ?></p></div>
				<?php
			} 
		);
	}

	/**
	 * Add the addon version to the platform version.
	 * 
	 * @param string $version Original platform version.
	 * @return string
	 */
	public function update_platform_version( $version ) {
		return sprintf( '%s + %s %s', $version, $this->addon_data['Name'], $this->addon_data['Version'] );
	}

	/**
	 * Handle activation of the plugin.
	 */
	private function check_dependencies(): bool {
		if ( version_compare( PHP_VERSION, $this->addon_data['RequiresPHP'], '<' ) ) {
			$this->show_error( $this->addon_data['Name'] . ' requires PHP ' . $this->addon_data['RequiresPHP'] . ' or greater.' );
			deactivate_plugins( $this->addon_base_name );
			return false;
		}

		global $wp_version;
		if ( version_compare( $wp_version, $this->addon_data['RequiresWP'], '<' ) ) {
			$this->show_error( $this->addon_data['Name'] . ' requires WordPress ' . $this->addon_data['RequiresWP'] . ' or greater.' );
			deactivate_plugins( $this->addon_base_name );
			return false;
		}

		if ( ! defined( 'WC_VERSION' ) || version_compare( WC_VERSION, $this->plugin_data['RequiresWC'], '<' ) ) {
			$this->show_error( $this->addon_data['Name'] . ' requires WooCommerce ' . $this->plugin_data['RequiresWC'] . ' or greater.' );
			deactivate_plugins( $this->addon_base_name );
			return false;
		}

		if ( version_compare( $this->plugin_data['Version'], $this->addon_data['RequiresSQ'], '<' ) ) {
			$this->show_error( $this->addon_data['Name'] . ' requires seQura ' . $this->addon_data['RequiresSQ'] . ' or greater.' );
			deactivate_plugins( $this->addon_base_name );
			return false;
		}
		return true;
	}

	/**
	 * Declare WooCommerce compatibility.
	 */
	public function declare_woocommerce_compatibility() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', $this->file_path, true );
		}
	}
}
