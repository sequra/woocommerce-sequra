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
	 * 
	 * @param array<string, string> $atts The shortcode attributes
	 * @return string
	 */
	public function do_widget_shortcode( $atts );

	/**
	 * Handle the cart widget shortcode callback
	 * 
	 * @param array<string, string> $atts The shortcode attributes
	 * @return string
	 */
	public function do_cart_widget_shortcode( $atts );

	/**
	 * Handle the product listing widget shortcode callback
	 * 
	 * @param array<string, string> $atts The shortcode attributes
	 * @return string
	 */
	public function do_product_listing_widget_shortcode( $atts );
	
	/**
	 * Add [sequra_widget] to product page automatically
	 * Add [sequra_cart_widget] to cart page automatically
	 * 
	 * @return void
	 */
	public function add_widget_shortcode_to_page();

	/**
	 * Add meta boxes to the product edit page
	 * 
	 * @return void
	 */
	public function add_meta_boxes();

	/**
	 * Render the meta boxes
	 * 
	 * @param WP_Post $post The post object
	 * @return void
	 */
	public function render_meta_boxes( $post );

	/**
	 * Save product meta
	 * 
	 * @param int $post_id The post ID
	 * @return void
	 */
	public function save_product_meta( $post_id );
}
