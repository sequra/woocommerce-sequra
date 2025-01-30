<?php
/**
 * Payment fields for seQura payment gateway.
 *
 * @package    SeQura/WC
 * @var string $args The arguments. Must contain:
 * - description: string
 * - payment_methods: Payment_Method_Option[]
 * - cart_total: int The cart total in cents.
 */

use SeQura\WC\Dto\Payment_Method_Option;

defined( 'WPINC' ) || die;
if ( ! isset( $args['description'], $args['payment_methods'], $args['cart_total'] ) ) {
	return;
}
?>
<span class="sequra-block__description"><?php echo wp_kses_post( wpautop( wptexturize( $args['description'] ) ) ); ?></span>
<?php
/**
 * Payment methods
 * 
 * @var Payment_Method_Option $pm
 */
foreach ( (array) $args['payment_methods'] as $key => $pm ) :
	if ( ! $pm instanceof Payment_Method_Option || ! $pm->is_valid() ) {
		continue;
	}
	
	$input_id  = "sequra_payment_method_{$key}";
	$more_info = '';
	if ( $pm->should_show_more_info() ) {
		$more_info = sprintf(
			'<span class="sequra-educational-popup sequra_more_info" data-amount="%s" data-product="%s" data-campaign="%s" rel="sequra_invoice_popup_checkout" title="%s">%s</span>',
			esc_attr( (string) $args['cart_total'] ),
			esc_attr( (string) $pm->product ),
			esc_attr( (string) ( $pm->campaign ?? '' ) ),
			esc_attr__( 'More information', 'sequra' ),
			esc_html__( '+info', 'sequra' )
		);
	}
	?>
	<div class="sequra-payment-method">
		<input type="radio" name="sequra_payment_method_data" value="<?php echo esc_attr( $pm->encode_data() ); ?>" id="<?php echo esc_attr( $input_id ); ?>" class="sequra-payment-method__input wc-block-components-radio-control__input" />
		<label for="<?php echo esc_attr( $input_id ); ?>">
			<div class="sequra-payment-method__description">
				<span class="sequra-payment-method__name" style="width:100%"><?php echo esc_html( (string) empty( $pm->long_title ) ? $pm->title : $pm->long_title ); ?></span>
				<span class="sequra-payment-method__claim" style="width:100%"><?php echo esc_html( (string) ( $pm->claim ?? '' ) ); ?>  <?php echo wp_kses_post( $more_info ); ?></span>
				<?php if ( $pm->should_show_cost_description() ) : ?>
					<span class="sequra-payment-method__cost-desc" style="width:100%"><?php echo esc_html( (string) $pm->cost_description ); ?></span>
				<?php endif; ?>
			</div>
			<?php if ( $pm->icon ) : ?>
				<img src="data:image/svg+xml;base64,<?php echo esc_attr( base64_encode( (string) ( $pm->icon ?? '' ) ) ); ?>" height="40px" loading="lazy" alt="<?php echo esc_attr( $pm->title ); ?>" class="sequra-payment-method__icon" />
			<?php endif; ?>
		</label>
	</div>
<?php endforeach; ?>