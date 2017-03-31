<div id="sequra_partpayment_info_container" class="sequra_popup_embedded"></div>
<a href="https://www.sequra.es/preguntas-frecuentes.html" target="_blank">Tengo más preguntas</a>
<script type="text/javascript">
  jQuery(function(){
      Sequra.decimal_separator = '<?php echo wc_get_price_decimal_separator();?>';
      creditAgreements = Sequra.loan.compute({
        total_with_tax:   "<?php echo $this->get_order_total()*100;?>",
      });
      SequraPartPaymentMoreInfo.draw(creditAgreements['pp3'],'checkout','pp3',false);
      jQuery('#sequra_partpayments_popup_checkout .sequra_white_content').find('.sq-modal-head').remove();
      jQuery('#sequra_partpayments_popup_checkout .sequra_white_content').find('.sequra_popup_close').remove();
      jQuery('#sequra_partpayment_info_container').append(
        jQuery('#sequra_partpayments_popup_checkout .sequra_white_content')
      );
  });
</script>