<style>
	#selected_ca_field label.radio {display:inline}
	#selected_ca_field label.radio::after {content:"\a";white-space:pre;}
</style><?php
if ($this->description)
	echo wpautop(wptexturize($this->description));
?>
<b>Todos los costes incluidos</b><br/>
Paga ahora <?php echo $this->credit_agreements[$this->pp_product][0]['down_payment_amount']['string']; ?> después...
<?php
foreach ($payment_fields as $key => $field)
	woocommerce_form_field($key, $field, null);
?>
<a href="#" class="trigger" rel="sequra_partpayments_popup">Más información</a>
<script type="text/javascript">
	SequraCreditAgreements(
		{
			product: 'pp2',
			//Personalizar si hace falta
			currency_symbol_l: '',
			currency_symbol_r: ' €',
			decimal_separator: ',',
			thousands_separator: '.'
		}
	);
	SequraCreditAgreementsInstance.get(<?php echo round($this->get_order_total()*100);?>);
	SequraPartPaymentMoreInfo.draw();
</script>