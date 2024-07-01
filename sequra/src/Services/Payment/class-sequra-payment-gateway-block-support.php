<?php
/**
 * Provide compatibility with Gutenberg blocks for the payment gateway
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Payment;

if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
	return;
}

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
	 * Version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Constructor
	 */
	public function __construct( 
		string $assets_dir_path, 
		string $assets_url,
		string $version
	) {
		$this->assets_dir_path = $assets_dir_path;
		$this->assets_url      = $assets_url;
		$this->name            = 'sequra';
		$this->version         = $version;
	}

	/**
	 * Initialization
	 */
	public function initialize() {
		$this->settings = get_option( "woocommerce_{$this->name}_settings", array() );
		$gateways       = WC()->payment_gateways->payment_gateways();
		$this->gateway  = $gateways[ $this->name ];
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
		$version      = isset( $asset['version'] ) ? $asset['version'] : $this->version;
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
		ob_start();
		$this->gateway->payment_fields();
		$payment_fields = ob_get_clean();
		return array(
			'title'       => 'seQura',
			'description' => $payment_fields,
			'icon'        => $this->gateway->icon,
		);
	}
}
