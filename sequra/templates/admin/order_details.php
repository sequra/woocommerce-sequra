<?php
/**
 * Order details template
 *
 * @package    SeQura/WC
 * @var string $args The arguments. Must contains:
 * - 'sequra_link' The link to the order in the seQura back office.
 */

defined( 'WPINC' ) || die;
if ( ! isset( $args['sequra_link'] ) ) {
	return;
}
?>
<p class="form-field form-field-wide wc-order-status">
	<a class="sequra-link" target="_blank" href="<?php echo esc_url( $args['sequra_link'] ); ?>"><?php esc_html_e( 'View on seQura', 'sequra' ); ?></a>
</p>
