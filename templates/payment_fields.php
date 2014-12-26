<?php
if ($this->description)
    echo wpautop(wptexturize($this->description));

foreach ($payment_fields as $key => $field)
    woocommerce_form_field($key, $field, null);
?>
<a href="#" class="trigger" rel="sequra_factura_popup">Más información</a>
<!-- popup content -->
<div class="sequra_popup" id="sequra_factura_popup">
	<div class="sequra_white_content">
		<div class="sequra_content_popup">
			<h4 id="sequra_text_title"><?php $this->title;?></h4>
			<p>Compra hoy y paga después de recibir tus pedidos. Así de sencillo.</p>

			<div class="sequra_image_wrapper"></div>
			<ul>
				<li>Sin registros ni trajetas. La forma más segura de comprar en Internet.</li>
				<li>Paga hasta 7 días después de la fecha de envío.</li>
				<li>Paga con transferencia bancarios, ingreso en cuenta o tarjeta.</li>
			</ul>
			<small>Servicio ofrecido en colaboración con  <a href="https://sequra.es/es/consumidores" target="_blank">SeQura</a></small>
		</div>
	</div>
</div>
<?php echo $this->identity_form;?>
<script>
	SequraHelper.popupize(jQuery( '#sequra_factura_popup' ));
</script>