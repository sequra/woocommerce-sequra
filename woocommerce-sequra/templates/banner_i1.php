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
<div id="sequra_i1_banner" class="sequra_banner">
	<div id="sequra_i1_block1" class="block1">
		<div>RECIBE PRIMERO PAGA DESPU&Eacute;S</div>
	</div>
	<div id="sequra_i1_block2" class="sequra_block block2">
		<span class="sqnoc icon-cart-screen"></span>
		<div class="sqinner">
			<span class="sqheader">1. Pide sin tarjeta</span>
			<span class="sqcontent">Haz tu pedido on-line ahora.</span>
		</div>
	</div>
	<div id="sequra_i1_block3" class="sequra_block block3">
		<span class="sqnoc icon-bag"></span>
		<div class="sqinner">
			<span class="sqheader">2. Recibe tu pedido</span>
			<span class="sqcontent">Recibe tu pedido y compru&eacute;balo</span>
		</div>
	</div>
	<div id="sequra_i1_block4" class="sequra_block block4">
		<span class="sqnoc icon-euros"></span>
		<div class="sqinner">
			<span class="sqheader">3. Paga después</span>
			<span class="sqcontent">
                    <a class="btn btn-sequra" href="#" rel="sequra_invoice_popup_home">Más información</a>
                </span>
		</div>
	</div>
</div>
<script type="text/javascript">
  (jQuery(function (){
    SequraInvoiceMoreInfo.draw('home',<?php echo $pm->fee;?>);
  }));
</script>