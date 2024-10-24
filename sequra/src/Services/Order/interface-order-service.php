<?php
/**
 * Order service interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Order;

use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Address;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Customer;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\DeliveryMethod;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\PreviousOrder;
use SeQura\WC\Dto\Cart_Info;
use SeQura\WC\Dto\Payment_Method_Data;
use WC_Order;

/**
 * Handle use cases related to Order
 */
interface Interface_Order_Service {

	/**
	 * Get delivery method
	 */
	public function get_delivery_method( ?WC_Order $order ): DeliveryMethod;

	/**
	 * Get client first name. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_first_name( ?WC_Order $order, $is_delivery = true ): string;

	/**
	 * Get client last name. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_last_name( ?WC_Order $order, $is_delivery = true ): string;

	/**
	 * Get client company. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_company( ?WC_Order $order, $is_delivery = true ): string;

	/**
	 * Get client address's first line. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_address_1( ?WC_Order $order, $is_delivery = true ): string;

	/**
	 * Get client address's second line. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_address_2( ?WC_Order $order, $is_delivery = true ): string;

	/**
	 * Get client postcode. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_postcode( ?WC_Order $order, $is_delivery = true ): string;

	/**
	 * Get client city. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_city( ?WC_Order $order, $is_delivery = true ): string;

	/**
	 * Get client country code. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_country( ?WC_Order $order, $is_delivery = true ): string;

	/**
	 * Get client state. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_state( ?WC_Order $order, $is_delivery = true ): string;

	/**
	 * Get client phone. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_phone( ?WC_Order $order, $is_delivery = true ): string;

	/**
	 * Get client email. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_email( ?WC_Order $order ): string;

	/**
	 * Get client vat number. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_vat( ?WC_Order $order, $is_delivery = true ): string;

	/**
	 * Get client NIN number. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_nin( ?WC_Order $order ): ?string;
	
	/**
	 * Get date of birth. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_dob( ?WC_Order $order ): ?string;

	/**
	 * Get shopper title. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_shopper_title( ?WC_Order $order ): ?string;

	/**
	 * Get shopper created at date. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_shopper_created_at( ?WC_Order $order ): ?string;

	/**
	 * Get shopper updated at date. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_shopper_updated_at( ?WC_Order $order ): ?string;

	/**
	 * Get shopper rating. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_shopper_rating( ?WC_Order $order ): ?int;

	/**
	 * Get previous orders
	 * 
	 * @return PreviousOrder[]
	 */
	public function get_previous_orders( int $customer_id ): array;

	/**
	 * Get the seQura payment method title for the order.
	 * If the order is not a seQura order an empty string is returned.
	 */
	public function get_payment_method_title( WC_Order $order ): string;

	/**
	 * Get the seQura product for the order.
	 * If the value is not found an empty string is returned.
	 */
	public function get_product( WC_Order $order ): string;

	/**
	 * Get the seQura campaign for the order.
	 * If the value is not found an empty string is returned.
	 */
	public function get_campaign( WC_Order $order ): string;

	/**
	 * Save required metadata for the order.
	 * Returns true if the metadata was saved, false otherwise.
	 */
	public function set_order_metadata( WC_Order $order, ?Payment_Method_Data $data, ?Cart_Info $cart_info ): bool;

	/**
	 * Get the seQura cart info for the order.
	 * If the value is not found null is returned.
	 */
	public function get_cart_info( WC_Order $order ): ?Cart_Info;

	/**
	 * Set cart info if it is not already set
	 */
	public function create_cart_info( WC_Order $order ): ?Cart_Info;
	
	/**
	 * Get payment gateway webhook identifier
	 */
	public function get_ipn_url( WC_Order $order, string $store_id ): string;

	/**
	 * Get payment gateway webhook identifier
	 */
	public function get_return_url( WC_Order $order ): string;

	/**
	 * Get payment gateway webhook identifier
	 */
	public function get_event_url( WC_Order $order, string $store_id ): string;

	/**
	 * Get the meta key used to store the sent to seQura value.
	 */
	public function get_sent_to_sequra_meta_key(): string;

	/**
	 * Get customer for the order
	 */
	public function get_customer( ?WC_Order $order, string $lang, int $fallback_user_id = 0, string $fallback_ip = '', string $fallback_user_agent = '' ): Customer;

	/**
	 * Get delivery or invoice address
	 */
	public function get_address( ?WC_Order $order, bool $is_delivery ): Address;

	/**
	 * Set the order as sent to seQura
	 */
	public function set_as_sent_to_sequra( WC_Order $order ): void;

	/**
	 * Call the Order Update API to sync the order status with SeQura
	 */
	public function update_sequra_order_status( WC_Order $order, string $old_store_status, string $new_store_status ): void;

	/**
	 * Update the order amount in SeQura after a refund
	 *
	 * @throws Throwable 
	 */
	public function handle_refund( WC_Order $order, float $amount ): void;

	/**
	 * Get the link to the SeQura back office for the order
	 */
	public function get_link_to_sequra_back_office( WC_Order $order ): ?string;
}
