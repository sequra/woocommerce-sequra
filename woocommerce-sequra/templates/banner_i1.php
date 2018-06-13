<?php if(isset($atts['color'])) {?>
    <style>
        #sequra-banner-invoice .sqblock .sqnoc,
        #sequra-banner-invoice .sequra-educational-popup {
        color: <?php echo $atts['color'];?>;
        }
        #sequra-banner-invoice .sequra-educational-popup {
        border-color: <?php echo $atts['color'];?>;
        }
        #sequra-banner-invoice #block1 {
        background: <?php echo $atts['color'];?>;
        }
    </style>
<?php } ?>

<div id="sequra-banner-partpayment" class="sequra-banner">
    <div id="block1" class="sqblock">
        <span class="sqheader">FRACCIONA TU COMPRA</span>
    </div>
    <div id="block2" class="sqblock">
        <span class="sqnoc icon-puzzle">&nbsp;</span>
        <div class="sqinner">
            <span class="sqheader">Fracciona tu compra</span>
            <span class="sqcontent">Fracciona tu compra en nuestra tienda.</span>
        </div>
    </div>
    <div id="block3" class="sqblock">
        <span class="sqnoc icon-check-paiper">&nbsp;</span>
        <div class="sqinner">
            <span class="sqheader">Inmediato</span>
            <span class="sqcontent">Sin papeleo, directamente al finalizar el pedido.</span>
        </div>
    </div>
    <div id="block4" class="sqblock">
        <div class="sqinner">
            <span class="sqheader">Un coste fijo por cuota</span>
            <span class="sqcontent sequra-educational-popup" data-product="i1">Más información</span>
        </div>
    </div>
</div>