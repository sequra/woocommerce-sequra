<?php
/**
 * Product Controller interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers
 */

namespace SeQura\WC\Controllers\Hooks\Product;

use WP_Post;

/**
 * Product Controller interface
 */
interface Interface_Product_Controller {

	/**
	 * Handle the widget shortcode callback
	 */
	public function do_widget_shortcode( array $atts ): string;

	/**
	 * Handle the cart widget shortcode callback
	 */
	public function do_cart_widget_shortcode( array $atts ): string;

	/**
	 * Handle the product listing widget shortcode callback
	 */
	public function do_product_listing_widget_shortcode( array $atts ): string;
	
	/**
	 * Add [sequra_widget] to product page automatically
	 * Add [sequra_cart_widget] to cart page automatically
	 */
	public function add_widget_shortcode_to_page(): void;

	/**
	 * Add meta boxes to the product edit page
	 */
	public function add_meta_boxes(): void;

	/**
	 * Render the meta boxes
	 */
	public function render_meta_boxes( WP_Post $post ): void;

	/**
	 * Save product meta
	 */
	public function save_product_meta( int $post_id ): void;
}
