<?php
/**
 * StoreContextMock
 *
 * @package SeQura\Core\BusinessLogic\Domain\Multistore
 */

namespace SeQura\WC\Tests\Core\Extension\BusinessLogic\Domain\Multistore;

/**
 * StoreContextMock
 */
interface StoreContextMock {

	/**
	 * Executes callback method with set store id.
	 */
	public function do_with_store( string $storeId, callable $callback, array $params = array() ): void;
}
