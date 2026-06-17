<?php
/**
 * Affiliate configuration provider (server-provisioned).
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services/Affiliate
 */

namespace SeQura\WC\Services\Affiliate;

/**
 * Provides the affiliate configuration provisioned server-side (Option A, autoconfiguration).
 *
 * TODO (QRD-7898): back this by the integration-core affiliate settings service once that
 * core version is released and vendored. Until then it reports the feature as disabled so
 * the plugin stays dormant (opt-in, off by default) and never reads stale local config.
 */
class Pushed_Affiliate_Config_Provider implements Interface_Affiliate_Config_Provider {

	/**
	 * Get the affiliate settings for a store.
	 *
	 * @param string|null $store_id Store ID. Current store when null.
	 * @return array{enabled: bool, offer_id: string, security_token: string}
	 */
	public function get_settings( $store_id = null ): array {
		return array(
			'enabled'        => false,
			'offer_id'       => '',
			'security_token' => '',
		);
	}

	/**
	 * Whether the affiliate feature is enabled and fully configured for a store.
	 *
	 * @param string|null $store_id Store ID. Current store when null.
	 */
	public function is_enabled( $store_id = null ): bool {
		return false;
	}
}
