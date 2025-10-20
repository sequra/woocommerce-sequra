<?php
/**
 * StoreIdProvider
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Extension\BusinessLogic\Domain\Integration\Store;

use SeQura\Core\BusinessLogic\Domain\Integration\Store\StoreIdProvider;

/**
 * StoreIdProvider
 *
 * @package SeQura\Core\BusinessLogic\Domain\Integration\Store
 */
class Store_Id_Provider extends StoreIdProvider {

	/**
	 * Override to provide current store ID according to the platform context.
	 *
	 * @return string
	 */
	public function getCurrentStoreId(): string {
		return (string) get_current_blog_id();
	}
}
