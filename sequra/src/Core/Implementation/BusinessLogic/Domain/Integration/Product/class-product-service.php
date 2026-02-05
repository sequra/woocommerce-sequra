<?php
/**
 * Implementation of ProductServiceInterface
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Implementation\BusinessLogic\Domain\Integration\Product;

use SeQura\Core\BusinessLogic\Domain\Integration\Product\ProductServiceInterface;
use SeQura\Core\BusinessLogic\Domain\Product\Model\ShopProduct;

/**
 * Product Service
 */
class Product_Service implements ProductServiceInterface {

	/**
	 * Returns products SKU based on product ID.
	 * Returns null if product is not supported on integration level.
	 *
	 * @param string $productId
	 *
	 * @return string
	 */
	public function getProductsSkuByProductId( string $productId ): ?string {
		$_product = wc_get_product( (int) $productId );
		return $_product ? $_product->get_sku() : null;
	}

	/**
	 * Returns true if product is virtual.
	 *
	 * @param string $productId
	 *
	 * @return bool
	 */
	public function isProductVirtual( string $productId ): bool {
		$_product = wc_get_product( (int) $productId );
		return $_product ? $_product->get_virtual() : false;
	}

	/**
	 * Returns all categories related to product whose id is given as first parameter.
	 *
	 * @param string $productId
	 *
	 * @return string[]
	 */
	public function getProductCategoriesByProductId( string $productId ): array {
		$_product = wc_get_product( (int) $productId );
		return $_product ? array_map( 'strval', $_product->get_category_ids() ) : array();
	}

	/**
	 * Gets all shop products with their basic information.
	 *
	 * @param int $page Page number for pagination starting from 1.
	 * @param int $limit Number of products to return per page.
	 * @param string $search Search term to filter products by name or SKU.
	 *
	 * @return ShopProduct[]
	 */
	public function getShopProducts( int $page, int $limit, string $search ): array {
		$args = array(
			'status'  => 'publish',
			'limit'   => $limit,
			'offset'  => ( $page - 1 ) * $limit,
			'orderby' => 'name',
			'order'   => 'ASC',
			'return'  => 'objects',
		);

		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		$products = wc_get_products( $args );

		return array_map( array( $this, 'map_to_shop_product' ), $products );
	}

	/**
	 * Gets shop products by their IDs.
	 *
	 * @param string[] $ids
	 *
	 * @return ShopProduct[]
	 */
	public function getShopProductByIds( array $ids ): array {
		if ( empty( $ids ) ) {
			return array();
		}

		$args = array(
			'include' => array_map( 'intval', $ids ),
			'limit'   => -1,
			'return'  => 'objects',
		);

		$products = wc_get_products( $args );

		return array_map( array( $this, 'map_to_shop_product' ), $products );
	}

	/**
	 * Maps a WooCommerce product to a ShopProduct instance.
	 *
	 * @param \WC_Product $product WooCommerce product.
	 *
	 * @return ShopProduct
	 */
	private function map_to_shop_product( $product ): ShopProduct {
		return new ShopProduct( 
			(string) $product->get_id(), 
			$product->get_sku(),  
			$product->get_name()
		);
	}
}
