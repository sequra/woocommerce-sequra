<?php
if ( $sequra->is_available() && $product->get_price() < $sequra->max_amount ) { ?>
      <div id="sequra_invoice_teaser"></div>
      <script type="text/javascript">
  jQuery(function(){
    invoiceTeaser = new SequraInvoiceTeaser(
      {
        container:'#sequra_invoice_teaser',
        fee: <?php echo $sequra->fee?$sequra->fee:0;?>
      }
    );
    invoiceTeaser.draw();
  });
      </script>
<?php
}