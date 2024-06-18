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

	private const FORM_FIELD_ENABLED = 'enabled';
	private const FORM_FIELD_TITLE   = 'title';
	private const FORM_FIELD_DESC    = 'description';

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

		/**
		 * Action hook to allow plugins to run when the class is loaded.
		 * TODO: is this still needed?
		 *
		 * @since 2.0.0
		 */
		do_action( 'woocommerce_sequra_before_load', $this );

		$this->payment_service = ServiceRegister::getService( Interface_Payment_Service::class );
		$this->templates_path  = ServiceRegister::getService( 'plugin.templates_path' );
		$this->id              = 'sequra';
		// TODO: URL of the icon that will be displayed on checkout page near your gateway name.
		$this->icon               = 'https://cdn.prod.website-files.com/62b803c519da726951bd71c2/62b803c519da72c35fbd72a2_Logo.svg'; 
		$this->has_fields         = true;
		$this->method_title       = __( 'seQura', 'sequra' );
		$this->method_description = __( 'seQura payment method\'s configuration', 'sequra' );

		$this->supports = array(
			'products',
		);

		$this->init_form_fields();
		$this->init_settings();
		$this->enabled     = $this->get_form_field_value( self::FORM_FIELD_ENABLED );
		$this->title       = $this->get_form_field_value( self::FORM_FIELD_TITLE ); // Title of the payment method shown on the checkout page.
		$this->description = $this->get_form_field_value( self::FORM_FIELD_DESC ); // Description of the payment method shown on the checkout page.

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		/**
		 * Action hook to allow plugins to run when the class is loaded.
		 * TODO: is this still needed?
		 * 
		 * @since 2.0.0 
		 */
		do_action( 'woocommerce_sequra_loaded', $this );
	}

	/**
	 * Helper to read the value of a field from the configuration form
	 * or the default value if not set
	 */
	private function get_form_field_value( string $field_name ) {
		$default = isset( $this->form_fields[ $field_name ]['default'] ) ? $this->form_fields[ $field_name ]['default'] : null;
		return $this->get_option( $field_name, $default );
	}
	
	/**
	 * Declare fields for the configuration form
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			self::FORM_FIELD_ENABLED => array(
				'title'       => 'Enable/Disable',
				'label'       => 'Enable seQura',
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			self::FORM_FIELD_TITLE   => array(
				'title'       => __( 'Title', 'sequra' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'sequra' ),
				'default'     => __( 'Flexible payment with seQura', 'sequra' ),
			),
			self::FORM_FIELD_DESC    => array(
				'title'       => __( 'Description', 'sequra' ),
				'type'        => 'text',
				'description' => __( 'This controls the description which the user sees during checkout.', 'sequra' ),
				'default'     => __( 'Please, select the payment method you want to use', 'sequra' ),
			),
		);
	}

	/**
	 * Declare fields for the payment method in the checkout page
	 */
	public function payment_fields() {
		$args = array(
			'description'     => 'Select the payment method you want to use',
			'payment_methods' => $this->payment_service->get_payment_methods(),
		);
		wc_get_template( 'front/payment_fields.php', $args, '', $this->templates_path );
	}

	/**
	 * Validate fields for the payment method in the checkout page
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
