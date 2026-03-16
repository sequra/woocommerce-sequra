<?php
/**
 * Integration tests for the Repository caching layer.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Repositories;

use SeQura\Core\BusinessLogic\Domain\Order\Models\SeQuraOrder;
use SeQura\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use SeQura\Core\Infrastructure\ServiceRegister;
use SeQura\WC\Repositories\Cache_Repository;
use SeQura\WC\Repositories\Interface_Cache_Repository;
use SeQura\WC\Repositories\Repository;
use SeQura\WC\Repositories\SeQura_Order_Repository;
use SeQura\WC\Tests\Fixtures\SeQuraOrderTable;
use WP_UnitTestCase;

class RepositoryCachingTest extends WP_UnitTestCase {

	/**
	 * @var SeQura_Order_Repository
	 */
	private $repository;

	/**
	 * @var SeQuraOrderTable
	 */
	private $order_table;

	public function set_up() {
		parent::set_up();
		ServiceRegister::registerService(
			\wpdb::class,
			function () {
				global $wpdb;
				return $wpdb;
			}
		);
		ServiceRegister::registerService(
			Interface_Cache_Repository::class,
			function () {
				return new Cache_Repository();
			}
		);
		$this->reset_caches();
		$this->order_table = new SeQuraOrderTable();
		$this->order_table->fill_with_sample_data();
		$this->repository = new SeQura_Order_Repository();
		$this->repository->setEntityClass( SeQuraOrder::class );
	}

	public function tear_down() {
		$this->order_table->remove_table( true );
		$this->order_table->reset();
		$this->reset_caches();
		parent::tear_down();
	}

	// --- Bounded select caching ---

	public function testSelect_boundedQuery_secondCallReturnsCachedResult() {
		// Setup: first call populates the cache.
		$filter = new QueryFilter();
		$filter->setLimit( 10 );
		$first_result = $this->repository->select( $filter );

		// Directly delete a row from the DB, bypassing the repository write path.
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}sequra_order WHERE id = 1" );

		// Execute: second call with the same filter must hit the cache (stale result).
		$second_result = $this->repository->select( $filter );

		$this->assertCount( count( $first_result ), $second_result );
	}

	public function testSelect_unboundedQuery_doesNotCache() {
		// Setup: first call without a LIMIT should NOT be cached.
		$first_result = $this->repository->select();

		// Directly delete a row from the DB.
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}sequra_order WHERE id = 1" );

		// Execute: second call must reach the DB and reflect the deletion.
		$second_result = $this->repository->select();

		$this->assertCount( count( $first_result ) - 1, $second_result );
	}

	// --- Count caching ---

	public function testCount_secondCallReturnsCachedResult() {
		// Setup: first call populates the cache.
		$first_count = $this->repository->count();

		// Directly delete a row.
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}sequra_order WHERE id = 1" );

		// Execute: second call must return the cached count.
		$second_count = $this->repository->count();

		$this->assertEquals( $first_count, $second_count );
	}

	// --- Write invalidation ---

	public function testSave_insertsNewEntity_invalidatesCountCache() {
		$first_count = $this->repository->count();

		$this->repository->save( $this->new_entity() );
		$second_count = $this->repository->count();

		$this->assertEquals( $first_count + 1, $second_count );
	}

	public function testSave_updatesExistingEntity_invalidatesSelectCache() {
		// Setup: prime the select cache.
		$filter = new QueryFilter();
		$filter->where( 'id', '=', 1 );
		$filter->setLimit( 1 );
		$entity = $this->repository->selectOne( $filter );

		// Execute: update via save() and re-query through the repository.
		$this->repository->save( $entity );
		$second_result = $this->repository->selectOne( $filter );

		// Cache was invalidated, so the entity is still retrievable (no stale miss).
		$this->assertNotNull( $second_result );
		$this->assertEquals( $entity->getId(), $second_result->getId() );
	}

	public function testDelete_removesEntity_invalidatesCountCache() {
		$filter = new QueryFilter();
		$filter->where( 'id', '=', 1 );
		$filter->setLimit( 1 );
		$entity      = $this->repository->selectOne( $filter );
		$first_count = $this->repository->count();

		$this->repository->delete( $entity );
		$second_count = $this->repository->count();

		$this->assertEquals( $first_count - 1, $second_count );
	}

	public function testDeleteAll_invalidatesCountCache() {
		// Prime the count cache.
		$this->repository->count();

		$this->repository->delete_all();
		$count_after = $this->repository->count();

		$this->assertEquals( 0, $count_after );
	}

	// --- Cache disabled filter ---

	public function testSelect_cacheDisabled_doesNotCacheResults() {
		add_filter( 'sequra_cache_enabled', '__return_false' );
		$this->reset_cache_enabled_flag();

		try {
			$filter = new QueryFilter();
			$filter->setLimit( 10 );
			$this->repository->select( $filter );

			// Directly delete a row.
			global $wpdb;
			$wpdb->query( "DELETE FROM {$wpdb->prefix}sequra_order WHERE id = 1" );

			// With caching disabled the second call must hit the DB and reflect the deletion.
			$second_result = $this->repository->select( $filter );
			$this->assertCount( 2, $second_result );
		} finally {
			remove_filter( 'sequra_cache_enabled', '__return_false' );
			$this->reset_cache_enabled_flag();
		}
	}

	public function testCount_cacheDisabled_doesNotCacheResults() {
		add_filter( 'sequra_cache_enabled', '__return_false' );
		$this->reset_cache_enabled_flag();

		try {
			$first_count = $this->repository->count();

			// Directly delete a row.
			global $wpdb;
			$wpdb->query( "DELETE FROM {$wpdb->prefix}sequra_order WHERE id = 1" );

			$second_count = $this->repository->count();
			$this->assertEquals( $first_count - 1, $second_count );
		} finally {
			remove_filter( 'sequra_cache_enabled', '__return_false' );
			$this->reset_cache_enabled_flag();
		}
	}

	// --- Helpers ---

	/**
	 * Resets both the static in-memory cache and the Repository::$cache_enabled flag.
	 */
	private function reset_caches(): void {
		$this->reset_static_cache();
		$this->reset_cache_enabled_flag();
	}

	/**
	 * Resets the static in-memory array inside Cache_Repository.
	 */
	private function reset_static_cache(): void {
		Cache_Repository::$static_cache = array();
	}

	/**
	 * Resets the cached result of the 'sequra_cache_enabled' filter so that the
	 * next call to is_cache_enabled() re-evaluates it from scratch.
	 */
	private function reset_cache_enabled_flag(): void {
		Repository::$cache_enabled = null;
	}

	/**
	 * Creates a minimal SeQuraOrder entity suitable for insert tests.
	 */
	private function new_entity(): SeQuraOrder {
		return SeQuraOrder::fromArray(
			array(
				'reference'          => 'test_reference_new',
				'cartId'             => 'test_cart_new',
				'orderRef1'          => '',
				'merchant'           => array( 'id' => 'test_merchant' ),
				'merchantReference'  => array(),
				'merchant_reference' => array(),
				'shippedCart'        => array(),
				'shipped_cart'       => array(),
				'unshippedCart'      => array(),
				'unshipped_cart'     => array(),
				'state'              => '',
				'deliveryMethod'     => array(),
				'delivery_method'    => array(),
				'deliveryAddress'    => array(),
				'delivery_address'   => array(),
				'invoiceAddress'     => array(),
				'invoice_address'    => array(),
				'customer'           => array(
					'given_names'     => 'Test',
					'surnames'        => 'User',
					'email'           => 'test@test.com',
					'logged_in'       => false,
					'language_code'   => 'en',
					'ip_number'       => '127.0.0.1',
					'user_agent'      => 'test',
					'ref'             => 0,
					'company'         => '',
					'vat_number'      => '',
					'previous_orders' => array(),
				),
				'platform'           => array(
					'name'           => 'Test',
					'version'        => '1.0',
					'plugin_version' => '1.0',
					'uname'          => 'Linux',
					'db_name'        => 'mysql',
					'db_version'     => '5.7',
					'php_version'    => '7.4',
				),
				'gui'                => array( 'layout' => 'desktop' ),
				'paymentMethod'      => null,
				'payment_method'     => null,
			)
		);
	}
}
