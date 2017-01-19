<?php if(isset($atts['color'])) {?>
	<style>
		.sequra_banner .block1{
			background: <?php echo $atts['color'];?> !important;
		}
		.sequra_banner .btn-sequra,
		.sqnoc {
			color: <?php echo $atts['color'];?>  !important;
		}
		.sequra_banner .btn-sequra{
			border: 1px solid <?php echo $atts['color'];?>  !important;
		}
	</style>
<?php } ?>
<div id="sequra_pp3_banner" class="clearfix sequra_banner">
	<div id="sequra_pp3_block1" class="block1">
        <div>FRACCIONA TU PAGO</div>
    </div>
	<div id="sequra_pp3_block2" class="sequra_block block2">
        <i class="sqnoc icon-puzzle"></i>
        <div class="sqinner">
            <span class="sqheader">Fracciona tu compra</span>
            <span class="sqcontent">Fracciona tu compra en nuestra tienda.</span>
        </div>
    </div>
	<div id="sequra_pp3_block3" class="sequra_block block3">
        <span class="sqnoc icon-check-paiper"></span>
        <div class="sqinner">
            <span class="sqheader">Inmediato</span>
            <span class="sqcontent">Sin papeleo, directamente al finalizar el pedido.</span>
        </div>
    </div>
	<div id="sequra_pp3_block4" class="sequra_block block4">
    <span class="sqnoc icon-euro-one"></span>
    <div class="sqinner">
            <span class="sqheader">Un coste fijo por cuota</span>
            <span class="sqcontent">
                <a class="btn btn-sequra" href="#" rel="sequra_partpayments_popup">Más información</a>
            </span>
    </div>
    </div>
</div>
<script type='text/javascript'>
  jQuery.getJSON('<?php echo $pm->pp_cost_url;?>',function (json){
    SequraCreditAgreements(
        {
          costs_json: json,
          product: 'pp3',
          //Personalizar si hace falta
          currency_symbol_l: '',
          currency_symbol_r: ' €',
          decimal_separator: '<?php echo get_option('woocommerce_price_decimal_sep');?>',
          thousands_separator: '<?php echo get_option('woocommerce_price_thousand_sep');?>'
        }
    );
    SequraPartPaymentMoreInfo.draw(true);
  });
</script>