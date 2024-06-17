<?php
/**
 * Payment fields for seQura payment gateway.
 *
 * @package    SeQura/WC
 * @var string $args The arguments. Must contain:
 * - description: string
 * - payment_methods: array<string, mixed>
 */

defined( 'WPINC' ) || die;
if ( ! isset( $args['description'], $args['payment_methods'] ) ) {
	return;
}
?>

<span class="sequra-block__description"><?php echo wp_kses_post( wpautop( wptexturize( $args['description'] ) ) ); ?></span>
<?php
foreach ( (array) $args['payment_methods'] as $key => $pm ) :
	$data_product  = $pm['product'];
	$data_campaign = ! empty( $pm['campaign'] ) ? $pm['campaign'] : ''; 
	$input_val     = $data_product . ( ! empty( $data_campaign ) ? ":$data_campaign" : '' );
	$input_id      = "sequra_payment_method_{$key}";
	?>
	<div class="sequra-payment-method">
		<input type="radio" name="sequra_product_campaign" value="<?php echo esc_attr( $input_val ); ?>" data-product="<?php echo esc_attr( $data_product ); ?>" data-campaign="<?php echo esc_attr( $data_campaign ); ?>" id="<?php echo esc_attr( $input_id ); ?>" class="sequra-payment-method__input wc-block-components-radio-control__input" />
		<label for="<?php echo esc_attr( $input_id ); ?>">
			<div class="sequra-payment-method__description">
				<span class="sequra-payment-method__name" style="width:100%"><?php echo esc_html( $pm['title'] ); ?></span>
				<span class="sequra-payment-method_claim" style="width:100%"><?php echo esc_html( $pm['claim'] ); ?></span>
			</div>
			<?php if ( ! empty( $pm['costDescription'] ) ) : ?>
				<?php echo esc_html( $pm['costDescription'] ); ?>
			<?php endif; ?>
		</label>
	</div>
<?php endforeach; ?>
