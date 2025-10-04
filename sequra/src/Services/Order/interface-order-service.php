<?php
/**
 * Order service interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Order;

use SeQura\WC\Dto\Cart_Info;
use SeQura\WC\Dto\Payment_Method_Data;
use WC_Order;

/**
 * Handle use cases related to Order
 */
interface Interface_Order_Service {

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
	 * Set cart info for the order
	 * 
	 * @param Cart_Info $cart_info Cart info
	 */
	public function set_cart_info( WC_Order $order, $cart_info ): void;

	/**
	 * Set cart info if it is not already set
	 */
	public function create_cart_info( WC_Order $order ): ?Cart_Info;

	/**
	 * Get the meta key used to store the sent to seQura value.
	 */
	public function get_sent_to_sequra_meta_key(): string;

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

	/**
	 * Get the total amount of the order
	 * 
	 * @param WC_Order $order
	 * @return float|int
	 */
	public function get_total( $order, $in_cents = true );

	/**
	 * Cleanup orders
	 * 
	 * @return void
	 */
	public function cleanup_orders();

	/**
	 * Get the Merchant ID
	 * 
	 * @param WC_Order $order
	 * @return string
	 */
	public function get_merchant_id( $order );

	/**
	 * Check if the migration process is complete
	 * 
	 * @return bool True if they are missing indexes, false otherwise
	 */
	public function is_migration_complete();

	/**
	 * Execute the migration process
	 */
	public function migrate_data();

	/**
	 * Get the order completion date or current date if not completed.
	 * 
	 * @param WC_Order $order
	 * @return string
	 */
	public function get_order_completion_date( $order );
}
