<?php
/**
 * Product service interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Product;

use DateTime;
use WC_Product;

/**
 * Handle use cases related to products
 */
interface Interface_Product_Service {

	/**
	 * Get desired first charge date for a product
	 * 
	 * @param int|WC_Product $product The product ID or product object
	 * @return DateTime|null|string
	 */
	public function get_desired_first_charge_date( $product, bool $raw_value = false );

	/**
	 * Get product instance
	 * 
	 * @param int|WC_Product $product The product ID or product object
	 */
	public function get_product_instance( $product ): ?WC_Product;

	/**
	 * Check if product is a service
	 * 
	 * @param int|WC_Product $product The product ID or product object
	 */
	public function is_service( $product ): bool;

	/**
	 * Update is service value
	 */
	public function set_is_service( int $product_id, ?string $value ): void;

	/**
	 * Check if product is banned
	 * 
	 * @param int|WC_Product $product The product ID or product object
	 */
	public function is_banned( $product ): bool;

	/**
	 * Update is banned value
	 */
	public function set_is_banned( int $product_id, ?string $value ): void;

	/**
	 * Get product service end date
	 *
	 * @param WC_Product|int $product the product we are building item info for.
	 */
	public function get_service_end_date( $product, bool $raw_value = false ): string;

	/**
	 * Update service end date value
	 */
	public function set_service_end_date( int $product_id, ?string $value ): void;

	/**
	 * Update desired_first_charge_date value
	 */
	public function set_desired_first_charge_date( int $product_id, ?string $value ): void;

	/**
	 * Get registration amount
	 *
	 * @param WC_Product|int $product the product we are building item info for.
	 */
	public function get_registration_amount( $product, bool $to_cents = false ): float;

	/**
	 * Update registration amount value
	 */
	public function set_registration_amount( int $product_id, ?float $value ): void;

	/**
	 * Check if we can display mini widgets
	 */
	public function can_display_mini_widgets(): bool;

	/**
	 * Check if we can display widgets for a product
	 *
	 * @param WC_Product|int $product the product.
	 */
	public function can_display_widgets( $product ): bool;

	/**
	 * Check if we can display widget of a payment method for a product
	 * 
	 * @param WC_Product|int $product the product.
	 * @param array<string, string> $method the payment method. See PaymentMethodsResponse::toArray() output
	 */
	public function can_display_widget_for_method( $product, $method ): bool;
}
