<?php
/**
 * StoreContextDummy
 *
 * @package SeQura\Core\BusinessLogic\Domain\Multistore
 */

namespace SeQura\WC\Tests\Core\Extension\BusinessLogic\Domain\Multistore;

use Exception;
use SeQura\Core\BusinessLogic\Domain\Multistore\StoreContext as CoreStoreContext;

/**
 * StoreContextDummy
 */
class StoreContext extends CoreStoreContext {

	/**
	 * @var self
	 */
	protected static $instance;

	/**
	 * @var string
	 */
	protected $storeId = '';

	/**
	 * Mock object.
	 * @var StoreContextMock
	 */
	private static $mock;

	/**
	 * StoreContext constructor.
	 * @param StoreContextMock $mock
	 */
	public function __construct( $mock ) {
		self::$mock = $mock;
	}

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
	public static function doWithStore( string $storeId, $callback, array $params = array() ) {
		return self::$mock->do_with_store( $storeId, $callback, $params );
	}
}
