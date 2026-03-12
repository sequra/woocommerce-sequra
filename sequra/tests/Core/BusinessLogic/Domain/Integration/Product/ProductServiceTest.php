<?php
/**
 * Tests for the Product_Service class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Core\BusinessLogic\Domain\Integration\Product;

use SeQura\Core\BusinessLogic\Domain\Product\Model\ShopProduct;
use SeQura\WC\Core\Implementation\BusinessLogic\Domain\Integration\Product\Product_Service;
use WP_UnitTestCase;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

class ProductServiceTest extends WP_UnitTestCase {

	/**
	 * @var Product_Service
	 */
	private $service;

	/**
	 * @var int[]
	 */
	private $product_ids = array();

	public function set_up(): void {
		parent::set_up();
		global $wpdb;
		$this->service     = new Product_Service( $wpdb );
		$this->product_ids = array();

		// Create 5 test products.
		$product_a = $this->create_product( 'Product Alpha', 'sku-alpha', false );
		$product_b = $this->create_product( 'Product Beta', 'sku-beta', false );
		$product_c = $this->create_product( 'Product Gamma', 'sku-gamma', true );
		$product_d = $this->create_product( 'Product Delta', 'sku-delta', false );
		$product_e = $this->create_product( 'Product Epsilon', 'sku-epsilon', false );

		$this->product_ids = array( $product_a, $product_b, $product_c, $product_d, $product_e );
	}

	public function tear_down(): void {
		foreach ( $this->product_ids as $id ) {
			$product = wc_get_product( $id );
			if ( $product ) {
				$product->delete( true );
			}
		}
		parent::tear_down();
	}

	/**
	 * Helper: create a WC product.
	 *
	 * @param string $name    Product name.
	 * @param string $sku     Product SKU.
	 * @param bool   $virtual Whether the product is virtual.
	 * @param int[]  $cat_ids Category term IDs to assign.
	 *
	 * @return int
	 */
	private function create_product( string $name, string $sku, bool $virtual, array $cat_ids = array() ): int {
		$product = new \WC_Product_Simple();
		$product->set_name( $name );
		$product->set_sku( $sku );
		$product->set_virtual( $virtual );
		$product->set_status( 'publish' );
		if ( ! empty( $cat_ids ) ) {
			$product->set_category_ids( $cat_ids );
		}
		return $product->save();
	}

	public function testGetProductsSkuByProductId_existingProduct_returnsSku(): void {
		$id  = $this->product_ids[0];
		$sku = $this->service->getProductsSkuByProductId( (string) $id );
		$this->assertSame( 'sku-alpha', $sku );
	}

	public function testGetProductsSkuByProductId_nonExistentProduct_returnsNull(): void {
		$sku = $this->service->getProductsSkuByProductId( '999999999' );
		$this->assertNull( $sku );
	}

	public function testIsProductVirtual_virtualProduct_returnsTrue(): void {
		// Product Gamma (index 2) is virtual.
		$id = $this->product_ids[2];
		$this->assertTrue( $this->service->isProductVirtual( (string) $id ) );
	}

	public function testIsProductVirtual_physicalProduct_returnsFalse(): void {
		$id = $this->product_ids[0];
		$this->assertFalse( $this->service->isProductVirtual( (string) $id ) );
	}

	public function testIsProductVirtual_nonExistentProduct_returnsFalse(): void {
		$this->assertFalse( $this->service->isProductVirtual( '999999999' ) );
	}

	public function testGetProductCategoriesByProductId_withCategories_returnsCategoryIds(): void {
		// Create a category and a product with that category.
		$term = wp_insert_term( 'Test Cat ' . uniqid(), 'product_cat' );
		$this->assertIsArray( $term );
		$cat_id     = (int) $term['term_id'];
		$product_id = $this->create_product( 'Cat Product', 'sku-cat', false, array( $cat_id ) );

		$result = $this->service->getProductCategoriesByProductId( (string) $product_id );

		$this->assertIsArray( $result );
		$this->assertContains( (string) $cat_id, $result );

		// Cleanup.
		$product = wc_get_product( $product_id );
		if ( $product ) {
			$product->delete( true );
		}
		wp_delete_term( $cat_id, 'product_cat' );
	}

	public function testGetProductCategoriesByProductId_noCategories_returnsEmptyArray(): void {
		$id     = $this->product_ids[0];
		$result = $this->service->getProductCategoriesByProductId( (string) $id );
		$this->assertIsArray( $result );
	}

	public function testGetProductCategoriesByProductId_nonExistentProduct_returnsEmptyArray(): void {
		$result = $this->service->getProductCategoriesByProductId( '999999999' );
		$this->assertSame( array(), $result );
	}

	public function testGetShopProducts_returnsShopProductInstances(): void {
		$products = $this->service->getShopProducts( 1, 100, '' );

		$this->assertNotEmpty( $products );
		foreach ( $products as $product ) {
			$this->assertInstanceOf( ShopProduct::class, $product );
		}
	}

	public function testGetShopProducts_pagination_respectsPageAndLimit(): void {
		// Request page 2 with limit 2 — should return 2 products.
		$page2 = $this->service->getShopProducts( 2, 2, '' );
		$this->assertCount( 2, $page2 );

		// Request page 1 with limit 2 — should return the first 2 products.
		$page1 = $this->service->getShopProducts( 1, 2, '' );
		$this->assertCount( 2, $page1 );

		// Products on page 1 and page 2 should differ.
		$page1_ids = array_map( fn( $p ) => $p->getId(), $page1 );
		$page2_ids = array_map( fn( $p ) => $p->getId(), $page2 );
		$this->assertEmpty( array_intersect( $page1_ids, $page2_ids ) );
	}

	public function testGetShopProducts_search_filtersProductsByName(): void {
		$results = $this->service->getShopProducts( 1, 100, 'Gamma' );
		$this->assertNotEmpty( $results );
		foreach ( $results as $product ) {
			$this->assertStringContainsString( 'Gamma', $product->getName() );
		}
	}

	public function testGetShopProductByIds_existingIds_returnsMatchingProducts(): void {
		$ids     = array_slice( $this->product_ids, 0, 2 );
		$str_ids = array_map( 'strval', $ids );

		$results = $this->service->getShopProductByIds( $str_ids );

		$this->assertCount( 2, $results );
		$result_ids = array_map( fn( $p ) => (int) $p->getId(), $results );
		foreach ( $ids as $id ) {
			$this->assertContains( $id, $result_ids );
		}
	}

	public function testGetShopProductByIds_emptyIds_returnsEmptyArray(): void {
		$results = $this->service->getShopProductByIds( array() );
		$this->assertSame( array(), $results );
	}
}
