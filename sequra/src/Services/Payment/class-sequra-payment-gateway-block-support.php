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
use Error;

/**
 * Provide compatibility with Gutenberg blocks for the payment gateway
 */
class Sequra_Payment_Gateway_Block_Support extends AbstractPaymentMethodType {

	private const PAYMENT_METHOD_CONTENT_ACTION = 'sequra_payment_method_content';
	
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

		// Register the AJAX actions for handle block content.
		add_action( 'wp_ajax_' . self::PAYMENT_METHOD_CONTENT_ACTION, array( $this, 'handle_get_payment_method_block_content' ) );
		add_action( 'wp_ajax_nopriv_' . self::PAYMENT_METHOD_CONTENT_ACTION, array( $this, 'handle_get_payment_method_block_content' ) );
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
	 * Return the block content if any.
	 */
	public function handle_get_payment_method_block_content(): void {

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$country   = isset( $_POST['country'] ) ? sanitize_text_field( $_POST['country'] ) : '';
		$requestId = isset( $_POST['requestId'] ) ? sanitize_text_field( $_POST['requestId'] ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		ob_start();
		$this->gateway->payment_fields();
		$payment_fields = ob_get_clean();

		wp_send_json(
			array(
				'content'   => $payment_fields,
				'requestId' => (int) $requestId,
				'country'   => $country ?? '',
			),
			200,
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_LINE_TERMINATORS
		);

		// $this->gateway->payment_fields();
		// wp_die();
	}

	/**
	 * Provide all the necessary data to use on the front-end as an associative array.
	 */
	public function get_payment_method_data(): array {
		// ob_start();
		// $this->gateway->payment_fields();
		// $payment_fields = ob_get_clean();
		return array(
			'blockContentUrl'        => admin_url( 'admin-ajax.php' ),
			'blockContentAjaxAction' => self::PAYMENT_METHOD_CONTENT_ACTION,
			'title'                  => $this->gateway->get_title(),
			// 'description' => $payment_fields,
			'description'            => '',
		);
	}
}
