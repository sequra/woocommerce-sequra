<?php
/**
 * Cart service interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Cart;

use SeQura\WC\Dto\Cart_Info;
use WC_Order;

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
	 * Get product items as an associative array
	 *
	 * @return array<string, mixed>
	 */
	public function get_product_items(): array;

	/**
	 * Get handling items as an associative array
	 *
	 * @return array<string, mixed>
	 */
	public function get_handling_items( ?WC_Order $order = null ): array;
	
	/**
	 * Get discount items as an associative array
	 *
	 * @return array<string, mixed>
	 */
	public function get_discount_items( ?WC_Order $order = null ): array;

	/**
	 * Get extra items as an associative array
	 *
	 * @return array<string, mixed>
	 */
	public function get_extra_items( ?WC_Order $order = null ): array;

	/**
	 * Check if conditions are met for showing seQura in checkout
	 */
	public function is_available_in_checkout(): bool;
}
