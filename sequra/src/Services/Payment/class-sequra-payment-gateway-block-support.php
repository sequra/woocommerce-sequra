<?php
/**
 * Provide compatibility with Gutenberg blocks for the payment gateway
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Payment;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Provide compatibility with Gutenberg blocks for the payment gateway
 */
class Sequra_Payment_Gateway_Block_Support extends AbstractPaymentMethodType {
	
	/**
	 * Sequra payment gateway instance.
	 *
	 * @var Sequra_Payment_Gateway $gateway
	 */
	private $gateway;
	
	/**
	 * Payment method name defined by payment methods extending this class.
	 *
	 * @var string
	 */
	protected $name = 'sequra';

	/**
	 * Initialization
	 */
	public function initialize() {
		$this->settings = get_option( "woocommerce_{$this->name}_settings", array() );
		
		// you can also initialize your payment gateway here
		// $gateways = WC()->payment_gateways->payment_gateways();
		// $this->gateway  = $gateways[ $this->name ];
	}

	/**
	 * Check if the payment method is active.
	 */
	public function is_active(): bool {
		return ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'];
	}

	/**
	 * Include a JavaScript file which contains the client-side part of the integration.
	 */
	public function get_payment_method_script_handles(): array {

		$asset_path = WC_SEQURA_PLG_PATH . 'assets/js/dist/index.asset.php';
		$asset_url  = plugin_dir_url( __FILE__ ) . '/assets/js/dist/index.js';
	
		$dependencies = array();
		if ( ! file_exists( $asset_path ) ) {
			return array();
		}
		
		$asset        = require $asset_path; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
		$version      = isset( $asset['version'] ) ? $asset['version'] : $version;
		$dependencies = isset( $asset['dependencies'] ) ? $asset['dependencies'] : $dependencies;
	
		wp_register_script( 
			'wc-sequra-blocks-integration', 
			$asset_url, 
			$dependencies, 
			$version, 
			true 
		);

		return array( 'wc-sequra-blocks-integration' );
	}

	/**
	 * Provide all the necessary data to use on the front-end as an associative array.
	 */
	public function get_payment_method_data(): array {
		return array(
			'title'       => 'seQura',
			'description' => 'seQura payment gateway',
			// 'icon'        => plugin_dir_url( __DIR__ ) . 'assets/icon.png',
		
			// if $this->gateway was initialized on line 15
			// 'supports'  => array_filter( $this->gateway->supports, [ $this->gateway, 'supports' ] ),

			// example of getting a public key
			// 'publicKey' => $this->get_publishable_key(),
		);
	}

	// private function get_publishable_key() {
	// $test_mode   = ( ! empty( $this->settings[ 'testmode' ] ) && 'yes' === $this->settings[ 'testmode' ] );
	// $setting_key = $test_mode ? 'test_publishable_key' : 'publishable_key';
	// return ! empty( $this->settings[ $setting_key ] ) ? $this->settings[ $setting_key ] : '';
	// }
}
