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
$price                    = strval( $args['price'] );
$is_price_numeric         = preg_match( '/^[0-9]+(?:[.,][0-9]+)?$/', $price );
$in_place_widget_id       = str_replace( '.', '', uniqid( 'sequra-widget-', true ) );
$in_place_widget_selector = '#' . $in_place_widget_id;
$dest                     = empty( $args['dest'] ) ? $in_place_widget_selector : strval( $args['dest'] );

//phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
if ( $is_price_numeric || empty( $args['dest'] ) ) : ?>
<div id="<?php echo esc_attr( $in_place_widget_id ); ?>" style="display: none;" aria-hidden="true"><?php echo $is_price_numeric ? esc_html( $price ) : ''; ?></div>
<?php endif; ?>
<script type='text/javascript'>
	SequraWidgetFacade.widgets && SequraWidgetFacade.widgets.push({
		product: <?php echo wp_json_encode( $args['product'] ); ?>,
		dest: <?php echo wp_json_encode( $dest ); ?>,
		theme: <?php echo wp_json_encode( $args['theme'] ); ?>,
		reverse: <?php echo wp_json_encode( $args['reverse'] ); ?>,
		campaign: <?php echo wp_json_encode( $args['campaign'] ); ?>,
		priceSel: <?php echo wp_json_encode( $is_price_numeric ? $in_place_widget_selector : $price ); ?>,
		variationPriceSel: <?php echo $is_price_numeric ? 'null' : wp_json_encode( $args['alt_price'] ); ?>,
		isVariableSel: <?php echo $is_price_numeric ? 'null' : wp_json_encode( $args['is_alt_price'] ); ?>,
		registrationAmount: <?php echo wp_json_encode( $args['reg_amount'] ); ?>,
		minAmount: <?php echo $args['min_amount'] ? wp_json_encode( $args['min_amount'] ) : '0'; ?>,
		maxAmount: <?php echo $args['max_amount'] ? wp_json_encode( $args['max_amount'] ) : 'null'; ?>
	});
</script>