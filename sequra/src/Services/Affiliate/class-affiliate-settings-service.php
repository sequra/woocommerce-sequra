<?php
/**
 * Affiliate settings service.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services/Affiliate
 */

namespace SeQura\WC\Services\Affiliate;

use SeQura\Core\BusinessLogic\Domain\Multistore\StoreContext;

/**
 * Persist the affiliate feature configuration as a per-store WordPress option.
 *
 * Stored in wp_options (not a wp_sequra_entity entity) to keep PR #1 self-contained; the contract is migration-safe.
 */
class Affiliate_Settings_Service implements Interface_Affiliate_Settings_Service {

	private const OPTION_PREFIX = 'sequra_affiliate_settings_';

	/**
	 * Store context.
	 *
	 * @var StoreContext
	 */
	private $store_context;

	/**
	 * Constructor.
	 *
	 * @param StoreContext $store_context Store context.
	 */
	public function __construct( StoreContext $store_context ) {
		$this->store_context = $store_context;
	}

	/**
	 * Get the affiliate settings for a store.
	 *
	 * @param string|null $store_id Store ID. Current store when null.
	 * @return array{enabled: bool, offer_id: string, security_token: string}
	 */
	public function get_settings( $store_id = null ): array {
		$store_id = $this->resolve_store_id( $store_id );
		$stored   = \get_option( self::OPTION_PREFIX . $store_id, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		return array(
			'enabled'        => (bool) ( $stored['enabled'] ?? false ),
			'offer_id'       => (string) ( $stored['offer_id'] ?? '' ),
			'security_token' => (string) ( $stored['security_token'] ?? '' ),
		);
	}

	/**
	 * Persist the affiliate settings for a store.
	 *
	 * @param string $store_id       Store ID.
	 * @param bool   $enabled        Whether the feature is enabled.
	 * @param string $offer_id       The TUNE offer ID.
	 * @param string $security_token The security token.
	 */
	public function save_settings( $store_id, $enabled, $offer_id, $security_token ): void {
		\update_option(
			self::OPTION_PREFIX . $this->resolve_store_id( $store_id ),
			array(
				'enabled'        => (bool) $enabled,
				'offer_id'       => (string) $offer_id,
				'security_token' => (string) $security_token,
			),
			false
		);
	}

	/**
	 * Whether the affiliate feature is enabled and fully configured for a store.
	 *
	 * @param string|null $store_id Store ID. Current store when null.
	 */
	public function is_enabled( $store_id = null ): bool {
		$settings = $this->get_settings( $store_id );
		return $settings['enabled'] && '' !== $settings['offer_id'] && '' !== $settings['security_token'];
	}

	/**
	 * Resolve the store ID, falling back to the current store.
	 *
	 * @param string|null $store_id Store ID.
	 */
	private function resolve_store_id( $store_id ): string {
		if ( null !== $store_id && '' !== $store_id ) {
			return (string) $store_id;
		}
		return (string) $this->store_context->getStoreId();
	}
}
