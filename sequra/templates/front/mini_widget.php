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
		product: "<?php echo esc_js( $args['product'] ); ?>",
		dest: "<?php echo wp_strip_all_tags( $args['dest'] ); ?>",
		priceSel: "<?php echo wp_strip_all_tags( $args['price'] ); ?>",
		campaign: "<?php echo esc_js( $args['campaign'] ); ?>",
		message: "<?php echo esc_js( $args['message'] ); ?>",
		messageBelowLimit: "<?php echo esc_js( $args['message_below_limit'] ); ?>",
		minAmount: <?php echo esc_js( $args['min_amount'] ); ?>,
		maxAmount: <?php echo esc_js( $args['max_amount'] ?? 'null' ); ?>
	});
</script>