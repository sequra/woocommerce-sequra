<div class="sequra_popup" id="sequra_identity_partpayment_form_popup">
	<div class="sequra_white_content closeable">
		<div class="sequra_content_popup">
			<h1>Fracciona tu pago</h1>
			<div id="first_step">
				<h2 id="sequra_partpayment_tittle">1. Elige cómo pagar</h2>
				<h2 id="sequra_partpayment_alt_tittle">1. Paga <span class="sequra_partpayment_down_payment_amount-js"></span> y
					<span class="sequra_partpayment_instalment_count-js"></span> cuotas de <span class="sequra_partpayment_instalment_amount-js"></span> después.<small>Editar</small></h2>
				<div id="first_step_content">
					<div id="sequra-wrapper"></div>
					<div style="border: #C0C0C0 1px solid;width: 80%;margin: 25px auto;"></div>
					<ul>
						<li>Sin intereses ni letra pequeña.</li>
						<li>Puedes pagar la totalidad cuando tú quieras.</li>
					</ul>
					<input class="sequra_custom_button" type="button" id="part_payment_last_step" value="Último paso &raquo;">
				</div>
			</div>
			<div id="second_step">
				<h2 id="sequra_partpayment_tittle2">2. Finaliza tu compra</h2>
				<div id="second_step_content" style="display: none;">
					<ul id="description">
						<li>El primer pago se hace con tarjeta. Los pagos futuros se cargan automáticamente en la misma tarjeta.</li>
						<li>Puedes pagar la totalidad cuando tú quieras</li>
						<li>¿Tienes alguna pregunta? Habla con nuestro partner SeQura en el <span>93 176 00 08</span></li>
					</ul>
					<?php echo $this->identity_form; ?>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
	jQuery(document).ready(function ($) {
		SequraHelper.preparePopup();
		SequraHelper.preparePartPaymentAcordion(true);

		$('#sequra_identity_partpayment_form_popup').fadeIn();
		jQuery(document).delegate(".sequra_custom_button", 'click', function() {
			jQuery('div.checker').removeClass('checker');
		});
		jQuery(document).delegate("#sequra_identity_partpayment_form_popup .sequra_popup_close", 'click', function() {
			history.back(1);
		});
		jQuery('#sequra_identity_partpayment_form_popup').show();
		new SequraFraction({
			element:document.getElementById('sequra-wrapper'),
			product:"pp2",
			preselectedCreditAgreement: <?php echo $selected_ca;?>,
		});
	});
</script>