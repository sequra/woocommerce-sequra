<?php
/**
 * The core plugin class.
 *
 * @package    SeQura/WC/LearnPress
 */

namespace SeQura\WC\LearnPress;

use SeQura\Core\Infrastructure\ServiceRegister;

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
	 * Construct the plugin.
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Initialize the plugin.
	 */
	public function init(): void {
		if ( ! class_exists( 'SeQura\Core\Infrastructure\ServiceRegister' ) ) {
			$this->show_error( 'seQura - LearnPress requires seQura to be installed and active.' );
			return;
		}

		\SeQura\WC\LearnPress\Bootstrap::init();

		$this->plugin_data     = (array) ServiceRegister::getService( 'plugin.data' );
		$this->addon_data      = (array) ServiceRegister::getService( 'lp_addon.data' );
		$this->addon_base_name = strval( ServiceRegister::getService( 'lp_addon.basename' ) );
		
		if ( ! $this->check_dependencies() ) {
			return;
		}
		// TODO: Bind controllers with hooks.
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

		if ( ! defined( 'LEARNPRESS_VERSION' ) || version_compare( LEARNPRESS_VERSION, $this->addon_data['RequiresLP'], '<' ) ) {
			$this->show_error( $this->addon_data['Name'] . ' requires LearnPress ' . $this->addon_data['RequiresLP'] . ' or greater.' );
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
	 * Handle deactivation of the plugin.
	 */
	public function deactivate(): void {
	}
}
