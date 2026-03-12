<?php
/**
 * Tests for the Category_Service class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Core\BusinessLogic\Domain\Integration\Category;

use SeQura\Core\BusinessLogic\Domain\GeneralSettings\Models\Category;
use SeQura\WC\Core\Implementation\BusinessLogic\Domain\Integration\Category\Category_Service;
use WP_UnitTestCase;

class CategoryServiceTest extends WP_UnitTestCase {

	/**
	 * @var Category_Service
	 */
	private $service;

	/**
	 * @var int[]
	 */
	private $term_ids = array();

	public function set_up(): void {
		parent::set_up();
		$this->service  = new Category_Service();
		$this->term_ids = array();

		// Create base categories.
		$this->term_ids[] = $this->create_category( 'Cat Apple' );
		$this->term_ids[] = $this->create_category( 'Cat Banana' );
		$this->term_ids[] = $this->create_category( 'Cat Cherry' );
		$this->term_ids[] = $this->create_category( 'Cat Date' );
		$this->term_ids[] = $this->create_category( 'Cat Elderberry' );
	}

	public function tear_down(): void {
		foreach ( $this->term_ids as $id ) {
			wp_delete_term( $id, 'product_cat' );
		}
		parent::tear_down();
	}

	/**
	 * Helper: create a product category.
	 *
	 * @param string $name   Category name.
	 * @param int    $parent_cat Parent term ID (0 = none).
	 *
	 * @return int
	 */
	private function create_category( string $name, int $parent_cat = 0 ): int {
		$args = array( 'parent' => $parent_cat );
		$term = wp_insert_term( $name, 'product_cat', $args );
		$this->assertIsArray( $term );
		return (int) $term['term_id'];
	}

	public function testGetCategories_noParams_returnsAllCategories(): void {
		$categories = $this->service->getCategories();

		$this->assertNotEmpty( $categories );
		foreach ( $categories as $category ) {
			$this->assertInstanceOf( Category::class, $category );
		}
	}

	public function testGetCategories_withPagination_respectsPageAndLimit(): void {
		$page1 = $this->service->getCategories( 1, 2 );
		$page2 = $this->service->getCategories( 2, 2 );

		$this->assertCount( 2, $page1 );
		$this->assertCount( 2, $page2 );

		$page1_ids = array_map( fn( $c ) => $c->getId(), $page1 );
		$page2_ids = array_map( fn( $c ) => $c->getId(), $page2 );
		$this->assertEmpty( array_intersect( $page1_ids, $page2_ids ) );
	}

	public function testGetCategories_withSearch_filtersCategories(): void {
		$results = $this->service->getCategories( null, null, 'Apple' );

		$this->assertNotEmpty( $results );
		foreach ( $results as $category ) {
			$this->assertStringContainsString( 'Apple', $category->getName() );
		}
	}

	public function testGetCategories_withPaginationAndSearch_combinesBoth(): void {
		// Create additional matching categories for pagination.
		$extra_id_1       = $this->create_category( 'Cat Apple Extra1' );
		$extra_id_2       = $this->create_category( 'Cat Apple Extra2' );
		$this->term_ids[] = $extra_id_1;
		$this->term_ids[] = $extra_id_2;

		// Search "Apple", page 1, limit 1 should return 1 result.
		$results = $this->service->getCategories( 1, 1, 'Apple' );
		$this->assertCount( 1, $results );
		$this->assertStringContainsString( 'Apple', $results[0]->getName() );
	}

	public function testGetCategories_nestedCategory_returnsFullPath(): void {
		$parent_id        = $this->create_category( 'Parent Category' );
		$child_id         = $this->create_category( 'Child Category', $parent_id );
		$this->term_ids[] = $parent_id;
		$this->term_ids[] = $child_id;

		// Use a fresh service instance to avoid static cache interference.
		$service    = new Category_Service();
		$categories = $service->getCategoriesByIds( array( (string) $child_id ) );

		$this->assertCount( 1, $categories );
		$this->assertSame( 'Parent Category > Child Category', $categories[0]->getName() );
	}

	public function testGetCategories_sortedByName(): void {
		$categories = $this->service->getCategories();

		$names  = array_map( fn( $c ) => $c->getName(), $categories );
		$sorted = $names;
		usort( $sorted, 'strcasecmp' );

		$this->assertSame( $sorted, $names );
	}

	public function testGetCategoriesByIds_existingIds_returnsCategories(): void {
		$ids     = array_slice( $this->term_ids, 0, 2 );
		$str_ids = array_map( 'strval', $ids );

		$results = $this->service->getCategoriesByIds( $str_ids );

		$this->assertCount( 2, $results );
		$result_ids = array_map( fn( $c ) => (int) $c->getId(), $results );
		foreach ( $ids as $id ) {
			$this->assertContains( $id, $result_ids );
		}
	}

	public function testGetCategoriesByIds_emptyIds_returnsEmptyArray(): void {
		$results = $this->service->getCategoriesByIds( array() );
		$this->assertSame( array(), $results );
	}

	public function testGetCategoriesByIds_nestedCategory_returnsFullPath(): void {
		$parent_id        = $this->create_category( 'Root Cat' );
		$child_id         = $this->create_category( 'Nested Cat', $parent_id );
		$this->term_ids[] = $parent_id;
		$this->term_ids[] = $child_id;

		// Use a fresh service instance to avoid static cache interference.
		$service = new Category_Service();
		$results = $service->getCategoriesByIds( array( (string) $child_id ) );

		$this->assertCount( 1, $results );
		$this->assertSame( 'Root Cat > Nested Cat', $results[0]->getName() );
	}
}
