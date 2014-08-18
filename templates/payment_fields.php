<?php
if ($this->description)
    echo wpautop(wptexturize($this->description));

foreach ($payment_fields as $key => $field)
    woocommerce_form_field($key, $field, null);
?>
<a href="#" onclick="show_mas_info_popup();return false;">Más información</a>
<div id="mas_informacion" style="display:none">
    <div id="sequra_mas_informacion">
        <h2>Recibir antes de pagar</h2>
        <img id="sequra_process_img" src="<?php echo WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/../assets/images/recibir-antes-de-pagar.png';?>"/>
        <ul>
            <li>Sin comsiones</li>
            <li>Dispones de hasta 7 días para pagar con transferencia bancaria o ingreso en cuenta</li>
            <li>Cómodo, rápido y 100% seguro</li>
            <li>Ideal si navegas desde tu dispositivo móvil</li>
        </ul>
        <button onclick="jQuery('body').unblock();">Cerrar</button>
        <div id="sequra_mas_informacion_footer">Servicio ofrecido con nuestro partner <a href="https://sequra.es/">SeQura</a></div>
    </div>
</div>
<?php echo $this->identity_form;?>
<script>
    function show_mas_info_popup() {
        jQuery("body").block({
            message: jQuery("#mas_informacion").html(),
            overlayCSS: {
                background: "#fff",
                opacity: 0.6
            },
            css: {
                border: "3px solid #aaa",
                backgroundColor: "#fff",
                width:"690px"
            }
        });
    }
</script>