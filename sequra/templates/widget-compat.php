<?php
/**
 * This template is used to provide compatibility with the old widget.
 * The objective is unset the old function to avoid being executed.
 *
 * @package woocommerce-sequra
 * 
 * @var array $product The seQura product.
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.
// Check if the variables are defined.
if ( ! isset( $product ) ) {
	return;
}
?>
<script type='text/javascript'>
	if('undefined' !== typeof displaySequra<?php echo esc_js( $product ); ?>Teaser){
		displaySequra<?php echo esc_js( $product ); ?>Teaser = (the_price_container, sq_product, campaign, theme) => {}
	}
	if('undefined' !== typeof displaySequra<?php echo esc_js( $product ); ?>Teaser){
		updateSequra<?php echo esc_js( $product ); ?>Teaser = (e) => {}
	}
</script>
