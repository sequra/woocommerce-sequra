<?php
/**
 * Payment fields template.
 *
 * @package woocommerce-sequra
 */

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable
// used in class-sequrapaymentgateway.php.
?>
<div id="sq_pm_<?php echo esc_attr( $sq_product_campaign ); ?>"
	class="sq_payment_method <?php echo esc_attr( $sq_product_campaign ); ?>">
	<input type="radio" name="sq_product_campaign" value="<?php echo esc_attr( $sq_product_campaign ); ?>"
		id="sq_product_campaign_<?php echo esc_attr( $sq_product_campaign ); ?>" class="input-radio sq-input-radio" />
	<label for="sq_product_campaign_<?php echo esc_attr( $sq_product_campaign ); ?>" class="sq_payment_method">
		<img src="data:image/svg+xml;base64,<?php echo esc_attr( base64_encode( $method['icon'] ) ); ?>" />
		<div class="sq_payment_method_title_claim">
			<span class="sq_payment_method_title">
				<?php echo wp_kses( $method['long_title'], array() ); ?>
			</span>
			<?php if ( isset( $method['claim'] ) && $method['claim'] ) { ?>
				<br />
				<?php echo wp_kses( $method['claim'], array( 'br', 'p', 'b', 'div', 'ol', 'ul', 'li', 'span' ) ); ?>
			<?php } ?>
			<?php if ( ! in_array( $method['product'], array( 'fp1' ), true ) ) { ?>
				<span id="sequra_info_link" class="sequra-educational-popup sequra_more_info"
					data-amount="<?php echo esc_attr( round($this->get_order_total() * 100) ); ?>"
					data-product="<?php echo esc_attr( $method['product'] ); ?>"
					data-campaign="<?php echo esc_attr( $method['campaign'] ); ?>" rel="sequra_invoice_popup_checkout"
					title="Más información"><span class="sequra-more-info"> + info</span>
				</span>
			<?php } ?>
		</div>
	</label>
	<div class="sq_payment_method_cost">
		<?php if ( isset( $method['cost_description'] ) ) { ?>
			<span id="sequra_cost_link_<?php echo esc_attr( $sq_product_campaign ); ?>"
				class="sequra-educational-popup sequra_cost_description"
				data-amount="<?php echo esc_attr( round($this->get_order_total() * 100) ); ?>"
				data-product="<?php echo esc_attr( $method['product'] ); ?>"
				data-campaign="<?php echo esc_attr( $method['campaign'] ); ?>" rel="sequra_invoice_popup_checkout"
				title="Más información">
				<span class="sequra-cost">
					<?php echo wp_kses_post( $method['cost_description'] ); ?>
				</span>
			</span>
		<?php } ?>
	</div>
</div>
<script>
	jQuery('.payment_method_sequra').show();
</script>
