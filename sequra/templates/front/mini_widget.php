<?php
/**
 * Mini Widget template
 *
 * @package    SeQura/WC
 * @var array<string, string> $args The arguments. Must contain:
 * - string 'product' The seQura payment method identifier.
 * - string 'campaign' The seQura campaign identifier.
 * - string 'dest' The CSS selector to place the widget.
 * - string 'price' The CSS selector for the price.
 * - string 'message' The message to show.
 * - string 'message_below_limit' The message to show when the amount is below the limit.
 * - int min_amount The minimum amount to show the widget.
 * - ?int max_amount The maximum amount to show the widget.
 */

defined( 'WPINC' ) || die;

// Check if the variables are defined.
if ( ! isset( 
	$args['product'], 
	$args['campaign'],
	$args['dest'],
	$args['price'],
	$args['message'],
	$args['message_below_limit'],
	$args['min_amount']
) ) {
	return;
}
	
//phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<script type='text/javascript'>
	SequraWidgetFacade.miniWidgets && SequraWidgetFacade.miniWidgets.push({
		product: <?php echo wp_json_encode( $args['product'] ); ?>,
		dest: <?php echo wp_json_encode( $args['dest'] ); ?>,
		priceSel: <?php echo wp_json_encode( $args['price'] ); ?>,
		campaign: <?php echo wp_json_encode( $args['campaign'] ); ?>,
		message: <?php echo wp_json_encode( $args['message'] ); ?>,
		messageBelowLimit: <?php echo wp_json_encode( $args['message_below_limit'] ); ?>,
		minAmount: <?php echo wp_json_encode( $args['min_amount'] ); ?>,
		maxAmount: <?php echo $args['max_amount'] ? wp_json_encode( $args['max_amount'] ) : 'null'; ?>
	});
</script>