<?php
if ( $sequra->is_available() && $product->get_price() < $sequra->max_amount ) { ?>
    <div id="sequra_invoice_teaser_container" style="clear:both"><div id="sequra_invoice_teaser"></div></div>
      <script type="text/javascript">
          Sequra.onLoad(function(){
              SequraHelper.drawPromotionWidget('<?php echo $price_container; ?>','#sequra_invoice_teaser','i1','<?php echo $theme;?>',0);
              Sequra.refreshComponents();
          });
      </script>
<?php
}