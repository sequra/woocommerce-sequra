<?php
/**
 * Order service
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Order;

use Exception;
use SeQura\Core\BusinessLogic\Domain\Multistore\StoreContext;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Address;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Cart;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Customer;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\DeliveryMethod;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\PreviousOrder;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderUpdateData;
use SeQura\Core\BusinessLogic\Domain\Order\OrderStates;
use SeQura\Core\BusinessLogic\Domain\Order\Service\OrderService;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\OrderStatusSettings\Services\Order_Status_Settings_Service;
use SeQura\WC\Core\Extension\Infrastructure\Configuration\Configuration;
use SeQura\WC\Dto\Cart_Info;
use SeQura\WC\Dto\Payment_Method_Data;
use SeQura\WC\Services\Cart\Interface_Cart_Service;
use SeQura\WC\Services\Payment\Interface_Payment_Service;
use SeQura\WC\Services\Pricing\Interface_Pricing_Service;
use Throwable;
use WC_Customer;
use WC_DateTime;
use WC_Order;
use WP_User;

/**
 * Handle use cases related to Order
 */
class Order_Service implements Interface_Order_Service {

	private const META_KEY_METHOD_TITLE    = '_sq_method_title';
	private const META_KEY_PRODUCT         = '_sq_product';
	private const META_KEY_CAMPAIGN        = '_sq_campaign';
	private const META_KEY_CART_REF        = '_sq_cart_ref';
	private const META_KEY_CART_CREATED_AT = '_sq_cart_created_at';
	private const META_KEY_SENT_TO_SEQURA  = '_sq_sent_to_sequra';

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
	 * Order status service
	 *
	 * @var Order_Status_Settings_Service
	 */
	private $order_status_service;

	/**
	 * Configuration
	 *
	 * @var Configuration
	 */
	private $configuration;

	/**
	 * Cart service
	 *
	 * @var Interface_Cart_Service
	 */
	private $cart_service;

	/**
	 * Store context
	 *
	 * @var StoreContext
	 */
	private $store_context;

	/**
	 * Constructor
	 */
	public function __construct( 
		Interface_Payment_Service $payment_service,
		Interface_Pricing_Service $pricing_service,
		Order_Status_Settings_Service $order_status_service,
		Configuration $configuration,
		Interface_Cart_Service $cart_service,
		StoreContext $store_context
	) {
		$this->payment_service      = $payment_service;
		$this->pricing_service      = $pricing_service;
		$this->order_status_service = $order_status_service;
		$this->configuration        = $configuration;
		$this->cart_service         = $cart_service;
		$this->store_context        = $store_context;
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
		
		$shipping_method = current( $order->get_shipping_methods() );

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
	 * Get shopper vat number. If the order is null, attempt to retrieve data from the session.
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
	 * Get shopper NIN number. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_nin( ?WC_Order $order ): ?string {
		/**
		 * TODO: Document this filter
		 * Get NIN number
		 *
		 * @since 3.0.0
		 */
		$nin = apply_filters( 'sequra_get_nin', null, $order );
		return is_string( $nin ) || null === $nin ? $nin : null;
	}

	/**
	 * Get date of birth. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_dob( ?WC_Order $order ): ?string {
		/**
		 * TODO: Document this filter
		 * Get Date of Birth number
		 *
		 * @since 3.0.0
		 */
		$dob = apply_filters( 'sequra_get_dob', null, $order );
		return is_string( $dob ) || null === $dob ? $dob : null;
	}

	/**
	 * Get shopper title. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_shopper_title( ?WC_Order $order ): ?string {
		/**
		 * TODO: Document this filter
		 * Get Shopper title
		 *
		 * @since 3.0.0
		 */
		$title = apply_filters( 'sequra_get_shopper_title', null, $order );
		return is_string( $title ) || null === $title ? $title : null;
	}

	/**
	 * Get shopper created at date. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_shopper_created_at( ?WC_Order $order ): ?string {
		/**
		 * TODO: Document this filter
		 * Get Shopper created at date
		 *
		 * @since 3.0.0
		 */
		$date = apply_filters( 'sequra_get_shopper_created_at', $this->get_shopper_registration_date( $order ), $order );
		return is_string( $date ) || null === $date ? $date : null;
	}

	/**
	 * Get shopper registration date
	 */
	private function get_shopper_registration_date( ?WC_Order $order ): ?string {
		if ( ! $order ) {
			return null;
		}
		/**
		 * Order user
		 *
		 * @var WP_User $shopper
		 */
		$shopper = get_user_by( 'id', $order->get_customer_id() );
		if ( ! $shopper instanceof WP_User ) {
			return null;
		}
		$timestamp = strtotime( $shopper->user_registered );
		return $timestamp ? gmdate( 'c', $timestamp ) : null;
	}

	/**
	 * Get shopper updated at date. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_shopper_updated_at( ?WC_Order $order ): ?string {
		/**
		 * TODO: Document this filter
		 * Get Shopper updated at date
		 *
		 * @since 3.0.0
		 */
		$date = apply_filters( 'sequra_get_shopper_updated_at', $this->get_shopper_registration_date( $order ), $order );
		return is_string( $date ) || null === $date ? $date : null;
	}

	/**
	 * Get shopper rating. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_shopper_rating( ?WC_Order $order ): ?int {
		/**
		 * TODO: Document this filter
		 * Get Shopper rating. Must return an integer between 0 and 100 or null.
		 *
		 * @since 3.0.0
		 */
		$rating = apply_filters( 'sequra_get_shopper_rating', null, $order );
		return ( is_int( $rating ) && 0 <= $rating && 100 >= $rating ) || null === $rating ? $rating : null;
	}

	/**
	 * Get previous orders
	 * 
	 * @return PreviousOrder[]
	 */
	public function get_previous_orders( int $customer_id ): array {
		$previous_orders = array();

		$order_statuses = $this->order_status_service->getOrderStatusSettings();

		if ( ! $order_statuses ) {
			return $previous_orders;
		}

		$statuses = $this->order_status_service->get_shop_status_completed();
		foreach ( $order_statuses as $order_status ) {
			if ( $order_status->getSequraStatus() === OrderStates::STATE_APPROVED ) {
				// Use the default status if the shop status is not set.
				$statuses[] = empty( $order_status->getShopStatus() ) ? 'wc-processing' : $order_status->getShopStatus();
			}
		}

		/**
		 * Get previous orders
		 *
		 * @var WC_Order[] $previous_orders
		 */
		$orders = wc_get_orders(
			array(
				'limit'    => -1,
				'customer' => $customer_id,
				'status'   => $statuses,
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
		$data = array();
		if ( function_exists( 'WC' ) && WC()->customer ) {
			/**
			 * Customer instance.
			 *
			 * @var WC_Customer $customer
			 */
			$customer = WC()->customer;
			$data     = array(
				'email'               => $customer->get_email(),
				'billing_first_name'  => $customer->get_billing_first_name(),
				'billing_last_name'   => $customer->get_billing_last_name(),
				'billing_company'     => $customer->get_billing_company(),
				'billing_address_1'   => $customer->get_billing_address_1(),
				'billing_address_2'   => $customer->get_billing_address_2(),
				'billing_postcode'    => $customer->get_billing_postcode(),
				'billing_city'        => $customer->get_billing_city(),
				'billing_country'     => $customer->get_billing_country(),
				'billing_state'       => $customer->get_billing_state(),
				'billing_phone'       => $customer->get_billing_phone(),
				'billing_nif'         => method_exists( $customer, 'get_billing_nif' ) ? $customer->get_billing_nif() : '',
				'billing_vat'         => method_exists( $customer, 'get_billing_vat' ) ? $customer->get_billing_vat() : '',
				'shipping_first_name' => $customer->get_shipping_first_name(),
				'shipping_last_name'  => $customer->get_shipping_last_name(),
				'shipping_company'    => $customer->get_shipping_company(),
				'shipping_address_1'  => $customer->get_shipping_address_1(),
				'shipping_address_2'  => $customer->get_shipping_address_2(),
				'shipping_postcode'   => $customer->get_shipping_postcode(),
				'shipping_city'       => $customer->get_shipping_city(),
				'shipping_country'    => $customer->get_shipping_country(),
				'shipping_state'      => $customer->get_shipping_state(),
				'shipping_phone'      => $customer->get_shipping_phone(),
				'shipping_nif'        => method_exists( $customer, 'get_shipping_nif' ) ? $customer->get_shipping_nif() : '',
				'shipping_vat'        => method_exists( $customer, 'get_shipping_vat' ) ? $customer->get_shipping_vat() : '',
			);
		}
		return $data;
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
	 * Get IPN webhook identifier
	 */
	public function get_ipn_url( WC_Order $order, string $store_id ): string {
		return add_query_arg(
			array(
				'order'    => strval( $order->get_id() ),
				'wc-api'   => $this->payment_service->get_ipn_webhook(),
				'store_id' => $store_id,
			),
			home_url( '/' )
		);
	}

	/**
	 * Get payment gateway webhook identifier
	 */
	public function get_event_url( WC_Order $order, string $store_id ): string {
		return add_query_arg(
			array(
				'order'    => strval( $order->get_id() ),
				'wc-api'   => $this->payment_service->get_event_webhook(),
				'store_id' => $store_id,
			),
			home_url( '/' )
		);
	}

	/**
	 * Get payment gateway webhook identifier
	 */
	public function get_return_url( WC_Order $order ): string {
		return add_query_arg(
			array(
				'order'      => strval( $order->get_id() ),
				'sq_product' => 'SQ_PRODUCT_CODE',
				'wc-api'     => $this->payment_service->get_return_webhook(),
			),
			home_url( '/' )
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
		} else {
			$order->delete_meta_data( self::META_KEY_CAMPAIGN );
		}
		$order->update_meta_data( self::META_KEY_METHOD_TITLE, $dto->title );

		$order->update_meta_data( self::META_KEY_CART_REF, $cart_info->ref );
		$order->update_meta_data( self::META_KEY_CART_CREATED_AT, $cart_info->created_at );

		$order->save();
		return true;
	}

	/**
	 * Set cart info if it is not already set
	 */
	public function create_cart_info( WC_Order $order ): ?Cart_Info {
		$cart_info = $this->get_cart_info( $order );
		if ( $cart_info && $cart_info->ref ) {
			// Skip if the cart info is already set.
			return null;
		}

		$date      = $order->get_date_created();
		$cart_info = new Cart_Info( null, $date ? $date->date( 'c' ) : null );
		$order->update_meta_data( self::META_KEY_CART_REF, $cart_info->ref );
		$order->update_meta_data( self::META_KEY_CART_CREATED_AT, $cart_info->created_at );
		$order->save();

		return $cart_info;
	}

	/**
	 * Get the meta key used to store the sent to seQura value.
	 */
	public function get_sent_to_sequra_meta_key(): string {
		return self::META_KEY_SENT_TO_SEQURA;
	}

	/**
	 * Set the order as sent to seQura
	 */
	public function set_as_sent_to_sequra( WC_Order $order ): void {
		$order->update_meta_data( self::META_KEY_SENT_TO_SEQURA, 1 );
		$order->save();
	}

	/**
	 * Get customer for the order
	 */
	public function get_customer( ?WC_Order $order, string $lang, int $fallback_user_id = 0, string $fallback_ip = '', string $fallback_user_agent = '' ): Customer {
		$current_user_id = $order ? $order->get_customer_id() : $fallback_user_id;
		$logged_in       = $current_user_id > 0;
		$ref             = $logged_in ? $current_user_id : null;
		$ip              = $order ? $order->get_customer_ip_address( 'edit' ) : $fallback_ip;
		$user_agent      = $order ? $order->get_customer_user_agent( 'edit' ) : $fallback_user_agent;

		return new Customer(
			$this->get_email( $order ),
			$lang,
			$ip,
			$user_agent,
			$this->get_first_name( $order, true ),
			$this->get_last_name( $order, true ),
			$this->get_shopper_title( $order ), // title.
			$ref,
			$this->get_dob( $order ), // dateOfBirth.
			$this->get_nin( $order ), // nin.
			$this->get_company( $order, true ),
			$this->get_vat( $order, true ), // vatNumber.
			$this->get_shopper_created_at( $order, true ), // createdAt.
			$this->get_shopper_updated_at( $order, true ), // updatedAt.
			$this->get_shopper_rating( $order ), // rating.
			null, // ninControl.
			$this->get_previous_orders( $current_user_id ),
			null, // vehicle.
			$logged_in
		);
	}

	/**
	 * Get delivery or invoice address
	 */
	public function get_address( ?WC_Order $order, bool $is_delivery ): Address {
		$country = $this->get_country( $order, $is_delivery );
		return new Address(
			$this->get_company( $order, $is_delivery ),
			$this->get_address_1( $order, $is_delivery ),
			$this->get_address_2( $order, $is_delivery ),
			$this->get_postcode( $order, $is_delivery ),
			$this->get_city( $order, $is_delivery ),
			$country ? $country : 'ES',
			$this->get_first_name( $order, $is_delivery ),
			$this->get_last_name( $order, $is_delivery ),
			null, // phone.
			$this->get_phone( $order, $is_delivery ), // mobile phone.
			$this->get_state( $order, $is_delivery ),
			$order ? $order->get_customer_note( 'edit' ) : null, // extra.
			$this->get_vat( $order, $is_delivery )
		);
	}

	/**
	 * Call the Order Update API to sync the order status with SeQura
	 * 
	 * @throws Throwable
	 */
	public function update_sequra_order_status( WC_Order $order, string $old_store_status, string $new_store_status ): void {
		if ( in_array( $new_store_status, $this->order_status_service->get_shop_status_completed( true ), true ) ) {
			$this->set_sequra_order_status_to_shipped( $order );
		}
	}

	/**
	 * Set the order status to shipped in SeQura
	 *
	 * @throws Throwable 
	 */
	private function set_sequra_order_status_to_shipped( WC_Order $order ): void {
		$cart_info     = $this->get_cart_info( $order );
		$currency      = $order->get_currency( 'edit' );
		$cart_ref      = $cart_info ? $cart_info->ref : null;
		$created_at    = $cart_info ? $cart_info->created_at : null;
		$updated_at    = $order->get_date_completed()->format( 'Y-m-d H:i:s' );
		$shipped_items = array_merge(
			$this->cart_service->get_items( $order ),
			$this->cart_service->get_handling_items( $order ),
			$this->cart_service->get_discount_items( $order ),
			$this->cart_service->get_refund_items( $order )
		);

		try {
			$this->call_update_order(
				new OrderUpdateData(
					(string) $order->get_id(), // Order reference.
					new Cart( $currency, false, $shipped_items, $cart_ref, $created_at, $updated_at ), // Shipped cart.
					new Cart( $currency ), // Unshipped cart.
					null, // Delivery address.
					null // Invoice address.
				) 
			);
		} catch ( Throwable $e ) {
			throw $e;
		}
	}

	/**
	 * Get a fresh instance of the core order service
	 *
	 * @param OrderUpdateData $order_data Order data
	 * 
	 * @throws Exception
	 */
	private function call_update_order( $order_data ) {
		$store_id = $this->configuration->get_store_id();
		/**
		 * Order service
		 *
		 * @var OrderService $order_service
		 */
		$order_service = $this->store_context::doWithStore( $store_id, 'SeQura\Core\Infrastructure\ServiceRegister::getService', array( OrderService::class ) );
		$this->store_context::doWithStore( $store_id, array( $order_service, 'updateOrder' ), array( $order_data ) );
	}

	/**
	 * Update the order amount in SeQura after a refund
	 *
	 * @throws Throwable 
	 */
	public function handle_refund( WC_Order $order, float $amount ): void {
		$cart_info     = $this->get_cart_info( $order );
		$currency      = $order->get_currency( 'edit' );
		$cart_ref      = $cart_info ? $cart_info->ref : null;
		$created_at    = $cart_info ? $cart_info->created_at : null;
		$updated_at    = $order->get_date_completed()->format( 'Y-m-d H:i:s' );
		$shipped_items = array();
		if ( $order->get_total( 'edit' ) > $amount ) {
			$shipped_items = array_merge(
				$this->cart_service->get_items( $order ),
				$this->cart_service->get_handling_items( $order ),
				$this->cart_service->get_discount_items( $order ),
				$this->cart_service->get_refund_items( $order )
			);
		}
		
		try {
			$this->call_update_order(
				new OrderUpdateData(
					(string) $order->get_id(), // Order reference.
					new Cart( $currency, false, $shipped_items, $cart_ref, $created_at, $updated_at ), // Shipped cart.
					new Cart( $currency ), // Unshipped cart.
					null, // Delivery address.
					null // Invoice address.
				) 
			);
		} catch ( Throwable $e ) {
			throw $e;
		}
	}

	/**
	 * Get the link to the SeQura back office for the order
	 */
	public function get_link_to_sequra_back_office( WC_Order $order ): ?string {
		if ( $order->get_payment_method() !== $this->payment_service->get_payment_gateway_id() ) {
			return null;
		}
		
		switch ( $this->configuration->get_env() ) {
			case 'sandbox':
				return 'https://simbox.sequrapi.com/orders/' . $order->get_transaction_id();
			case 'live':
				return 'https://simba.sequra.es/orders/' . $order->get_transaction_id();
			default:
				return null;
		}
	}
}
