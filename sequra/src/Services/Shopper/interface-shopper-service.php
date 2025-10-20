<?php
/**
 * Shopper service interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Shopper;

use WC_Order;

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
}
