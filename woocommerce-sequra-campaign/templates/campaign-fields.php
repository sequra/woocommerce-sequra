<?php
/**
 * Payment fields template.
 *
 * @package woocommerce-sequracampaign
 */

$data_amount = (string) $this->get_order_total() * 100;
?>
<p id="sequra_campaign_teaser_container">
	Compra al momento, sin registros ni papeleos.<br/>
	Tienes hasta el %d para pagar tu compra.
	<span id="sequra_invoice_method_link"
		class="sequra-educational-popup"
		data-amount="<?php echo esc_html( $data_amount ); ?>"
		data-product="<?php echo esc_html( $this->product ); ?>"
		data-campaign="<?php echo esc_html( $this->campaign ); ?>"> + info</span>
</p>
<script type="text/javascript">
Sequra.onLoad(function () {
	var container = jQuery("#sequra_campaign_teaser_container");
	if(container.attr('done')){
		return;
	}
	var creditAgreements = Sequra.computeCreditAgreements({
		amount: "<?php echo esc_js( $data_amount ); ?>",
		campaign: "<?php echo esc_js( $this->campaign ); ?>",
		product: "<?php echo esc_js( $this->product ); ?>"
	});
	var ca = creditAgreements["<?php echo esc_js( $this->product ); ?>"];
	var due_date = ca[0]["due_date"]["string"];
	var method_name = "Compra ahora, paga el %d";
	var el = jQuery("[for=payment_method_sequracampaign]");
	el.html(
		el.html().replace(
			'<?php echo esc_html( $this->title ); ?>',
			method_name.replace('%d',due_date)
		)
	);
	container.html(container.html().replace('%d',due_date));
	container.attr('done',true);
	Sequra.refreshComponents();
});
</script>
