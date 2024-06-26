<?php
/**
 * Order service
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Order;

use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\DeliveryMethod;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\PreviousOrder;
use SeQura\WC\Dto\Cart_Info;
use SeQura\WC\Dto\Delivery_Method;
use SeQura\WC\Dto\Payment_Method_Data;
use SeQura\WC\Services\Payment\Interface_Payment_Service;
use SeQura\WC\Services\Pricing\Interface_Pricing_Service;
use WC_DateTime;
use WC_Order;

/**
 * Handle use cases related to Order
 */
class Order_Service implements Interface_Order_Service {

	private const META_KEY_METHOD_TITLE    = '_sq_method_title';
	private const META_KEY_PRODUCT         = '_sq_product';
	private const META_KEY_CAMPAIGN        = '_sq_campaign';
	private const META_KEY_CART_REF        = '_sq_cart_ref';
	private const META_KEY_CART_CREATED_AT = '_sq_cart_created_at';

	/**
	 * Payment service
	 *
	 * @var Interface_Payment_Service
	 */
	private $payment_service;

	/**
	 * Pricing service
	 *
	 * @var Interface_Pricing_Service
	 */
	private $pricing_service;

	/**
	 * Constructor
	 */
	public function __construct( 
		Interface_Payment_Service $payment_service,
		Interface_Pricing_Service $pricing_service
	) {
		$this->payment_service = $payment_service;
		$this->pricing_service = $pricing_service;
	}
	
	/**
	 * Get delivery method
	 */
	public function get_delivery_method( ?WC_Order $order ): DeliveryMethod {

		if ( ! $order ) {
			$session          = WC()->session;
			$shipping_methods = $session ? WC()->session->chosen_shipping_methods : array();
			
			if ( ! $shipping_methods || empty( WC()->shipping->get_packages() ) ) {
				return new DeliveryMethod( 'default', null, 'default' );
			}
			$package         = current( WC()->shipping->get_packages() );
			$shipping_method = current( $shipping_methods );
			
			if ( ! isset( $package['rates'][ $shipping_method ] ) ) {
				return new DeliveryMethod( 'default', null, 'default' );
			}
			
			$rate = $package['rates'][ $shipping_method ];
			return new DeliveryMethod( $rate->label, null, $rate->id );
		}
		
		$shipping_methods = current( $order->get_shipping_methods() );

		return new DeliveryMethod(
			$shipping_method['name'] ?? 'default',
			null,
			$shipping_method['method_id'] ?? 'default'
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
	 * @return PreviousOrder[]
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
				/**
				 * Order date
				 *
				 * @var WC_DateTime $date
				 */
				$date     = $order->get_date_created( 'edit' );
				$postcode = $this->get_postcode( $order );
				$country  = $this->get_country( $order );

				$previous_orders[] = new PreviousOrder(
					$date ? $date->date( 'c' ) : '',
					$this->pricing_service->to_cents( (float) $order->get_total( 'edit' ) ),
					$order->get_currency(),
					$order->get_status( 'edit' ),
					wc_get_order_status_name( $order->get_status( 'edit' ) ),
					$order->get_payment_method( 'edit' ),
					$order->get_payment_method_title( 'edit' ),
					$postcode ? $postcode : null,
					$country ? $country : null
				);
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
	 * Get order meta value by key from a seQura order.
	 * If the order is not a seQura order an empty string is returned.
	 */
	private function get_order_meta( WC_Order $order, $meta_key ): string {
		if ( $order->get_payment_method() !== $this->payment_service->get_payment_gateway_id() ) {
			return '';
		}
		return strval( $order->get_meta( $meta_key, true ) );
	}

	/**
	 * Get the seQura payment method title for the order.
	 * If the order is not a seQura order an empty string is returned.
	 */
	public function get_payment_method_title( WC_Order $order ): string {
		return $this->get_order_meta( $order, self::META_KEY_METHOD_TITLE );
	}

	/**
	 * Get the seQura product for the order.
	 * If the value is not found an empty string is returned.
	 */
	public function get_product( WC_Order $order ): string {
		return $this->get_order_meta( $order, self::META_KEY_PRODUCT );
	}

	/**
	 * Get the seQura campaign for the order.
	 * If the value is not found an empty string is returned.
	 */
	public function get_campaign( WC_Order $order ): string {
		return $this->get_order_meta( $order, self::META_KEY_CAMPAIGN );
	}

	/**
	 * Get the seQura cart info for the order.
	 * If the value is not found null is returned.
	 */
	public function get_cart_info( WC_Order $order ): ?Cart_Info {
		return Cart_Info::from_array(
			array(
				'ref'        => $this->get_order_meta( $order, self::META_KEY_CART_REF ),
				'created_at' => $this->get_order_meta( $order, self::META_KEY_CART_CREATED_AT ),
			)
		);
	}

	/**
	 * Get payment gateway webhook identifier
	 */
	public function get_notify_url( WC_Order $order ): string {
		return add_query_arg(
			array(
				'order'  => '' . $order->get_id(),
				'wc-api' => $this->payment_service->get_payment_gateway_webhook(),
			),
			home_url( '/' )
		);
	}

	/**
	 * Get payment gateway webhook identifier
	 */
	public function get_return_url( WC_Order $order ): string {
		return add_query_arg(
			array( 'sq_product' => 'SQ_PRODUCT_CODE' ),
			$this->get_notify_url( $order )
		);
	}

	/**
	 * Save required metadata for the order.
	 * Returns true if the metadata was saved, false otherwise.
	 */
	public function set_order_metadata( WC_Order $order, ?Payment_Method_Data $dto, ?Cart_Info $cart_info ): bool {
		if ( ! $dto ) {
			return false;
		}

		if ( ! $cart_info ) {
			return false;
		}

		$order->update_meta_data( self::META_KEY_PRODUCT, $dto->product );
		if ( ! empty( $dto->campaign ) ) {
			$order->update_meta_data( self::META_KEY_CAMPAIGN, $dto->campaign );
		}
		$order->update_meta_data( self::META_KEY_METHOD_TITLE, $dto->title );

		$order->update_meta_data( self::META_KEY_CART_REF, $cart_info->ref );
		$order->update_meta_data( self::META_KEY_CART_CREATED_AT, $cart_info->created_at );

		$order->save();
		return true;
	}
}
