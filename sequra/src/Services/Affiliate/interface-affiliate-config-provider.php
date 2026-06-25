<?php
/**
 * Affiliate configuration provider interface.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services/Affiliate
 */

namespace SeQura\WC\Services\Affiliate;

/**
 * Read-only access to the affiliate configuration provisioned server-side (Option A).
 *
 * The plugin no longer lets the merchant type these values; they are delivered through
 * the seQura config-push and read from here.
 */
interface Interface_Affiliate_Config_Provider {

	/**
	 * Get the affiliate settings for a store.
	 *
	 * @param string|null $store_id Store ID. Current store when null.
	 * @return array{enabled: bool, offer_id: string, security_token: string}
	 */
	public function get_settings( $store_id = null ): array;

	/**
	 * Whether the affiliate feature is enabled and fully configured for a store.
	 *
	 * @param string|null $store_id Store ID. Current store when null.
	 */
	public function is_enabled( $store_id = null ): bool;
}
