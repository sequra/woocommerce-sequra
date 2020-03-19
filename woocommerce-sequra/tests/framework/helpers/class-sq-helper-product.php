<?php

/**
 * Class WC_Helper_Product.
 *
 * This helper class should ONLY be used for unit tests!.
 */
class SQ_Helper_Product extends WC_Helper_Product {

	/**
	 * Create simple virtual product.
	 *
	 * @since 2.3
	 * @return WC_Product_Simple
	 */
	public static function create_simple_virtual_product() {
		$product = new WC_Product_Simple();
		$product->set_props(
			array(
				'name'          => 'Dummy Product',
				'regular_price' => 10,
				'sku'           => 'DUMMY SKU',
				'manage_stock'  => false,
				'tax_status'    => 'taxable',
				'downloadable'  => false,
				'virtual'       => true,
				'stock_status'  => 'instock',
				'weight'        => '1.1',
			)
		);
		$product->save();

		return wc_get_product( $product->get_id() );
	}
}
