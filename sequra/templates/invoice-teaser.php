<?php
/**
 * Invoice teaser template.
 *
 * @package woocommerce-sequra
 * 
 * Requires the following variables to be defined:
 * @var string $price_container The price container.
 * @var string $dest The destination.
 * @var string $product The product.
 * @var string $theme The theme to be used.
 * @var string $reverse Reverse.
 * @var string $campaign Campaign.
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

// Check if the variables are defined.
if ( ! isset( $price_container, $dest, $product, $theme, $reverse, $campaign ) ) {
	return;
}

?>
<div class="sequra_invoice_teaser_container" 
	style="clear:both" 
	data-price-container="<?php echo esc_attr( $price_container ); ?>"
	data-dest="<?php echo esc_attr( $dest ); ?>"
	data-product="<?php echo esc_attr( $product ); ?>"
	data-theme="<?php echo esc_attr( $theme ); ?>"
	data-reverse="<?php echo esc_attr( $reverse ); ?>"
	data-campaign="<?php echo esc_attr( $campaign ); ?>" >
	<div id="sequra_invoice_teaser"></div>
</div>

