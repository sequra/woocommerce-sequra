<div id='sequra_partpayment_teaser'></div>
<script type='text/javascript'>
    jQuery(window).on('load',function(){
      Sequra.decimal_separator = '<?php echo wc_get_price_decimal_separator(); ?>';
      partPaymnetTeaser = new SequraPartPaymentTeaser(
      {
        container:'#sequra_partpayment_teaser',
        price_container: '<?php echo $price_container; ?>'
      }
    )
    partPaymnetTeaser.draw();
    partPaymnetTeaser.preselect(20);
    <?php if(isset($atts['dest']) && $atts['dest']!='') {?>
    jQuery('<?php echo $atts['dest'];?>').after(jQuery('#sequra_partpayment_teaser'));
    <?php } ?>
  });       
</script>