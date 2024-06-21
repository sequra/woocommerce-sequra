<?php
/**
 * Payment service interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Payment;

use Exception;
use SeQura\Core\BusinessLogic\AdminAPI\AdminAPI;
use SeQura\WC\Dto\Payment_Method_Data;
use SeQura\WC\Services\Core\Configuration;
use SeQura\WC\Services\Core\Interface_Create_Order_Request_Builder;
use SeQura\WC\Services\I18n\Interface_I18n;
use Throwable;
use WC_Order;

/**
 * Handle use cases related to payments
 */
class Payment_Service implements Interface_Payment_Service {

	private const META_KEY_METHOD_TITLE = '_sq_method_title';
	private const META_KEY_PRODUCT      = '_sq_product';
	private const META_KEY_CAMPAIGN     = '_sq_campaign';

	/**
	 * Configuration
	 *
	 * @var Configuration
	 */
	private $configuration;

	/**
	 * I18n service
	 *
	 * @var Interface_I18n
	 */
	private $i18n;

	/**
	 * Create order request builder
	 *
	 * @var Interface_Create_Order_Request_Builder
	 */
	private $create_order_request_builder;

	/**
	 * Constructor
	 */
	public function __construct( 
		Configuration $configuration,
		Interface_I18n $i18n,
		Interface_Create_Order_Request_Builder $create_order_request_builder
	) {
		$this->configuration                = $configuration;
		$this->i18n                         = $i18n;
		$this->create_order_request_builder = $create_order_request_builder;
	}

	/**
	 * Get payment gateway ID
	 */
	public function get_payment_gateway_id(): string {
		return 'sequra';
	}

	/**
	 * Get payment gateway webhook identifier
	 */
	public function get_payment_gateway_webhook(): string {
		return 'woocommerce_sequra';
	}

	/**
	 * Get payment gateway webhook identifier
	 */
	public function get_notify_url( WC_Order $order ): string {
		return add_query_arg(
			array(
				'order'  => '' . $order->get_id(),
				'wc-api' => $this->get_payment_gateway_webhook(),
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
	 * Get current merchant ID
	 */
	public function get_merchant_id(): ?string {
		$store_id = $this->configuration->get_store_id();
		
		$countries = AdminAPI::get()
		->countryConfiguration( $store_id )
		->getCountryConfigurations()
		->toArray();

		$merchant        = null;
		$current_country = $this->i18n->get_current_country();

		foreach ( $countries as $country ) {
			if ( $country['countryCode'] === $current_country ) {
				$merchant = $country['merchantId'];
				break;
			}
		}
		return empty( $merchant ) ? null : $merchant;
	}

	/**
	 * Get order meta value by key from a seQura order.
	 * If the order is not a seQura order an empty string is returned.
	 */
	private function get_order_meta( WC_Order $order, $meta_key ): string {
		if ( $order->get_payment_method() !== $this->get_payment_gateway_id() ) {
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
	 * Save required metadata for the order.
	 * Returns true if the metadata was saved, false otherwise.
	 */
	public function set_order_metadata( WC_Order $order, ?Payment_Method_Data $dto ): bool {
		if ( ! $dto || ! $this->is_payment_method_data_valid( $dto ) ) {
			return false;
		}

		$order->update_meta_data( self::META_KEY_PRODUCT, $dto->product );
		if ( ! empty( $dto->campaign ) ) {
			$order->update_meta_data( self::META_KEY_CAMPAIGN, $dto->campaign );
		}
		$order->update_meta_data( self::META_KEY_METHOD_TITLE, $dto->title );
		$order->save();
		return true;
	}

	/**
	 * Sign the string using HASH_ALGO and merchant's password
	 */
	public function sign( string $message ): string {
		return hash_hmac( 'sha256', $message ?? '', $this->configuration->get_password() );
	}
}
