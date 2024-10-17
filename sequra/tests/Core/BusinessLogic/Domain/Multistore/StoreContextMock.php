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
	 *
	 * @param string $storeId
	 * @param callable $callback
	 * @param array $params
	 *
	 * @throws Exception
	 *
	 * @return mixed
	 */
	public function do_with_store( $storeId, $callback, $params = array() );
}
