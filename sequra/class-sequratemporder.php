<?php
/**
 * SeQura Temp Order class.
 *
 * @package woocommerce-sequra
 */

/**
 * SeQura Temp Order Class
 * Stores the checkout information temporarily in a format similar to WC_Order.
 * */
class SequraTempOrder {
	/**
	 * Data array
	 *
	 * @var array
	 */
	public $data = array();
	/**
	 * Constructor
	 *
	 * @param array $post_data Data submited during checkout.
	 */
	public function __construct( $post_data ) {
		parse_str( $post_data, $this->data );
		if ( !isset( $this->data['ship_to_different_address'] ) || !$this->data['ship_to_different_address']) {
			$this->data['shipping_first_name'] = isset( $this->data['billing_first_name'] ) ? $this->data['billing_first_name']: '';
			$this->data['shipping_last_name'] = isset( $this->data['billing_last_name'] ) ? $this->data['billing_last_name']: '';
			$this->data['shipping_company'] = isset( $this->data['billing_company'] ) ? $this->data['billing_company']: '';
			$this->data['shipping_address_1'] = isset( $this->data['billing_address_1'] ) ? $this->data['billing_address_1']: '';
			$this->data['shipping_address_2'] = isset( $this->data['billing_address_2'] ) ? $this->data['billing_address_2']: '';
			$this->data['shipping_city'] = isset( $this->data['billing_city'] ) ? $this->data['billing_city']: '';
			$this->data['shipping_state'] = isset( $this->data['billing_state'] ) ? $this->data['billing_state']: '';
			$this->data['shipping_postcode'] = isset( $this->data['billing_postcode'] ) ? $this->data['billing_postcode']: '';
			$this->data['shipping_country'] = isset( $this->data['billing_country'] ) ? $this->data['billing_country']: '';
		}
		$this->shipping_first_name = isset( $this->data['shipping_first_name'] ) ? $this->data['shipping_first_name'] : '';
		$this->shipping_last_name  = isset( $this->data['shipping_last_name'] ) ? $this->data['shipping_last_name'] : '';
		$this->shipping_company    = isset( $this->data['shipping_company'] ) ? $this->data['shipping_company'] : '';
		$this->shipping_address_1  = isset( $this->data['shipping_address_1'] ) ? $this->data['shipping_address_1'] : '';
		$this->shipping_address_2  = isset( $this->data['shipping_address_2'] ) ? $this->data['shipping_address_2'] : '';
		$this->shipping_city       = isset( $this->data['shipping_city'] ) ? $this->data['shipping_city'] : '';
		$this->shipping_state      = isset( $this->data['shipping_state'] ) ? $this->data['shipping_state'] : '';
		$this->shipping_postcode   = isset( $this->data['shipping_postcode'] ) ? $this->data['shipping_postcode'] : '';
		$this->shipping_country    = isset( $this->data['shipping_country'] ) ? $this->data['shipping_country'] : '';
		$this->status              = null;
	}
	// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
	/**
	 * Magic function
	 *
	 * @param string $name       Function name.
	 * @param array  $arguments  Functions args.
	 * @return mixed
	 */
	public function __call( $name, $arguments ) {
		if ( isset( $this->data[ substr( $name, 4 ) ] ) ) {
			return $this->data[ substr( $name, 4 ) ];
		}
	}
	// phpcs:enable
}
