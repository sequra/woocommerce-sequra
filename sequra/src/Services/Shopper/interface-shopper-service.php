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
	 * Check if the IP is allowed in SeQura settings
	 * 
	 * @param ?string $ip The IP address to check. If null, the current shopper's IP will be used.
	 */
	public function is_ip_allowed( ?string $ip = null ): bool;

	/**
	 * Get User Agent
	 */
	public function get_user_agent(): string;

	/**
	 * Check if the shopper is using a mobile device
	 */
	public function is_using_mobile(): bool;

	/**
	 * Check if the User Agent is a bot
	 */
	public function is_bot(): bool;

	/**
	 * Get customer date of birth
	 */
	public function get_date_of_birth( int $customer_id ): string;
}
