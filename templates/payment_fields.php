<?php
if ($this->description)
    echo wpautop(wptexturize($this->description));

foreach ($payment_fields as $key => $field)
    woocommerce_form_field($key, $field, null);
?>
<a href="#" onclick="show_mas_info_popup();return false;">Más información</a>
<div id="mas_informacion_container" style="display:none">
<div class="sequra_popup_close" onclick="jQuery('body').unblock();"></div>
<img id="sequra_process_img" src="http://shop-assets.sequrapi.com/base/popup.jpg"/>
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
                border: "3px solid #aaa",
                backgroundColor: "#fff",
                width:"690px"
            }
        });
    }
</script>