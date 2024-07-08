<?php
/**
 * Payment fields for seQura payment gateway.
 *
 * @package    SeQura/WC
 * @var string $args The arguments. Must contain:
 * - description: string
 * - payment_methods: array<string, mixed>
 */

use SeQura\WC\Dto\Payment_Method_Data;

defined( 'WPINC' ) || die;
if ( ! isset( $args['description'], $args['payment_methods'] ) ) {
	return;
}
?>

<span class="sequra-block__description"><?php echo wp_kses_post( wpautop( wptexturize( $args['description'] ) ) ); ?></span>
<?php
foreach ( (array) $args['payment_methods'] as $key => $pm ) :
	/**
	 * Dto
	 *
	 * @var Payment_Method_Data $dto
	 */
	$dto = Payment_Method_Data::from_array( $pm );
	if ( ! $dto || ! $dto->is_valid() ) {
		continue; 
	}
	
	$input_id = "sequra_payment_method_{$key}";
	?>
	<div class="sequra-payment-method">
		<input type="radio" name="sequra_payment_method_data" value="<?php echo esc_attr( $dto->encode() ); ?>" id="<?php echo esc_attr( $input_id ); ?>" class="sequra-payment-method__input wc-block-components-radio-control__input" />
		<label for="<?php echo esc_attr( $input_id ); ?>">
			<div class="sequra-payment-method__description">
				<span class="sequra-payment-method__name" style="width:100%"><?php echo esc_html( $pm['title'] ); ?></span>
				<span class="sequra-payment-method__claim" style="width:100%"><?php echo esc_html( $pm['claim'] ); ?></span>
				<?php if ( ! empty( $pm['costDescription'] ) ) : ?>
					<span class="sequra-payment-method__cost-desc" style="width:100%"><?php echo esc_html( $pm['costDescription'] ); ?></span>
				<?php endif; ?>
			</div>
			<?php if ( ! empty( $pm['icon'] ) ) : ?>
				<img src="data:image/svg+xml;base64,<?php echo esc_attr( base64_encode( $pm['icon'] ) ); ?>" height="40px" loading="lazy" alt="<?php echo esc_attr( $pm['title'] ); ?>" class="sequra-payment-method__icon" />
			<?php endif; ?>
		</label>
	</div>
<?php endforeach; ?>
