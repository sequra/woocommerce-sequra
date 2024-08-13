<?php
/**
 * Product Controller interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers
 */

namespace SeQura\WC\Controllers\Hooks\Asset;

/**
 * Product Controller interface
 */
interface Interface_Product_Controller {

	/**
	 * Handle the widget shortcode callback
	 */
	public function do_widget_shortcode( array $atts ): string;
	
	/**
	 * Add [sequra_widget] to product page automatically
	 */
	public function add_widget_shortcode_to_product_page(): void;
}
