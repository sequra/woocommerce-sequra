<div class="sequra_popup" id="sequra_identity_partpayment_form_popup">
    <div class="sequra_white_content closeable">
        <div class="sequra_content_popup">
            <h1>Fracciona tu pago</h1>
            <div id="first_step">
                <h2 id="sequra_partpayment_tittle">1. Elige cómo pagar</h2>
                <h2 id="sequra_partpayment_alt_tittle">1. Pagar <span class="sequra_partpayment_quota_number-js"></span> cuotas de <span class="sequra_partpayment_quota_price-js"></span>.<small>Editar</small></h2>
                <div id="first_step_content">
                    <div id="sequra-wrapper"></div>
                    <div style="border: #C0C0C0 1px solid;width: 80%;margin: 25px auto;"></div>
                    <ul>
                        <li>El único coste es de <span class="sequra_partpayment_fee-js">5€</span> por cuota.</li>
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
<div class="sequra_popup" id="sequra_partpayments_popup" styles="display:block;">
  <div class="sequra_lightbox sequra_white_content closeable">
    <div class="sequra_content_popup">
      <h1>Paga en 3, 6 ó 12 cuotas mensuales</h1>
      <div>
        <ul>
          <li>El único coste es de 3€ por cuota o de 5€ si el pedido vale más de 200€. Sin intereses ocultos ni letra pequeña.</li>
          <li>El primer pago se hace con tarjeta. Los pagos futuros se cargan automáticamente en la misma tarjeta.</li>
          <li>Puedes pagar la totalidad cuando tú quieras.</li>
        </ul>
        <p>Tienes alguna pregunta? Leer más <a href="https://www.sequra.es/es/fraccionados" target="_blank">aquí</a> o habla con nuestro partner SeQura a través del <span style="display:inline-block;">93 176 00 08</span>.</p>
	  </div>
    </div>
    <div class="sequra_footer_popup">¿Cuánto cuesta? Para un pedido de 500€ el coste sería el siguiente:</br>
      <ul>
        <li>3 cuotas:&nbsp;&nbsp;&nbsp;Fijo TIN 0%, TAE 43,09%, Coste por cuota: 5€. Coste total del pedido: 515€</li>
        <li>6 cuotas:&nbsp;&nbsp;&nbsp;Fijo TIN 0%, TAE 32,79%, Coste por cuota: 5€. Coste total del pedido: 530€</li>
        <li>12 cuotas:&nbsp;Fijo TIN 0%, TAE 28,79%, Coste por cuota: 5€. Coste total del pedido: 560€</li>
      </ul>
    </div>
  </div>
</div>
<script type="text/javascript">
	jQuery( document ).ready(function($) {
		SequraHelper.preparePartPaymentAcordion();

		$('#sequra_identity_partpayment_form_popup').fadeIn();
		$("#sequra_identity_partpayment_form_popup .sequra_popup_close").on('click', function() {
			history.back(1);
		});
		new SequraFraction({
			totalPrice: <?php echo $total_price;?>,
			element: document.getElementById('sequra-wrapper')
		});
		new SequraFraction({
			totalPrice: <?php echo $total_price;?>,
			element:document.getElementById('sequra-wrapper'),
			taxRanges : [ {min: 0, max: 19999, tax: 300}, {min:20000,max:<?php echo $this->max_amount;?>, tax: 500}]
		});
	});
</script>