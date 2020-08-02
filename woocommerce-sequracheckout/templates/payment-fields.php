<?php
/**
 * Payment fields template.
 *
 * @package woocommerce-sequra
 */

 // class-sequrapaymentgateway.php
?>
<div id="sq_pm_<?php echo esc_html( $sq_product_campaign );?>" class="sq_payment_method <?php echo esc_html( $sq_product_campaign );?>">
	<input type="radio" name="sq_product_campaign" value="<?php echo esc_html( $sq_product_campaign );?>" id="sq_product_campaign_<?php echo esc_html( $sq_product_campaign );?>"/>
	<label for="sq_product_campaign_<?php echo esc_html( $sq_product_campaign );?>" class="sq_payment_method">
		<img src="data:image/svg+xml;base64,<?php echo base64_encode($method['icon']);?>"/>
		<div class="sq_payment_method_title_claim">
			<span class="sq_payment_method_title"><?php echo $method['long_title']; ?></span>
			<?php if (isset($method['claim']) && $method['claim']) {?>
			<br/>
				<?php echo $method['claim'];?>
			<?php } ?>
			<?php if ( !in_array($method['product'],['fp1']) ) { ?>
				<span id="sequra_info_link" class="sequra-educational-popup sequra_more_info"
				data-amount="<?php echo $this->get_order_total()*100; ?>" data-product="<?php echo esc_html( $method['product'] );?>" data-campaign="<?php echo esc_html( $method['campaign'] );?>"
				rel="sequra_invoice_popup_checkout" title="Más información"><span class="sequra-more-info"> + info</span>
				</span>
			<?php } ?>
		</div>
	</label>
	<div class="sq_payment_method_cost">
		<?php if ( isset($method['cost_description']) ) { ?>
			<span id="sequra_cost_link_<?php echo $sq_product_campaign; ?>" class="sequra-educational-popup sequra_cost_description"
				data-amount="<?php echo $this->get_order_total()*100; ?>" data-product="<?php echo $method['product']; ?>" data-campaign="<?php echo $method['campaign']; ?>"
				rel="sequra_invoice_popup_checkout" title="Más información">
				<span class="sequra-cost"><?php echo $method['cost_description']; ?></span>
			</span>
		<?php } ?>
	</div>
</div>