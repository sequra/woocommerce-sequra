<?php
/**
 * Wrapper to ease the read and write of configuration values.
 * Delegate to the ConfigurationManager instance to access the data in the database.
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Services\Core;

use Exception;
use SeQura\Core\BusinessLogic\AdminAPI\GeneralSettings\Responses\GeneralSettingsResponse;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\CreateOrderRequest;
use SeQura\Core\Infrastructure\ServiceRegister;
use SeQura\WC\Dto\Registration_Item;
use SeQura\WC\Services\Cart\Interface_Cart_Service;
use SeQura\WC\Services\I18n\Interface_I18n;
use SeQura\WC\Services\Order\Interface_Order_Service;
use SeQura\WC\Services\Payment\Interface_Payment_Service;
use SeQura\WC\Services\Product\Interface_Product_Service;
use SeQura\WC\Services\Shopper\Interface_Shopper_Service;
use WC_Order;

/**
 * Wrapper to ease the read and write of configuration values.
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
	 * Constructor
	 */
	public function __construct(
		Interface_Payment_Service $payment_service,
		Interface_Cart_Service $cart_service,
		Configuration $configuration,
		Interface_Product_Service $product_service,
		Interface_Order_Service $order_service,
		Interface_I18n $i18n,
		Interface_Shopper_Service $shopper_service
	) {
		$this->payment_service = $payment_service;
		$this->cart_service    = $cart_service;
		$this->configuration   = $configuration;
		$this->product_service = $product_service;
		$this->order_service   = $order_service;
		$this->i18n            = $i18n;
		$this->shopper_service = $shopper_service;
	}

	/**
	 * Set current order
	 */
	public function set_current_order( WC_Order $order ): void {
		$this->current_order = $order;
	}

	/**
	 * Build a CreateOrderRequest instance.
	 * 
	 * @throws Exception
	 */
	public function build(): CreateOrderRequest {
		$merchant = $this->get_merchant_data();
		if ( empty( $merchant ) ) {
			throw new Exception( 'Merchant ID is empty' );
		}

		/**
		 * Filter the delivery method options.
		 * TODO: document this hook
		 *
		 * @since 3.0.0
		 */
		$delivery_method = apply_filters( 'sequra_create_order_request_delivery_method_options', $this->order_service->get_delivery_method( $this->current_order )->to_array() );

		return CreateOrderRequest::fromArray(
			array(
				'state'            => '',
				'merchant'         => $merchant,
				'cart'             => $this->cart_with_items(),
				'delivery_method'  => $delivery_method,
				'delivery_address' => $this->delivery_address(),
				'invoice_address'  => $this->invoice_address(),
				'customer'         => $this->customer(),
				'gui'              => $this->gui(),
				'platform'         => $this->platform(),
			)
		);
	}

	/**
	 * Get cart payload
	 */
	private function cart_with_items(): array {
		$sequra_cart_info     = $this->cart_service->get_cart_info_from_session();
		$items                = array_merge(
			$this->cart_service->get_product_items(),
			$this->cart_service->get_handling_items(), // TODO: order is always null here?
			$this->cart_service->get_discount_items(), // TODO: order is always null here?
			$this->cart_service->get_extra_items(), // TODO: order is always null here?
		);
		$order_total_with_tax = 0;
		$allow_service_reg    = $this->configuration->allow_service_reg_items();
		$reg_items            = array();

		foreach ( $items as &$item ) {
			$order_total_with_tax += $item['total_with_tax'];

			// Registration items.
			if ( ! $allow_service_reg ) {
				continue;
			}

			if ( empty( $item['product_id'] ) ) {
				continue;
			}

			$registration_amount = $this->product_service->get_registration_amount( (int) $item['product_id'] );
			if ( $registration_amount <= 0 ) {
				continue;
			}

			$reg_items[] = ( new Registration_Item( 
				$item['reference'], 
				$item['name'], 
				$item['quantity'] * $registration_amount
			) )->to_array();
			
			// Fix orginal item.
			$item['total_with_tax'] = max(
				0,
				$item['total_with_tax'] - $item['quantity'] * $registration_amount
			);
			$item['price_with_tax'] = max(
				0,
				$item['price_with_tax'] - $registration_amount
			);
		}

		$options = array(
			'currency'             => get_woocommerce_currency(),
			'cart_ref'             => $sequra_cart_info->ref,
			'created_at'           => $sequra_cart_info->created_at,
			'updated_at'           => gmdate( 'c' ),
			'gift'                 => false,
			'items'                => $items,
			'order_total_with_tax' => $order_total_with_tax,
			'order_total_tax'      => 0,
		);

		/**
		 * Filter the cart options.
		 * TODO: document this hook
		 *
		 * @since 3.0.0
		 */
		return apply_filters( 'sequra_create_order_request_cart_options', $options );
	}

	/**
	 * Get the merchant data.
	 */
	private function get_merchant_data(): array {
		$merchant_id = $this->payment_service->get_merchant_id();
		if ( ! $merchant_id ) {
			return array();
		}

		$data = array(
			'id'      => $merchant_id,
			'options' => $this->get_merchant_options(),
		);

		if ( $this->current_order ) {
			$notify_url = $this->payment_service->get_notify_url( $this->current_order );

			$data = array_merge(
				$data,
				array(
					'notify_url'              => $notify_url,
					'notification_parameters' => array(
						'order'     => strval( $this->current_order->get_id() ),
						'signature' => $this->payment_service->sign( $this->current_order->get_id() ),
						'result'    => '0',
					),
					'return_url'              => $this->payment_service->get_return_url( $this->current_order ),
					'events_webhook'          => array(
						'url'        => $notify_url,
						'parameters' => array(
							'signature' => $this->payment_service->sign( $this->current_order->get_id() ),
							'order'     => '' . $this->current_order->get_id(),
						),
					),
				)
			);
		}

		/**
		 * Filter the merchant data.
		 * TODO: document this hook (https://docs.sequrapi.com/api_ref/api_ref_order_documentation.html#merchant)
		 *
		 * @since 3.0.0
		 */
		return apply_filters( 'sequra_create_order_request_merchant_data', $data );
	}

	/**
	 * Get the merchant options.
	 */
	private function get_merchant_options(): array {
		$options = array();

		if ( $this->configuration->allow_first_service_payment_delay() ) {
			$desired_first_charge_on = $this->cart_service->get_desired_first_charge_on();
			if ( $desired_first_charge_on ) {
				$options['desired_first_charge_on'] = $desired_first_charge_on;
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
	private function delivery_address(): array {
		return $this->address( true );
	}
	/**
	 * Get invoice address payload
	 */
	private function invoice_address(): array {
		return $this->address( false );
	}
	
	/**
	 * Get delivery or invoice address payload
	 */
	private function address( bool $is_delivery ): array {
		$country = $this->order_service->get_country( $this->current_order, $is_delivery );
		$options = array(
			'given_names'    => $this->order_service->get_first_name( $this->current_order, $is_delivery ),
			'surnames'       => $this->order_service->get_last_name( $this->current_order, $is_delivery ),
			'company'        => $this->order_service->get_company( $this->current_order, $is_delivery ),
			'address_line_1' => $this->order_service->get_address_1( $this->current_order, $is_delivery ),
			'address_line_2' => $this->order_service->get_address_2( $this->current_order, $is_delivery ),
			'postal_code'    => $this->order_service->get_postcode( $this->current_order, $is_delivery ),
			'city'           => $this->order_service->get_city( $this->current_order, $is_delivery ),
			'country_code'   => $country ? $country : 'ES',
			'state'          => $this->order_service->get_state( $this->current_order, $is_delivery ),
			'mobile_phone'   => $this->order_service->get_phone( $this->current_order, $is_delivery ),
			'vat_number'     => $this->order_service->get_vat( $this->current_order, $is_delivery ),
		);

		/**
		 * Filter the address options.
		 * TODO: document this hook
		 *
		 * @since 3.0.0
		 */
		return apply_filters( 'sequra_create_order_request_' . ( $is_delivery ? 'delivery_address' : 'invoice_address' ) . '_options', $options );
	}

	/**
	 * Get customer payload
	 */
	private function customer(): array {


		$is_user_logged_in = is_user_logged_in();
		$current_user_id   = $is_user_logged_in ? get_current_user_id() : -1;
		$data              = array(
			'given_names' => $this->order_service->get_first_name( $this->current_order, true ),
			'surnames'    => $this->order_service->get_last_name( $this->current_order, true ),
			'email'       => $this->order_service->get_email( $this->current_order ),
			'nin'         => $this->order_service->get_vat( $this->current_order, true ),
			'company'     => $this->order_service->get_company( $this->current_order, true ),
		);

		if ( $current_user_id > 0 ) {
			$data['previous_orders'] = $this->order_service->get_previous_orders( $current_user_id );
			$data['ref']             = $current_user_id;
		}

		$data['language_code'] = $this->i18n->get_lang();
		$ip                    = $this->shopper_service->get_ip();
		if ( $ip ) {
			$data['ip_number'] = $ip;
		}
		$user_agent = $this->shopper_service->get_user_agent();
		if ( $user_agent ) {
			$data['user_agent'] = $user_agent;
		}
		$data['logged_in'] = $is_user_logged_in;
		

		/**
		 * Filter the customer options.
		 * TODO: document this hook
		 *
		 * @since 3.0.0
		 */
		return apply_filters( 'sequra_create_order_request_customer_options', $data );
	}

	/**
	 * Get GUI payload
	 */
	private function gui(): array {
		$data = array(
			'layout' => $this->shopper_service->is_using_mobile() ? 'mobile' : 'desktop',
		);

		/**
		 * Filter the gui options.
		 * TODO: document this hook
		 *
		 * @since 3.0.0
		 */
		return apply_filters( 'sequra_create_order_request_gui_options', $data );
	}

	/**
	 * Get platform payload
	 */
	public static function platform(): array {
		$woo = ServiceRegister::getService( 'woocommerce.data' );
		$sq  = ServiceRegister::getService( 'plugin.data' );
		
		$data = array_merge(
			array(
				'name'           => 'WooCommerce',
				'version'        => empty( $woo['Version'] ) ? '' : $woo['Version'],
				'plugin_version' => empty( $sq['Version'] ) ? '' : $sq['Version'],
			),
			ServiceRegister::getService( 'environment.data' )
		);

		/**
		 * Filter the platform options.
		 * TODO: document this hook
		 *
		 * @since 3.0.0
		 */
		return apply_filters( 'sequra_create_order_request_platform_options', $data );
	}

	/**
	 * Check if the builder is allowed for the current settings 
	 */
	public function is_allowed_for( GeneralSettingsResponse $general_settings_response ): bool {
		return true; // TODO: implement this method
		try {
			$generalSettings = $generalSettingsResponse->toArray();
			$stateService    = ServiceRegister::getService( UIStateService::class );
			$isOnboarding    = StoreContext::doWithStore( $this->storeId, array( $stateService, 'isOnboardingState' ), array( true ) );
			$this->quote     = $this->quoteRepository->getActive( $this->cartId );
			$merchantId      = $this->getMerchantId();

			if ( ! $merchantId || $isOnboarding ) {
				return false;
			}

			if (
				! empty( $generalSettings['allowedIPAddresses'] ) &&
				! empty( $ipAddress = $this->getCustomerIpAddress() ) &&
				! in_array( $ipAddress, $generalSettings['allowedIPAddresses'], true )
			) {
				return false;
			}

			if (
				empty( $generalSettings['excludedProducts'] ) &&
				empty( $generalSettings['excludedCategories'] )
			) {
				return true;
			}

			$this->quote = $this->quoteRepository->getActive( $this->cartId );
			foreach ( $this->quote->getAllVisibleItems() as $item ) {
				if (
					! empty( $generalSettings['excludedProducts'] ) &&
					! empty( $item->getSku() ) &&
					( in_array( $item->getProduct()->getData( 'sku' ), $generalSettings['excludedProducts'], true ) ||
						in_array( $item->getProduct()->getSku(), $generalSettings['excludedProducts'], true ) )
				) {
					return false;
				}

				if ( $item->getIsVirtual() ) {
					return false;
				}

				if (
					! empty( $generalSettings['excludedCategories'] ) &&
					! empty(
						array_intersect(
							$generalSettings['excludedCategories'],
							$this->productService->getAllProductCategories( $item->getProduct()->getCategoryIds() )
						)
					)
				) {
					return false;
				}
			}

			return true;
		} catch ( Throwable $exception ) {
			Logger::logWarning(
				'Unexpected error occurred while checking if SeQura payment methods are available.
             Reason: ' . $exception->getMessage() . ' . Stack trace: ' . $exception->getTraceAsString()
			);

			return false;
		}
	}
}
