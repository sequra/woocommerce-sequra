<?php
/**
 * Cart service interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Cart;

use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Item\OtherPaymentItem;
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
	public function get_desired_first_charge_on(): ?string;

	/**
	 * Get seQura cart info data from session. If not exists, then initialize it.
	 */
	public function get_cart_info_from_session(): Cart_Info;

	/**
	 * Attempt to clear seQura cart info data from session. 
	 */
	public function clear_cart_info_from_session(): void;

	/**
	 * Get items in cart
	 *
	 * @return array<ProductItem|ServiceItem>
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
	 * Get registration items
	 *
	 * @return OtherPaymentItem[]
	 */
	public function get_registration_items( ?WC_Order $order = null ): array;

	/**
	 * Check if conditions are met for showing seQura in checkout
	 */
	public function is_available_in_checkout(): bool;
}
