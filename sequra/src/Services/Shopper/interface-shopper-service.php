<?php
/**
 * Shopper service interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Shopper;

/**
 * Handle use cases related to Shopper
 */
interface Interface_Shopper_Service {

	/**
	 * Get customer IP
	 */
	public function get_ip(): string;

	/**
	 * Get User Agent
	 */
	public function get_user_agent(): string;

	/**
	 * Check if the shopper is using a mobile device
	 */
	public function is_using_mobile(): bool;

	/**
	 * Get customer date of birth
	 */
	public function get_date_of_birth( int $customer_id ): string;
}
