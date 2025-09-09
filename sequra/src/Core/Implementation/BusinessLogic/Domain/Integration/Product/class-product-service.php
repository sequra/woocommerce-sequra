<?php
/**
 * Implementation of ProductServiceInterface
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Implementation\BusinessLogic\Domain\Integration\Product;

use SeQura\Core\BusinessLogic\Domain\Integration\Product\ProductServiceInterface;

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
    public function getProductsSkuByProductId(string $productId): ?string{
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
    public function isProductVirtual(string $productId): bool{
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
    public function getProductCategoriesByProductId(string $productId): array{
        $_product = wc_get_product( (int) $productId );
        return $_product ? array_map('strval', $_product->get_category_ids()) : array();
    }
}
