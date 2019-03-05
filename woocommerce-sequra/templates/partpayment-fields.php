<?php
/**
 * Partpayment fields template.
 *
 * @package woocommerce-sequra
 */

$data_amount = (string) $this->get_order_total() * 100;
?>
<p>
	<div id="sequra_partpayment_info_container" class="sequra_popup_embedded">
	</div>
	<b>Finaliza tu compra para poder elegir tu plan de pago 
		<span id="sequra_partpayment_method_link"
			class="sequra-educational-popup"
			data-amount="<?php echo esc_html( $data_amount ); ?>"
			data-product="pp3"> + info</span>
	</b>
</p>
<script type="text/javascript">
Sequra.onLoad(function () {
	var creditAgreements = Sequra.computeCreditAgreements({
		amount: "<?php echo esc_html( $data_amount ); ?>",
		product: "pp3"
	});
	var ca = creditAgreements["pp3"];
	var instalment_total = ca[ca.length - 1]["instalment_total"]["string"];
	var method_name = "<?php esc_html_e( 'Desde 00,00€/mes', 'wc_sequra' ); ?>";
	var el = jQuery("[for=payment_method_sequra_pp]");
	el.html(
		el.html().replace(
			'<?php echo esc_html( $this->title ); ?>',
			method_name.replace('00,00€',instalment_total)
		)
	);
	var i=0;
	var container = jQuery("#sequra_partpayment_info_container");
	for(i=0;i<ca.length;i++){
		container.append(
			"<div>" + ca[i]["instalment_count"] + " cuotas de " + ca[i]["instalment_total"]["string"] + "/mes</div>"
		);
	}
	Sequra.refreshComponents();
});
</script>
