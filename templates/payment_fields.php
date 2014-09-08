<?php
if ($this->description)
    echo wpautop(wptexturize($this->description));

foreach ($payment_fields as $key => $field)
    woocommerce_form_field($key, $field, null);
?>
<a href="#" onclick="show_mas_info_popup();return false;">Más información</a>
<div id="mas_informacion_container" style="display:none">
<!-- close button-->
<div id="sequra_popup_close" onclick="jQuery('body').unblock();"></div>
	<!-- popup content -->
	<div id="sequra_mas_informacion">
		<h2>Compra ahora paga después</h2>
		<p>Con el servicio <b>Compra ahora, paga después</b> compras online y pagas después de recibir tus pedidos. Así de sencillo</p>
		<img id="sequra_process_img" src="<?php echo WP_PLUGIN_URL . "/" . dirname(plugin_basename(__FILE__));?>/../assets/images/process.png"/>
		<ul>
			<li><b>Recibe y comprueba</b> tus compras antes de pagar</li>
			<li><b>100% seguro</b>. No compartes los datos de tu tarjeta</li>
			<li>Paga hasta <b><?php echo $this->days_after;?> días después de la fecha de envío.</b></li>
			<li>Paga con <b>transferencia bancaria, ingreso en cuenta</b> o <b>tarjeta</b>.</li>
			<li><b>Lo más sencillo y rápido</b>si navegas deste un <b>tablet</b> o <b>móvil</b>.</li>
			<li>Servicio ofrecido por nuestro partner <a href="https://sequra.es/es/consumidores"><b>SeQura</b></a>.</li>
		</ul>
	</div>

</div>
<?php echo $this->identity_form;?>
<script>
    function show_mas_info_popup() {
        jQuery("body").block({
            message: jQuery("#mas_informacion_container").html(),
            overlayCSS: {
                background: "#fff",
                opacity: 0.6
            },
            css: {
                border: "2px solid #aaa",
                backgroundColor: "#fff",
                'max-width':"690px",
				width:"80%"
            }
        });
    }
</script>