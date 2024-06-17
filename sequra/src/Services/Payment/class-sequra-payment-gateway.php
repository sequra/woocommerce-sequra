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
	 * Constructor
	 */
	public function __construct() {
		$this->payment_service = ServiceRegister::getService( Interface_Payment_Service::class );
		$this->id              = 'sequra'; // payment gateway plugin ID.
		$this->icon            = ''; // URL of the icon that will be displayed on checkout page near your gateway name.
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
	
	public function process_payment( $order_id ) { 
		// TODO: Implement process_payment() method.
		// get order by id
		$order = wc_get_order( $order_id );
		// return array(
		// 'result'   => 'success',
		// 'redirect' => $this->get_return_url( $order ),
		// );

		// force failure for testing.
		return array(
			'result'   => 'failure',
			'redirect' => wc_get_checkout_url(),
		);
	}
	
	public function webhook() {
		// TODO: Implement webhook() method.
	}
}
