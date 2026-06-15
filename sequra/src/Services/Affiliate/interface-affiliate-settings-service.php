<?php
/**
 * Affiliate settings service interface.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services/Affiliate
 */

namespace SeQura\WC\Services\Affiliate;

/**
 * Read and persist the affiliate feature configuration.
 */
interface Interface_Affiliate_Settings_Service {

	/**
	 * Get the affiliate settings for a store.
	 *
	 * @param string|null $store_id Store ID. Current store when null.
	 * @return array<string, mixed> With keys enabled (bool), offer_id (string), security_token (string).
	 */
	public function get_settings( $store_id = null ): array;

	/**
	 * Persist the affiliate settings for a store.
	 *
	 * @param string $store_id       Store ID.
	 * @param bool   $enabled        Whether the feature is enabled.
	 * @param string $offer_id       The TUNE offer ID.
	 * @param string $security_token The security token.
	 */
	public function save_settings( $store_id, $enabled, $offer_id, $security_token ): void;

	/**
	 * Whether the affiliate feature is enabled and fully configured for a store.
	 *
	 * @param string|null $store_id Store ID. Current store when null.
	 */
	public function is_enabled( $store_id = null ): bool;
}
