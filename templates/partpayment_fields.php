<?php
if ($this->description)
    echo wpautop(wptexturize($this->description));

foreach ($payment_fields as $key => $field)
    woocommerce_form_field($key, $field, null);
?>
<a href="#" class="trigger" rel="sequra_partpayments_popup">Más información</a>
<div class="sequra_popup" id="sequra_partpayments_popup">
	<div class="sequra_lightbox sequra_white_content">
		<div class="sequra_content_popup">
			<h4>Elige cuánto quieres pagar cada vez</h4>
			<p class="sequra_colored_text">Puedes elegir entre 3, 6 ó 12 cuotas mensuales</p>
			<img src="<?php echo WP_PLUGIN_URL . "/" . dirname(plugin_basename(__FILE__));?>/../assets/img/mrq.png"/>
			<div>
				<ul>
					<li>Fácil de usar y sencillo. Tu compra hecha en menos de un minuto.</li>
					<li>El único coste es de 3€ por cuota o de 5€ si el pedido vale más de 200€. Sin intereses ocultos ni letra pequeña.</li>
					<li>La aprobación es instantánea, por lo que los productos se envían de forma inmediata.</li>
					<li>El primer pago se hace con tarjeta en el momento de la realizaci&oacute;n del pedido. Los pagos siguientes se cargar&aacute;n autom&aacute;ticamente cada mes en la tarjeta.</li>
					<li>Puedes pagar la totalidad cuando tú quieras.</li>
					<li>Disponible para compras superiores a 50€.</li>
					<li>El servicio es ofrecido conjuntamente con <a href="https://sequra.es/es/fraccionados" target="_blank">SeQura</a></li>
				</ul>
				<p>¿Tienes alguna pregunta? Habla con nosotros a través del 93 492 03 85 o envíanos un email a clientes@sequra.es.</p>
				<a href="https://www.sequra.es/es/fraccionados" target="_blank" class="button">Leer más</a>
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
<script>
	SequraHelper.preparePopup();
</script>