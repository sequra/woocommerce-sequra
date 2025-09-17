<?php
/**
 * Cart service interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Cart;

use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Item\DiscountItem;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Item\HandlingItem;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Item\OtherPaymentItem;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Item\ProductItem;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Item\RegistrationItem;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Item\ServiceItem;
use SeQura\WC\Dto\Cart_Info;
use WC_Order;
use WC_Product;

/**
 * Handle use cases related to cart
 */
interface Interface_Cart_Service {

	/**
	 * Get closest desired first charge date from cart items
	 */
	public function get_desired_first_charge_on( ?WC_Order $order = null ): ?string;

	/**
	 * Get seQura cart info data from session. If not exists, then initialize it.
	 * 
	 * @param bool $initialize_if_not_exists If true, then initialize cart info if not exists.
	 */
	public function get_cart_info_from_session( $initialize_if_not_exists = true ): ?Cart_Info;

	/**
	 * Attempt to clear seQura cart info data from session. 
	 */
	public function clear_cart_info_from_session(): void;
	
	/**
	 * Check if cart info is valid
	 * 
	 * @param ?Cart_Info $cart_info
	 */
	public function is_cart_info_valid( $cart_info ): bool;

	/**
	 * Get items in cart
	 *
	 * @return array<ProductItem|ServiceItem|RegistrationItem>
	 */
	public function get_items( ?WC_Order $order ): array;

	/**
	 * Get products in cart
	 *
	 * @return array<WC_Product>
	 */
	public function get_products( ?WC_Order $order ): array;

	/**
	 * Get handling items
	 *
	 * @return HandlingItem[]
	 */
	public function get_handling_items( ?WC_Order $order = null ): array;
	
	/**
	 * Get discount items
	 *
	 * @return DiscountItem[]
	 */
	public function get_discount_items( ?WC_Order $order = null ): array;

	/**
	 * Get refund items
	 *
	 * @return OtherPaymentItem[]
	 */
	public function get_refund_items( WC_Order $order = null ): array;

	/**
	 * Create refund item instance
	 */
	public function create_refund_item( ?int $id, float $amount ): OtherPaymentItem;

	/**
	 * Check if conditions are met for showing seQura in checkout
	 */
	public function is_available_in_checkout( ?WC_Order $order = null ): bool;

	/**
	 * Get the total amount of the cart
	 * 
	 * @return float|int
	 */
	public function get_total( $in_cents = true );
}
