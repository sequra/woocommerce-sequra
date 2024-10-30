<?php
/**
 * Implementation of the Create Order Request Builder.
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Implementation\BusinessLogic\Domain\Order\Builders;

use Exception;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Address;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Cart;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\CreateOrderRequest;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Customer;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\EventsWebhook;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Gui;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Merchant;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\MerchantReference;
use SeQura\Core\Infrastructure\Logger\LogContextData;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Options;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\Order\Models\OrderRequest\Options as ExtendedOptions;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\Order\Builders\Interface_Create_Order_Request_Builder;
use SeQura\WC\Core\Extension\Infrastructure\Configuration\Configuration;
use SeQura\WC\Services\Cart\Interface_Cart_Service;
use SeQura\WC\Services\I18n\Interface_I18n;
use SeQura\WC\Services\Interface_Logger_Service;
use SeQura\WC\Services\Order\Interface_Order_Service;
use SeQura\WC\Services\Payment\Interface_Payment_Service;
use SeQura\WC\Services\Product\Interface_Product_Service;
use SeQura\WC\Services\Shopper\Interface_Shopper_Service;
use WC_Order;

/**
 * Implementation of the Create Order Request Builder.
 */
class Create_Order_Request_Builder implements Interface_Create_Order_Request_Builder {

	/**
	 * Payment service
	 *
	 * @var Interface_Payment_Service
	 */
	private $payment_service;

	/**
	 * Cart service
	 *
	 * @var Interface_Cart_Service
	 */
	private $cart_service;

	/**
	 * Current order
	 *
	 * @var WC_Order
	 */
	private $current_order;

	/**
	 * Configuration
	 *
	 * @var Configuration
	 */
	private $configuration;

	/**
	 * Product service
	 *
	 * @var Interface_Product_Service
	 */
	private $product_service;

	/**
	 * Order service
	 *
	 * @var Interface_Order_Service
	 */
	private $order_service;

	/**
	 * I18n service
	 *
	 * @var Interface_I18n
	 */
	private $i18n;

	/**
	 * Shopper service
	 *
	 * @var Interface_Shopper_Service
	 */
	private $shopper_service;

	/**
	 * Logger
	 *
	 * @var Interface_Logger_Service
	 */
	private $logger;

	/**
	 * Constructor
	 */
	public function __construct(
		Interface_Payment_Service $payment_service,
		Interface_Cart_Service $cart_service,
		Configuration $configuration,
		Interface_Product_Service $product_service,
		Interface_Order_Service $order_service,
		Interface_I18n $i18n,
		Interface_Shopper_Service $shopper_service,
		Interface_Logger_Service $logger
	) {
		$this->payment_service = $payment_service;
		$this->cart_service    = $cart_service;
		$this->configuration   = $configuration;
		$this->product_service = $product_service;
		$this->order_service   = $order_service;
		$this->i18n            = $i18n;
		$this->shopper_service = $shopper_service;
		$this->logger          = $logger;
	}

	/**
	 * Set current order
	 */
	public function set_current_order( ?WC_Order $order ): void {
		$this->current_order = $order;
	}

	/**
	 * Build a CreateOrderRequest instance.
	 * 
	 * @throws Exception
	 */
	public function build(): CreateOrderRequest {
		$merchant = $this->get_merchant();
		if ( ! $merchant ) {
			throw new Exception( 'Merchant ID is empty' );
		}

		/**
		 * Filter the delivery method options.
		 * TODO: document this hook
		 *
		 * @since 3.0.0
		 */
		$delivery_method = apply_filters( 'sequra_create_order_request_delivery_method_options', $this->order_service->get_delivery_method( $this->current_order ) );

		return new CreateOrderRequest(
			'', // state.
			$merchant,
			$this->cart(),
			$delivery_method,
			$this->customer(),
			$this->configuration->get_platform(),
			$this->delivery_address(),
			$this->invoice_address(),
			$this->gui(),
			$this->merchant_reference(),
			null // trackings.
		);
	}

	/**
	 * Get the merchant reference.
	 */
	private function merchant_reference(): ?MerchantReference {
		$merchant_reference = null;
		if ( $this->current_order ) {
			/**
			 * Filter the order_ref_1.
			 *
			 * @since 2.0.0
			 */
			$ref_1 = apply_filters_deprecated(
				'woocommerce_sequra_get_order_ref_1',
				array(
					$this->current_order->get_id(),
					$this->current_order,
				),
				'3.0.0',
				'sequra_create_order_request_merchant_reference'
			);

			$merchant_reference = new MerchantReference( $ref_1 );
		}

		/**
		* Filter the merchant_reference.
		* TODO: document this hook
		*
		* @since 3.0.0
		*/
		return apply_filters(
			'sequra_create_order_request_merchant_reference',
			$merchant_reference
		);
	}

	/**
	 * Get cart payload
	 */
	private function cart(): Cart {
		$cart_info = $this->current_order ? $this->order_service->get_cart_info( $this->current_order ) : $this->cart_service->get_cart_info_from_session();

		if ( $this->current_order && ( ! $cart_info || ! $cart_info->ref ) ) {
			$this->logger->log_debug( 'Cart info ref for order is missing. Trying to create one', __FUNCTION__, __CLASS__, array( new LogContextData( 'order_id', $this->current_order->get_id() ) ) );
			$cart_info = $this->order_service->create_cart_info( $this->current_order );
			if ( ! $cart_info || ! $cart_info->ref ) {
				$this->logger->log_debug( 'Cart info can\'t be created', __FUNCTION__, __CLASS__, array( new LogContextData( 'order_id', $this->current_order->get_id() ) ) );
			} else {
				$this->logger->log_debug( 'Cart info created successfully', __FUNCTION__, __CLASS__, array( new LogContextData( 'order_id', $this->current_order->get_id() ) ) );
			}
		}
		
		/**
		 * List of items in the order.
		 *
		 * @var Item[] 
		 */
		$items = array_merge(
			$this->cart_service->get_items( $this->current_order ),
			$this->cart_service->get_handling_items( $this->current_order ),
			$this->cart_service->get_discount_items( $this->current_order )
		);

		/**
		 * Filter the cart options.
		 * TODO: document this hook
		 *
		 * @since 3.0.0
		 */
		return apply_filters(
			'sequra_create_order_request_cart_options',
			new Cart(
				$this->current_order ? $this->current_order->get_currency( 'edit' ) : get_woocommerce_currency(),
				false, // gift.
				$items,
				$cart_info ? $cart_info->ref : null,
				$cart_info ? $cart_info->created_at : null,
				gmdate( 'c' )
			)
		);
	}

	/**
	 * Get the merchant.
	 */
	private function get_merchant(): ?Merchant {
		$merchant_id = $this->payment_service->get_merchant_id();
		if ( ! $merchant_id ) {
			return null;
		}

		$notify_url              = null;
		$notification_parameters = null;
		$return_url              = null;
		$events_webhook          = null;
		$store_id                = $this->configuration->get_store_id();

		if ( $this->current_order ) {
			$notify_url = $this->order_service->get_ipn_url( $this->current_order, $store_id );
			$_order     = strval( $this->current_order->get_id() );
			$_signature = $this->payment_service->sign( $this->current_order->get_id() );

			$notification_parameters = array(
				'order'     => $_order,
				'signature' => $_signature,
				'result'    => '0',
				'storeId'   => $store_id,
			);

			$return_url = $this->order_service->get_return_url( $this->current_order );

			$events_webhook = new EventsWebhook(
				$this->order_service->get_event_url( $this->current_order, $store_id ),
				array(
					'order'     => $_order,
					'signature' => $_signature,
					'storeId'   => $store_id,
				) 
			);
		}

		/**
		 * Filter the merchant data.
		 * TODO: document this hook (https://docs.sequrapi.com/api_ref/api_ref_order_documentation.html#merchant)
		 *
		 * @since 3.0.0
		 */
		return apply_filters(
			'sequra_create_order_request_merchant_data',
			new Merchant(
				$merchant_id,
				$notify_url,
				$notification_parameters,
				$return_url,
				null, // approved_callback.
				null, // $edit_url.
				null, // abort_url.
				null, // rejected_callback.
				null, // partpayment_details_getter.
				null, // approved_url.
				$this->get_merchant_options(),
				$events_webhook
			)
		);
	}

	/**
	 * Get the merchant options.
	 */
	private function get_merchant_options(): ?Options {
		$options = null;

		if ( $this->configuration->allow_first_service_payment_delay() ) {
			$desired_first_charge_on = $this->cart_service->get_desired_first_charge_on( $this->current_order );
			if ( $desired_first_charge_on ) {
				/**
				* TODO: document this hook
				* Allow modify the addresses_may_be_missing value.Accept null, true or false.
				*
				* @since 3.0.0
				*/
				$addresses_may_be_missing = apply_filters( 'sequra_merchant_options_addresses_may_be_missing', null );

				if ( ! is_bool( $addresses_may_be_missing ) && null !== $addresses_may_be_missing ) {
					$addresses_may_be_missing = null;
				}

				$options = new ExtendedOptions(
					null, // has_jquery.
					null, // uses_shipped_cart.
					$addresses_may_be_missing, // addresses_may_be_missing.
					null, // immutable_customer_data.
					$desired_first_charge_on
				);
			}
		}

		/**
		 * Filter the merchant options.
		 * TODO: document this hook
		 *
		 * @since 3.0.0
		 */
		return apply_filters( 'sequra_create_order_request_merchant_options', $options );
	}

	/**
	 * Get delivery address payload
	 */
	private function delivery_address(): Address {
		return $this->address( true );
	}
	/**
	 * Get invoice address payload
	 */
	private function invoice_address(): Address {
		return $this->address( false );
	}
	
	/**
	 * Get delivery or invoice address payload
	 */
	private function address( bool $is_delivery ): Address {
		/**
		 * Filter the address options.
		 * TODO: document this hook
		 *
		 * @since 3.0.0
		 */
		return apply_filters(
			'sequra_create_order_request_' . ( $is_delivery ? 'delivery_address' : 'invoice_address' ) . '_options',
			$this->order_service->get_address( $this->current_order, $is_delivery )
		);
	}

	/**
	 * Get customer payload
	 */
	private function customer(): Customer {
		/**
		 * Filter the customer options.
		 * TODO: document this hook
		 *
		 * @since 3.0.0
		 */
		return apply_filters(
			'sequra_create_order_request_customer_options',
			$this->order_service->get_customer(
				$this->current_order,
				$this->i18n->get_lang(),
				get_current_user_id(),
				$this->shopper_service->get_ip(),
				$this->shopper_service->get_user_agent()
			)
		);
	}

	/**
	 * Get GUI payload
	 */
	private function gui(): Gui {
		$gui = new Gui( $this->shopper_service->is_using_mobile() ? 'smartphone' : 'desktop' );

		/**
		 * Filter the gui options.
		 * TODO: document this hook
		 *
		 * @since 3.0.0
		 */
		return apply_filters( 'sequra_create_order_request_gui_options', $gui );
	}

	/**
	 * Check if the builder is allowed for the current settings 
	 */
	public function is_allowed(): bool {
		if ( ! $this->payment_service->get_merchant_id() ) {
			$this->logger->log_debug( 'Merchant ID is empty', __FUNCTION__, __CLASS__ );
			return false;
		}

		if ( ! $this->configuration->is_available_for_ip() ) {
			$this->logger->log_debug( 'Payment gateway is not available for this IP', __FUNCTION__, __CLASS__ );
			return false;
		}

		$excluded_products   = $this->configuration->get_excluded_products();
		$excluded_categories = $this->configuration->get_excluded_categories();

		if ( empty( $excluded_products ) && empty( $excluded_categories ) ) {
			return true;
		}

		/**
		 * Product
		 *
		 * @var WC_Product $product
		 */
		foreach ( $this->cart_service->get_products( $this->current_order ) as $product ) {
			if ( in_array( $product->get_sku(), $excluded_products, true ) 
				|| in_array( strval( $product->get_id() ), $excluded_products, true ) 
			) {
				$this->logger->log_debug(
					'Payment gateway is not available for this product',
					__FUNCTION__,
					__CLASS__,
					array( 
						new LogContextData( 'product_id', $product->get_id() ),
						new LogContextData( 'product_sku', $product->get_sku() ),
						new LogContextData( 'excluded_products', $excluded_products ), 
					) 
				);
				return false;
			}

			if ( $this->product_service->is_banned( $product ) ) {
				$this->logger->log_debug( 'Payment gateway is not available: product is banned', __FUNCTION__, __CLASS__, array( new LogContextData( 'product_id', $product->get_id() ) ) );
				return false;
			}

			if ( ! empty( $excluded_categories ) && ! empty(
				array_intersect( $excluded_categories, $product->get_category_ids() )
			) ) {
				$this->logger->log_debug(
					'Payment gateway is not available for category',
					__FUNCTION__,
					__CLASS__,
					array( 
						new LogContextData( 'product_categories', $product->get_category_ids() ), 
						new LogContextData( 'excluded_categories', $excluded_categories ), 
					) 
				);
				return false;
			}
		}

		return true;
	}
}
