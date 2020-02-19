<?php
/**
 * Partpayment fields template.
 *
 * @package woocommerce-sequra
 */

$data_amount = (string) $this->get_order_total() * 100;
?>
<div id="sequra_partpayment_info_container" class="sequra_popup_embedded">
</div>
<p>
	<b>Finaliza tu compra para poder elegir tu plan de pago
		<span id="sequra_partpayment_method_link"
			class="sequra-educational-popup"
			data-amount="<?php echo esc_html( $data_amount ); ?>"
			data-product="<?php echo esc_html( $this->product ); ?>"> + info</span>
	</b>
</p>
<script type="text/javascript">
Sequra.onLoad(function () {
	var container = jQuery("#sequra_partpayment_info_container");
	if(container.attr('done')){
		return;
	}
	var creditAgreements = Sequra.computeCreditAgreements({
		amount: "<?php echo esc_html( $data_amount ); ?>",
		product: "<?php echo esc_html( $this->product ); ?>"
	});
	var ca = creditAgreements["<?php echo esc_html( $this->product ); ?>"];
	var instalment_total = ca[ca.length - 1]["instalment_total"]["string"];
	var method_name = "<?php esc_html_e( 'Fracciona tu pago desde 00,00€/mes', 'wc_sequra' ); ?>";
	var el = jQuery("[for=payment_method_sequra_pp]");
	el.html(
		el.html().replace(
			'<?php echo esc_html( $this->title ); ?>',
			method_name.replace('00,00€',instalment_total)
		)
	);
	var i=0;
	for(i=0;i<ca.length;i++){
		container.append(
			"<div>" + ca[i]["instalment_count"] + " cuotas de " + ca[i]["instalment_total"]["string"] + "/mes</div>"
		);
	}
	container.attr('done',true);
	Sequra.refreshComponents();
});
</script>
