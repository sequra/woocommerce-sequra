<?php
/**
 * Widget template
 *
 * @package    SeQura/WC
 * @var array<string, string> $args The arguments. Must contain:
 * - string 'product' The seQura payment method identifier.
 * - string 'campaign' The seQura campaign identifier.
 * - string 'dest' The CSS selector to place the widget.
 * - string 'price' The CSS selector for the price.
 * - string 'alt_price' The CSS selector for the alternative price.
 * - string 'is_alt_price' The CSS selector to determine if the product has an alternative price.
 * - string 'reg_amount' The registration amount.
 * - string 'theme' The theme to use.
 */

defined( 'WPINC' ) || die;

// Check if the variables are defined.
if ( ! isset( 
	$args['product'], 
	$args['dest'],
	$args['theme'],
	$args['reverse'],
	$args['campaign'],
	$args['price'],
	$args['alt_price'],
	$args['is_alt_price'],
	$args['reg_amount']
) ) {
	return;
}
//phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?>

<script type='text/javascript'>
	SequraWidgetFacade.widgets && SequraWidgetFacade.widgets.push({
		product: "<?php echo esc_js( $args['product'] ); ?>",
		dest: "<?php echo wp_strip_all_tags( $args['dest'] ); ?>",
		theme: "<?php echo esc_js( $args['theme'] ); ?>",
		reverse: "<?php echo esc_js( $args['reverse'] ); ?>",
		campaign: "<?php echo esc_js( $args['campaign'] ); ?>",
		priceSel: "<?php echo wp_strip_all_tags( $args['price'] ); ?>",
		variationPriceSel: "<?php echo wp_strip_all_tags( $args['alt_price'] ); ?>",
		isVariableSel: "<?php echo wp_strip_all_tags( $args['is_alt_price'] ); ?>",
		registrationAmount: "<?php echo esc_js( $args['reg_amount'] ); ?>",
	});
</script>