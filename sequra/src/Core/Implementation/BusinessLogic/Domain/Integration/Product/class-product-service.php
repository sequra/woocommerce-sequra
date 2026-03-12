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
	 * WordPress database access object.
	 *
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * Constructor.
	 *
	 * @param \wpdb $wpdb WordPress database access object.
	 */
	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

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
		$base_args = array(
			'status'  => 'publish',
			'limit'   => $limit,
			'offset'  => ( $page - 1 ) * $limit,
			'orderby' => 'name',
			'order'   => 'ASC',
			'return'  => 'objects',
		);
		$search    = trim( $search );
		$products  = '' === $search
			? wc_get_products( $base_args )
			: $this->search_products( $base_args, $search );

		return array_map( array( $this, 'map_to_shop_product' ), $products );
	}

	/**
	 * Searches products by name or SKU in a single query.
	 *
	 * @param array  $base_args Base query arguments.
	 * @param string $search    Search term.
	 *
	 * @return \WC_Product[]
	 */
	private function search_products( array $base_args, string $search ): array {
		//phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$callback = function ( $clauses, $wp_query ) use ( $search ) {
			$like  = '%' . $this->wpdb->esc_like( $search ) . '%';
			$query = " AND ({$this->wpdb->posts}.post_title LIKE %s OR {$this->wpdb->posts}.ID IN (SELECT post_id FROM {$this->wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value LIKE %s))";
			/** Met static analysis requirements.
			 *
			 * @var literal-string $query */
			$clauses['where'] .= $this->wpdb->prepare( $query, $like, $like ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			return $clauses;
		};

		add_filter( 'posts_clauses', $callback, 10, 2 );
		$products = wc_get_products( $base_args );
		remove_filter( 'posts_clauses', $callback, 10 );

		return $products;
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
