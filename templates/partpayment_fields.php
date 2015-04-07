<?php
if ($this->description)
    echo wpautop(wptexturize($this->description));

foreach ($payment_fields as $key => $field)
    woocommerce_form_field($key, $field, null);
?>
<a href="#" class="trigger" rel="sequra_partpayments_popup">Más información</a>
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