<div id="sequra_partpayment_info_container" class="sequra_popup_embedded"></div>
<a href="https://www.sequra.es/preguntas-frecuentes.html" target="_blank">Tengo más preguntas</a>
<script type="text/javascript">
    jQuery.getJSON('<?php echo $this->pp_cost_url;?>', function (json) {
        SequraCreditAgreements(
            {
                costs_json: json,
                product: 'pp3',
                //Personalizar si hace falta
                currency_symbol_l: '',
                currency_symbol_r: ' €',
                decimal_separator: ',',
                thousands_separator: '.'
            }
        );
        SequraCreditAgreementsInstance.get(<?php echo $this->get_order_total()*100;?>);
        jQuery('#sequra_partpayment_info_container').append(
            SequraPartPaymentMoreInfo.get_pp_checkout_popup_html(SequraCreditAgreementsInstance.creditAgreements)
        );
    });
</script>