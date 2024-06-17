<?php
/**
 * Payment service interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Payment;

use SeQura\Core\Infrastructure\ServiceRegister;
use WC_Payment_Gateway;

/**
 * Handle use cases related to payments
 */
class Sequra_Payment_Gateway extends WC_Payment_Gateway {

	/**
	 * Payment service
	 *
	 * @var Interface_Payment_Service
	 */
	private $payment_service;

	/**
	 * Templates path
	 *
	 * @var string
	 */
	private $templates_path;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->payment_service = ServiceRegister::getService( Interface_Payment_Service::class );
		$this->templates_path  = ServiceRegister::getService( 'plugin.templates_path' );
		$this->id              = 'sequra'; // payment gateway plugin ID.
		$this->icon            = 'https://cdn.prod.website-files.com/62b803c519da726951bd71c2/62b803c519da72c35fbd72a2_Logo.svg'; // URL of the icon that will be displayed on checkout page near your gateway name.
		$this->has_fields      = true; // in case you need a custom credit card form.
		// BE.
		$this->method_title       = 'seQura';
		$this->method_description = 'seQura payment gateway';
		// FE.
		$this->title       = 'seQura';
		$this->description = 'seQura payment gateway';

		$this->supports = array(
			'products',
		);

		// Method with all the options fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();
		$this->enabled = $this->get_option( 'enabled', 'no' );
	}
	
	/**
	 * Plugin options, we deal with it in Step 3 too
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'       => 'Enable/Disable',
				'label'       => 'Enable seQura',
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
		);
	}

	/**
	 * Payment fields
	 */
	public function payment_fields() {
		$args = array(
			'description'     => 'Select the payment method you want to use',
			'payment_methods' => $this->payment_service->get_payment_methods(),
		);
		wc_get_template( 'front/payment_fields.php', $args, '', $this->templates_path );
	}

	/**
	 * Validate frontend fields.
	 *
	 * Validate payment fields on the frontend.
	 *
	 * @return bool
	 */
	public function validate_fields() {
		$prod     = $this->get_posted_product();
		$campaign = $this->get_posted_campaign();

		foreach ( $this->payment_service->get_payment_methods() as $pm ) {
			if ( $pm['product'] === $prod && $pm['campaign'] === $campaign ) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Process payment
	 * 
	 * @param int $order_id
	 * 
	 * @return array
	 */
	public function process_payment( $order_id ) { 
		$response = array(
			'result'   => 'failure',
			'redirect' => wc_get_checkout_url(),
		);

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return $response;
		}
		// TODO: review the meta keys names. Move this to the payment service.
		$order->update_meta_data( '_sequra_product', $this->get_posted_product() );
		$order->update_meta_data( '_sequra_campaign', $this->get_posted_campaign() );
		$order->save();

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * Get seQura product from POST
	 */
	private function get_posted_product(): ?string {
		//phpcs:ignore WordPress.Security.NonceVerification.Missing
		return ! isset( $_POST['sequra_product'] ) || ! is_string( $_POST['sequra_product'] ) ? null : trim( sanitize_text_field( $_POST['sequra_product'] ) );
	}

	/**
	 * Get seQura campaign from POST
	 */
	private function get_posted_campaign(): ?string {
		//phpcs:ignore WordPress.Security.NonceVerification.Missing
		return ! isset( $_POST['sequra_campaign'] ) || ! is_string( $_POST['sequra_campaign'] ) ? null : trim( sanitize_text_field( $_POST['sequra_campaign'] ) );
	}
	
	/**
	 * Webhook
	 */
	public function webhook() {
		// TODO: Implement webhook() method.
	}
}
