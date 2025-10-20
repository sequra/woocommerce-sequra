<?php
/**
 * Implementation of the Create Order Request Builder.
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Implementation\BusinessLogic\Domain\Order\Builders;

use Exception;
use SeQura\Core\BusinessLogic\Domain\Connection\Exceptions\ConnectionDataNotFoundException;
use SeQura\Core\BusinessLogic\Domain\Connection\Exceptions\CredentialsNotFoundException;
use SeQura\Core\BusinessLogic\Domain\Connection\Services\CredentialsService;
use SeQura\Core\BusinessLogic\Domain\Order\Builders\MerchantOrderRequestBuilder;
use SeQura\Core\BusinessLogic\Domain\Order\Exceptions\InvalidUrlException;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Address;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Cart;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\CreateOrderRequest;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Customer;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Gui;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Item\Item;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Merchant;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\MerchantReference;
use SeQura\Core\Infrastructure\Logger\LogContextData;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\Order\Builders\Interface_Create_Order_Request_Builder;
use SeQura\WC\Services\Cart\Interface_Cart_Service;
use SeQura\WC\Services\I18n\Interface_I18n;
use SeQura\WC\Services\Log\Interface_Logger_Service;
use SeQura\WC\Services\Order\Builder\Interface_Order_Address_Builder;
use SeQura\WC\Services\Order\Interface_Current_Order_Provider;
use SeQura\WC\Services\Order\Builder\Interface_Order_Customer_Builder;
use SeQura\WC\Services\Order\Builder\Interface_Order_Delivery_Method_Builder;
use SeQura\WC\Services\Order\Interface_Order_Service;
use SeQura\WC\Services\Platform\Platform_Provider;
use SeQura\WC\Services\Product\Interface_Product_Service;
use SeQura\WC\Services\Shopper\Interface_Shopper_Service;
use Throwable;

/**
 * Implementation of the Create Order Request Builder.
 */
class Create_Order_Request_Builder implements Interface_Create_Order_Request_Builder {

	/**
	 * Cart service
	 *
	 * @var Interface_Cart_Service
	 */
	private $cart_service;

	/**
	 * Platform provider
	 *
	 * @var Platform_Provider
	 */
	private $platform_provider;

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
	 * Current order provider
	 *
	 * @var Interface_Current_Order_Provider
	 */
	private $current_order_provider;

	/**
	 * Merchant order request builder
	 *
	 * @var MerchantOrderRequestBuilder
	 */
	private $merchant_order_request_builder;

	/**
	 * Order delivery method builder
	 *
	 * @var Interface_Order_Delivery_Method_Builder
	 */
	private $delivery_method_builder;

	/**
	 * Order address builder
	 *
	 * @var Interface_Order_Address_Builder
	 */
	private $address_builder;

	/**
	 * Order customer builder
	 *
	 * @var Interface_Order_Customer_Builder
	 */
	private $customer_builder;

	/**
	 * Credentials service
	 *
	 * @var CredentialsService
	 */
	private $credentials_service;

	/**
	 * Constructor
	 */
	public function __construct(
		Interface_Cart_Service $cart_service,
		Platform_Provider $platform_provider,
		Interface_Product_Service $product_service,
		Interface_Order_Service $order_service,
		Interface_I18n $i18n,
		Interface_Shopper_Service $shopper_service,
		Interface_Logger_Service $logger,
		Interface_Current_Order_Provider $current_order_provider,
		MerchantOrderRequestBuilder $merchant_order_request_builder,
		Interface_Order_Delivery_Method_Builder $delivery_method_builder,
		Interface_Order_Address_Builder $address_builder,
		Interface_Order_Customer_Builder $customer_builder,
		CredentialsService $credentials_service
	) {
		$this->cart_service                   = $cart_service;
		$this->platform_provider              = $platform_provider;
		$this->product_service                = $product_service;
		$this->order_service                  = $order_service;
		$this->i18n                           = $i18n;
		$this->shopper_service                = $shopper_service;
		$this->logger                         = $logger;
		$this->current_order_provider         = $current_order_provider;
		$this->merchant_order_request_builder = $merchant_order_request_builder;
		$this->delivery_method_builder        = $delivery_method_builder;
		$this->address_builder                = $address_builder;
		$this->customer_builder               = $customer_builder;
		$this->credentials_service            = $credentials_service;
	}

	/**
	 * Build a CreateOrderRequest instance.
	 * 
	 * @throws CredentialsNotFoundException|ConnectionDataNotFoundException|InvalidUrlException|Exception
	 */
	public function build(): CreateOrderRequest {
		$merchant = $this->get_merchant();

		/**
		 * Filter the delivery method options.
		 *
		 * @since 3.0.0
		 */
		$delivery_method = \apply_filters(
			'sequra_create_order_request_delivery_method_options',
			$this->delivery_method_builder->build( $this->current_order_provider->get() )
		);

		return new CreateOrderRequest(
			'', // state.
			$this->cart(),
			$delivery_method,
			$this->customer(),
			$this->platform_provider->get(),
			$this->delivery_address(),
			$this->invoice_address(),
			$this->gui(),
			$merchant,
			$this->merchant_reference(),
			null // trackings.
		);
	}

	/**
	 * Get the merchant reference.
	 */
	private function merchant_reference(): ?MerchantReference {
		$merchant_reference = null;
		$current_order      = $this->current_order_provider->get();
		if ( $current_order ) {
			/**
			 * Filter the order_ref_1.
			 *
			 * @since 2.0.0
			 */
			$ref_1              = \apply_filters( 'woocommerce_sequra_get_order_ref_1', $current_order->get_id(), $current_order );
			$merchant_reference = new MerchantReference( $ref_1 );
		}

		/**
		* Filter the merchant_reference.
		*
		* @since 3.0.0
		*/
		return \apply_filters(
			'sequra_create_order_request_merchant_reference',
			$merchant_reference
		);
	}

	/**
	 * Get cart payload
	 */
	private function cart(): Cart {
		$cart_info     = null;
		$current_order = $this->current_order_provider->get();

		if ( ! $current_order ) {
			$cart_info = $this->cart_service->get_cart_info_from_session();
		} else {
			// Try to get cart info from order.
			$cart_info = $this->order_service->get_cart_info( $current_order );

			if ( ! $this->cart_service->is_cart_info_valid( $cart_info ) ) {
				$context = array( new LogContextData( 'order_id', $current_order->get_id() ) );
				// Try to get cart info from session.
				$this->logger->log_debug( 'Cart info ref for order is missing. Trying to recover from session', __FUNCTION__, __CLASS__, $context );
				$cart_info = $this->cart_service->get_cart_info_from_session( false );
				if ( ! $this->cart_service->is_cart_info_valid( $cart_info ) ) {
					// Try to create a new cart info.
					$this->logger->log_debug( 'Cart info can\'t be recovered from session. Trying to create one', __FUNCTION__, __CLASS__, $context );
					$cart_info = $this->order_service->create_cart_info( $current_order );
	
					if ( ! $cart_info ) {
						$this->logger->log_debug( 'Cart info can\'t be created', __FUNCTION__, __CLASS__, $context );
					}
				} else {
					// Set cart info for the order.
					$this->order_service->set_cart_info( $current_order, $cart_info );
					$this->logger->log_debug( 'Cart info recovered from session', __FUNCTION__, __CLASS__, $context );
				}
			}
		}
		
		/**
		 * List of items in the order.
		 *
		 * @var Item[] 
		 */
		$items = array_merge(
			$this->cart_service->get_items( $current_order ),
			$this->cart_service->get_handling_items( $current_order ),
			$this->cart_service->get_discount_items( $current_order )
		);

		/**
		 * Filter the cart options.
		 *
		 * @since 3.0.0
		 */
		return apply_filters(
			'sequra_create_order_request_cart_options',
			new Cart(
				$current_order ? $current_order->get_currency( 'edit' ) : \get_woocommerce_currency(),
				false, // gift.
				$items,
				$cart_info ? $cart_info->ref : null,
				$cart_info ? $cart_info->created_at : null,
				gmdate( 'c' )
			)
		);
	}

	/**
	 * Try to get the country from the order or the cart.
	 * 
	 * @throws Exception
	 */
	private function get_country_from_order_or_cart(): string {
		$current_order = $this->current_order_provider->get();
		// Try to get the country from the order or the cart.
		$country = $this->shopper_service->get_country( $current_order );
		if ( empty( $country ) ) {
			throw new Exception( 'Country not found' );
		}
		return $country;
	}

	/**
	 * Get cart ref from order or cart
	 */
	private function get_cart_ref_from_order_or_cart(): string {
		$current_order = $this->current_order_provider->get();
		$cart_info     = ! $current_order ? 
			$this->cart_service->get_cart_info_from_session() :
			$this->order_service->get_cart_info( $current_order );
		
		if ( empty( $cart_info ) || empty( $cart_info->ref ) ) {
			return '';
		}
		return $cart_info->ref;
	}

	/**
	 * Get the merchant.
	 * 
	 * @throws CredentialsNotFoundException|ConnectionDataNotFoundException|InvalidUrlException|Exception
	 */
	private function get_merchant(): Merchant {
		$merchant = $this->merchant_order_request_builder->build(
			$this->get_country_from_order_or_cart(), 
			$this->get_cart_ref_from_order_or_cart()
		);

		/**
		 * Filter the merchant data.
		 *
		 * @since 3.0.0
		 */
		return \apply_filters( 'sequra_create_order_request_merchant_data', $merchant );
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
		 *
		 * @since 3.0.0
		 */
		return \apply_filters(
			'sequra_create_order_request_' . ( $is_delivery ? 'delivery_address' : 'invoice_address' ) . '_options',
			$this->address_builder->build( $this->current_order_provider->get(), $is_delivery )
		);
	}

	/**
	 * Get customer payload
	 */
	private function customer(): Customer {
		/**
		 * Filter the customer options.
		 *
		 * @since 3.0.0
		 */
		return \apply_filters(
			'sequra_create_order_request_customer_options',
			$this->customer_builder->build(
				$this->current_order_provider->get(),
				$this->i18n->get_lang(),
				\get_current_user_id(),
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
		 *
		 * @since 3.0.0
		 */
		return \apply_filters( 'sequra_create_order_request_gui_options', $gui );
	}

	/**
	 * Check if the builder is allowed for the current settings 
	 */
	public function is_allowed(): bool {
		// Filter bots requests.
		if ( $this->shopper_service->is_bot() ) {
			return false;
		}

		try {
			$this->credentials_service->getMerchantIdByCountryCode( 
				$this->get_country_from_order_or_cart()
			);
		} catch ( Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			return false;
		}

		if ( ! $this->shopper_service->is_ip_allowed() ) {
			$this->logger->log_debug( 'Payment gateway is not available for this IP', __FUNCTION__, __CLASS__ );
			return false;
		}

		if ( $this->product_service->is_ban_list_empty() ) {
			return true;
		}

		foreach ( $this->cart_service->get_products( $this->current_order_provider->get() ) as $product ) {
			if ( $this->product_service->is_banned( $product ) ) {
				$this->logger->log_debug( 'SeQura is not available: product is banned or is in a banned category', __FUNCTION__, __CLASS__, array( new LogContextData( 'product_id', $product->get_id() ) ) );
				return false;
			}
		}

		return true;
	}
}
