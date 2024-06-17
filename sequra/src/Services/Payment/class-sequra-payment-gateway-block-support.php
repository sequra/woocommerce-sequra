<?php
/**
 * Provide compatibility with Gutenberg blocks for the payment gateway
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Payment;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use SeQura\WC\Services\Interface_Logger_Service;
use Throwable;

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
	 * Assets directory path.
	 *
	 * @var string
	 */
	private $assets_dir_path;

	/**
	 * Assets URL.
	 *
	 * @var string
	 */
	private $assets_url;

	/**
	 * Payment service.
	 *
	 * @var Interface_Payment_Service
	 */
	private $payment_service;

	/**
	 * Logger service.
	 *
	 * @var Interface_Logger_Service
	 */
	private $logger;

	/**
	 * Constructor
	 */
	public function __construct( 
		string $assets_dir_path, 
		string $assets_url, 
		Interface_Payment_Service $payment_service,
		Interface_Logger_Service $logger
	) {
		$this->assets_dir_path = $assets_dir_path;
		$this->assets_url      = $assets_url;
		$this->payment_service = $payment_service;
		$this->logger          = $logger;
		$this->name            = 'sequra';
	}

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

		$asset_path = "{$this->assets_dir_path}/js/dist/block/payment-gateway.min.asset.php";
		$asset_url  = "{$this->assets_url}/js/dist/block/payment-gateway.min.js";
	
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
		$payment_methods = array();
		try {
			$payment_methods = $this->payment_service->get_payment_methods();
		} catch ( Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
		}
		return array(
			'title'          => 'seQura',
			'description'    => 'Select the payment method you want to use',
			'paymentMethods' => $payment_methods,
			'icon'           => 'https://cdn.prod.website-files.com/62b803c519da726951bd71c2/62b803c519da72c35fbd72a2_Logo.svg',
		
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
