<?php
/**
 * Implementation of MerchantDataProviderInterface
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Implementation\BusinessLogic\Domain\Integration\Order;

use SeQura\Core\BusinessLogic\Domain\Integration\Order\MerchantDataProviderInterface;
use SeQura\Core\BusinessLogic\Domain\Multistore\StoreContext;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Options;
use SeQura\WC\Services\Cart\Interface_Cart_Service;
use SeQura\WC\Services\Constants\Interface_Constants;
use SeQura\WC\Services\Order\Interface_Current_Order_Provider;
use SeQura\WC\Services\Product\Interface_Product_Service;
use SeQura\WC\Services\Shopper\Interface_Shopper_Service;

/**
 * Implementation of the MerchantDataProviderInterface.
 */
class Merchant_Data_Provider implements MerchantDataProviderInterface {


	/**
	 * Current order provider
	 *
	 * @var Interface_Current_Order_Provider
	 */
	private $current_order_provider;

	/**
	 * Constants
	 *
	 * @var Interface_Constants
	 */
	private $constants;

	/**
	 * Product service
	 *
	 * @var Interface_Product_Service
	 */
	private $product_service;

	/**
	 * Cart service
	 *
	 * @var Interface_Cart_Service
	 */
	private $cart_service;

	/**
	 * Shopper service
	 *
	 * @var Interface_Shopper_Service
	 */
	private $shopper_service;

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
		Interface_Current_Order_Provider $current_order_provider,
		Interface_Constants $constants,
		Interface_Product_Service $product_service,
		Interface_Cart_Service $cart_service,
		Interface_Shopper_Service $shopper_service,
		StoreContext $store_context
	) {
		$this->current_order_provider = $current_order_provider;
		$this->constants              = $constants;
		$this->product_service        = $product_service;
		$this->cart_service           = $cart_service;
		$this->shopper_service        = $shopper_service;
		$this->store_context          = $store_context;
	}

	/**
	 * Returns approved callback
	 *
	 * @return ?string
	 */
	public function getApprovedCallback(): ?string {
		return null;
	}

	/**
	 * Returns rejected callback
	 *
	 * @return ?string
	 */
	public function getRejectedCallback(): ?string {
		return null;
	}

	/**
	 * Returns part payment details
	 *
	 * @return ?string
	 */
	public function getPartPaymentDetailsGetter(): ?string {
		return null;
	}

	/**
	 * Returns notify url
	 *
	 * @return ?string
	 */
	public function getNotifyUrl(): ?string {
		$order = $this->current_order_provider->get();
		if ( ! $order ) {
			return null;
		}

		return \add_query_arg(
			array(
				'order'    => (string) $order->get_id(),
				'wc-api'   => $this->constants->get_ipn_webhook(),
				'store_id' => $this->store_context->getStoreId(),
			),
			\home_url( '/' )
		);
	}

	/**
	 * Returns return url for given cart id
	 *
	 * @param string $cartId
	 *
	 * @return ?string
	 */
	public function getReturnUrlForCartId( string $cartId ): ?string {
		$order = $this->current_order_provider->get();
		if ( ! $order ) {
			return null;
		}

		return \add_query_arg(
			array(
				'order'      => (string) $order->get_id(),
				'sq_product' => 'SQ_PRODUCT_CODE',
				'wc-api'     => $this->constants->get_return_webhook(),
			),
			\home_url( '/' )
		);
	}

	/**
	 * Returns edit url
	 *
	 * @return ?string
	 */
	public function getEditUrl(): ?string {
		return null;
	}

	/**
	 * Returns abort url
	 *
	 * @return ?string
	 */
	public function getAbortUrl(): ?string {
		return null;
	}

	/**
	 * Returns approved url
	 *
	 * @return ?string
	 */
	public function getApprovedUrl(): ?string {
		return null;
	}

	/**
	 * Returns options
	 *
	 * @return Options|null
	 */
	public function getOptions(): ?Options {
		$options = null;
		$order   = $this->current_order_provider->get();
		$country = $this->shopper_service->get_country( $order );

		if ( $this->product_service->is_enabled_for_services( $country ) ) {
			$desired_first_charge_on = $this->product_service->is_allow_first_service_payment_delay( $country ) ? $this->cart_service->get_desired_first_charge_on( $order ) : null;
			/**
			 * Allow modify the addresses_may_be_missing value.Accept null, true or false.
			 *
			 * @since 3.0.0
			 */
			$addresses_may_be_missing = \apply_filters( 'sequra_merchant_options_addresses_may_be_missing', true );
			if ( ! is_bool( $addresses_may_be_missing ) ) {
				$addresses_may_be_missing = true;
			}

			$options = new Options(
				false,
				false,
				$addresses_may_be_missing,
				false,
				$desired_first_charge_on
			);
		}

		/**
		 * Filter the merchant options.
		 *
		 * @since 3.0.0
		 */
		$filtered_options = \apply_filters( 'sequra_create_order_request_merchant_options', $options );
		if ( null !== $filtered_options && ! $filtered_options instanceof Options ) {
			return $options;
		}
		return $filtered_options;
	}

	/**
	 * Returns events webhook url
	 *
	 * @return string
	 */
	public function getEventsWebhookUrl(): string {
		return \add_query_arg(
			array(
				'order'    => $this->get_order_id(),
				'wc-api'   => $this->constants->get_event_webhook(),
				'store_id' => $this->store_context->getStoreId(),
			),
			\home_url( '/' )
		);
	}

	/**
	 * Returns events webhook parameters for cart id
	 *
	 * @param string $cartId
	 *
	 * @return string[]
	 */
	public function getEventsWebhookParametersForCartId( string $cartId ): array {
		return array( 'order' => $this->get_order_id() );
	}

	/**
	 * Returns notifications parameters for cart id
	 *
	 * @param string $cartId
	 *
	 * @return string[]
	 */
	public function getNotificationParametersForCartId( string $cartId ): array {
		return array(
			'order'  => $this->get_order_id(),
			'result' => '0',
		);
	}

	/**
	 * Get current order ID or empty string if no order is set
	 */
	private function get_order_id(): string {
		$order = $this->current_order_provider->get();
		return $order ? (string) $order->get_id() : '';
	}
}
