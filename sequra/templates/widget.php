<?php
/**
 * Teaser template.
 *
 * @package woocommerce-sequra
 * 
 * @var array $atts The attributes of the shortcode.
 * @var string $theme The theme to be used.
 * @var string $reverse Reverse.
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.
// Check if the variables are defined.
if ( ! isset( $atts['product'], $atts['dest'], $theme, $reverse, $atts['campaign'], $atts['price'], $atts['variation_price'], $atts['is_variable'], $atts['product_id'], $registration_amount ) ) {
	return;
}
?>
<script type='text/javascript'>
	sequraConfigParams.widgets && sequraConfigParams.widgets.push({
		product: SequraHelper.decodeChars("<?php echo esc_js( $atts['product'] ); ?>"),
		dest: SequraHelper.decodeChars("<?php echo esc_js( $atts['dest'] ); ?>"),
		theme: SequraHelper.decodeChars("<?php echo esc_js( $theme ); ?>"),
		reverse: SequraHelper.decodeChars("<?php echo esc_js( $reverse ); ?>"),
		campaign: SequraHelper.decodeChars("<?php echo esc_js( $atts['campaign'] ); ?>"),
		priceSel: SequraHelper.decodeChars("<?php echo esc_js( $atts['price'] ); ?>"),
		variationPriceSel: SequraHelper.decodeChars("<?php echo esc_js( $atts['variation_price'] ); ?>"),
		isVariableSel: SequraHelper.decodeChars("<?php echo esc_js( $atts['is_variable'] ); ?>"),
		registrationAmount: SequraHelper.decodeChars("<?php echo esc_js( $registration_amount ); ?>"),
	});
</script>
