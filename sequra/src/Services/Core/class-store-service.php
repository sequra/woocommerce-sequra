<?php
/**
 * Wrapper to ease the read and write of configuration values.
 * Delegate to the ConfigurationManager instance to access the data in the database.
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Services\Core;

use SeQura\Core\BusinessLogic\Domain\Integration\Store\StoreServiceInterface;
use SeQura\Core\BusinessLogic\Domain\Stores\Models\Store;

/**
 * Wrapper to ease the read and write of configuration values.
 */
class Store_Service implements StoreServiceInterface {

	/**
	 * Returns shop domain/url.
	 *
	 * @return string
	 */
	public function getStoreDomain(): string {
		return get_site_url();
	}

	/**
	 * Returns all stores within a multiple environment.
	 *
	 * @return Store[]
	 */
	public function getStores(): array {
		$stores = array();
		if ( function_exists( 'get_sites' ) && class_exists( 'WP_Site_Query' ) ) {
			/**
			 * Available site
			 *
			 * @var WP_Site $site
			 */
			foreach ( get_sites() as $site ) {
				$stores[] = new Store( (string) $site->blog_id, get_bloginfo( 'name', '', $site->blog_id ) );
			}
		} else {
			$stores[] = $this->getDefaultStore();
		}
		return $stores;
	}

	/**
	 * Returns current active store.
	 *
	 * @return Store|null
	 */
	public function getDefaultStore(): ?Store {
		return new Store( (string) get_current_blog_id(), get_bloginfo( 'name' ) );
	}

	/**
	 * Returns Store object based on id given as first parameter.
	 *
	 * @param string $id
	 *
	 * @return Store|null
	 */
	public function getStoreById( string $id ): ?Store {
		$stores = $this->getStores();
		foreach ( $stores as $store ) {
			if ( $store->getStoreId() === $id ) {
				return $store;
			}
		}
		return null;
	}
}
