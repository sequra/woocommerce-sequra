<?php
/**
 * Affiliate configuration provider (server-provisioned).
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services/Affiliate
 */

namespace SeQura\WC\Services\Affiliate;

use SeQura\Core\BusinessLogic\Domain\Affiliate\Models\AffiliateSettings;
use SeQura\Core\BusinessLogic\Domain\Affiliate\Services\AffiliateSettingsService;
use SeQura\Core\BusinessLogic\Domain\Multistore\StoreContext;

/**
 * Reads the affiliate configuration provisioned server-side (Option A, autoconfiguration),
 * delegating to the integration-core affiliate settings service.
 *
 * The merchant never types these values: they are delivered through the seQura config-push
 * (or the connect-time configuration_data payload), persisted per store by the core, and only
 * read from here. The core read service already returns a safe disabled default when nothing
 * is stored, so this provider keeps the plugin dormant until a real config arrives.
 */
class Pushed_Affiliate_Config_Provider implements Interface_Affiliate_Config_Provider {

	/**
	 * Core affiliate settings service (the reusable read service).
	 *
	 * @var AffiliateSettingsService
	 */
	private $affiliate_settings_service;

	/**
	 * Constructor.
	 *
	 * @param AffiliateSettingsService $affiliate_settings_service Core affiliate settings read service.
	 */
	public function __construct( AffiliateSettingsService $affiliate_settings_service ) {
		$this->affiliate_settings_service = $affiliate_settings_service;
	}

	/**
	 * Get the affiliate settings for a store.
	 *
	 * @param string|null $store_id Store ID. Current store when null.
	 * @return array{enabled: bool, offer_id: string, security_token: string}
	 */
	public function get_settings( $store_id = null ): array {
		$settings = $this->resolve_settings( $store_id );
		return array(
			'enabled'        => $settings->isEnabled(),
			'offer_id'       => $settings->getOfferId(),
			'security_token' => $settings->getSecurityToken(),
		);
	}

	/**
	 * Whether the affiliate feature is enabled and fully configured for a store.
	 *
	 * @param string|null $store_id Store ID. Current store when null.
	 */
	public function is_enabled( $store_id = null ): bool {
		return $this->resolve_settings( $store_id )->isEnabled();
	}

	/**
	 * Resolve the affiliate settings for the current store, or for an explicit store id.
	 *
	 * @param string|null $store_id Store ID. Current store when null.
	 * @return AffiliateSettings
	 */
	private function resolve_settings( $store_id ): AffiliateSettings {
		if ( null === $store_id ) {
			return $this->affiliate_settings_service->getAffiliateSettings();
		}

		return StoreContext::doWithStore(
			(string) $store_id,
			function () {
				return $this->affiliate_settings_service->getAffiliateSettings();
			}
		);
	}
}
