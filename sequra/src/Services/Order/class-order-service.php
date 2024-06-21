<?php
/**
 * Order service
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Order;

use SeQura\WC\Dto\Delivery_Method;
use SeQura\WC\Dto\Previous_Order;
use WC_Order;

/**
 * Handle use cases related to Order
 */
class Order_Service implements Interface_Order_Service {
	
	/**
	 * Get delivery method
	 */
	public function get_delivery_method( ?WC_Order $order ): Delivery_Method {
		if ( ! $order ) {
			return $this->get_shipping_method_from_session();    
		}
		
		$shipping_methods = $order->get_shipping_methods();
		$shipping_method  = current( $shipping_methods );

		return new Delivery_Method(
			! empty( $shipping_method['name'] ) ? $shipping_method['name'] : 'default',
			! empty( $shipping_method['method_id'] ) ? $shipping_method['method_id'] : 'default'
		);
	}

	/**
	 * Get client first name. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_first_name( ?WC_Order $order, $is_delivery = true ): string {
		$prefix = $is_delivery ? 'shipping' : 'billing';
		return $this->get_customer_field( $order, "get_{$prefix}_first_name", 'get_billing_first_name', "{$prefix}_first_name", 'first_name' );
	}

	/**
	 * Get client last name. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_last_name( ?WC_Order $order, $is_delivery = true ): string {
		$prefix = $is_delivery ? 'shipping' : 'billing';
		return $this->get_customer_field( $order, "get_{$prefix}_last_name", 'get_billing_last_name', "{$prefix}_last_name", 'last_name' );
	}

	/**
	 * Get client company. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_company( ?WC_Order $order, $is_delivery = true ): string {
		$prefix = $is_delivery ? 'shipping' : 'billing';
		return $this->get_customer_field( $order, "get_{$prefix}_company", 'get_billing_company', "{$prefix}_company", 'company' );
	}

	/**
	 * Get client address's first line. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_address_1( ?WC_Order $order, $is_delivery = true ): string {
		$prefix = $is_delivery ? 'shipping' : 'billing';
		return $this->get_customer_field( $order, "get_{$prefix}_address_1", 'get_billing_address_1', "{$prefix}_address_1", 'address_1' );
	}

	/**
	 * Get client address's second line. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_address_2( ?WC_Order $order, $is_delivery = true ): string {
		$prefix = $is_delivery ? 'shipping' : 'billing';
		return $this->get_customer_field( $order, "get_{$prefix}_address_2", 'get_billing_address_2', "{$prefix}_address_2", 'address_2' );
	}

	/**
	 * Get client postcode. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_postcode( ?WC_Order $order, $is_delivery = true ): string {
		$prefix = $is_delivery ? 'shipping' : 'billing';
		return $this->get_customer_field( $order, "get_{$prefix}_postcode", 'get_billing_postcode', "{$prefix}_postcode", 'postcode' );
	}

	/**
	 * Get client city. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_city( ?WC_Order $order, $is_delivery = true ): string {
		$prefix = $is_delivery ? 'shipping' : 'billing';
		$city   = $this->get_customer_field( $order, "get_{$prefix}_city", 'get_billing_city', "{$prefix}_city", 'city' );
		if ( ! $city ) {
			$city = $this->get_customer_field( $order, 'get_city', 'get_city', 'city', 'city' );
		}
		return $city;
	}

	/**
	 * Get client country code. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_country( ?WC_Order $order, $is_delivery = true ): string {
		$prefix = $is_delivery ? 'shipping' : 'billing';
		return $this->get_customer_field( $order, "get_{$prefix}_country", 'get_billing_country', "{$prefix}_country", 'country' );
	}

	/**
	 * Get client state. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_state( ?WC_Order $order, $is_delivery = true ): string {
		$prefix     = $is_delivery ? 'shipping' : 'billing';
		$state_code = $this->get_customer_field( $order, "get_{$prefix}_state", 'get_billing_state', "{$prefix}_state", 'state' );
		if ( ! $state_code ) {
			return '';
		}
		$states = WC()->countries->get_states( $this->get_country( $order ) );
		if ( ! $states || ! isset( $states[ $state_code ] ) ) {
			return '';
		}
		return $states[ $state_code ];
	}

	/**
	 * Get client phone. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_phone( ?WC_Order $order, $is_delivery = true ): string {
		$prefix = $is_delivery ? 'shipping' : 'billing';
		return $this->get_customer_field( $order, "get_{$prefix}_phone", 'get_billing_phone', "{$prefix}_phone", 'phone' );
	}

	/**
	 * Get client email. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_email( ?WC_Order $order ): string {
		return $this->get_customer_field( $order, 'get_billing_email', 'get_billing_email', 'email', 'email' );
	}

	/**
	 * TODO: Review this with Mikel. I need to know what is the origin of the VAT number field because it is not a default field in WooCommerce.
	 * Get client vat number. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_vat( ?WC_Order $order, $is_delivery = true ): string {
		$prefix = $is_delivery ? 'shipping' : 'billing';
		$vat    = $this->get_customer_field( $order, "get_{$prefix}_nif", 'get_billing_nif', "{$prefix}_nif", 'nif' );
		if ( ! $vat ) {
			$vat = $this->get_customer_field( $order, 'get_nif', 'get_nif', 'nif', 'nif' );
		}
		if ( ! $vat ) {
			$vat = $this->get_customer_field( $order, "get_{$prefix}_vat", 'get_billing_vat', "{$prefix}_vat", 'vat' );
		}
		
		return $vat;
	}

	/**
	 * Get previous orders
	 * 
	 * @return array<array<string, mixed>>
	 */
	public function get_previous_orders( int $customer_id ): array {
		$previous_orders = array();

		/**
		 * Get previous orders
		 *
		 * @var WC_Order[] $previous_orders
		 */
		$orders = wc_get_orders(
			array(
				'limit'    => -1,
				'customer' => $customer_id,
				'status'   => array( 'wc-processing', 'wc-completed' ), // TODO: Does this should use the status from the plugin settings?
			) 
		);

		if ( is_array( $orders ) ) {
			foreach ( $orders as $order ) {
				$previous_orders[] = Previous_Order::from_order( $order )->to_array();
			}
		}
		
		return $previous_orders;
	}

	/**
	 * Get customer data from order, session or POST. If not found, return an empty string.
	 */
	private function get_customer_field( 
		?WC_Order $order, 
		string $order_func, 
		string $order_alt_func, 
		string $session_key, 
		string $session_alt_key
	): string {
		if ( ! $order ) {
			$customer = $this->get_customer_from_session();
			return ! empty( $customer[ $session_key ] ) ? $customer[ $session_key ] : (
				$session_alt_key !== $session_key && ! empty( $customer[ $session_alt_key ] ) ? $customer[ $session_alt_key ] : ''
			);
		}
		$value = '';
		if ( method_exists( $order, $order_func ) ) {
			$value = call_user_func( array( $order, $order_func ) );
		} 
		
		if ( ! $value && $order_alt_func !== $order_func && method_exists( $order, $order_alt_func ) ) {
			$value = call_user_func( array( $order, $order_alt_func ) );
		}

		return $value;
	}

	/**
	 * Get customer data from session. If not found, return an empty array.
	 */
	private function get_customer_from_session(): array {
		return WC()->session ? WC()->session->get( 'customer' ) : array();
	}

	/**
	 * Get shipping method from session.
	 *
	 * @return string
	 */
	private function get_shipping_method_from_session() {
		$shipping_methods = WC()->session->chosen_shipping_methods;

		if ( ! $shipping_methods ) {
			$shipping_methods = array();
		}
		$package = current( WC()->shipping->get_packages() );
		if ( $package && isset( $package['rates'][ current( $shipping_methods ) ] ) ) {
			$rate = $package['rates'][ current( $shipping_methods ) ];
			return new Delivery_Method(
				$rate->label,
				$rate->id
			);
		}
		return new Delivery_Method(
			'default',
			'default'
		);
	}
}
