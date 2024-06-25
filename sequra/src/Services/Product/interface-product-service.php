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
	 * @param int|WC_Product $product_id The product ID or product object
	 */
	public function get_desired_first_charge_date( $product ): ?DateTime;

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
	 * Check if product is banned
	 * 
	 * @param int|WC_Product $product The product ID or product object
	 */
	public function is_banned( $product ): bool;

	/**
	 * Get service date regex
	 */
	public function get_service_date_regex(): string;

	/**
	 * Get product service end date
	 *
	 * @param WC_Product|int $product the product we are building item info for.
	 */
	public function get_service_end_date( $product ): string;

	/**
	 * Get registration amount
	 *
	 * @param WC_Product|int $product the product we are building item info for.
	 */
	public function get_registration_amount( $product ): float;
}
