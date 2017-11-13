<div id="sequra_campaign_info_container" class="sequra_popup_embedded"></div>
<script type="text/javascript">
    jQuery(function(){
        Sequra.decimal_separator = '<?php echo wc_get_price_decimal_separator();?>';
        creditAgreements = Sequra.loan.compute({
            total_with_tax:   "<?php echo $this->get_order_total()*100;?>",
        });
        ca = creditAgreements['pp5'].reduce(
            function (c,r){
                return c['campaign']=='<?php echo $this->campaign;?>'?c:r
            });
        SequraCampaignMoreInfo.draw('checkout',ca,'<?php echo $this->title;?>');
        jQuery('#sequra_campaign_popup_checkout .sequra_white_content').find('.sq-modal-head').remove();
        jQuery('#sequra_campaign_popup_checkout .sequra_white_content').find('.sequra_popup_close').remove();
        jQuery('#sequra_campaign_info_container').append(
            jQuery('#sequra_campaign_popup_checkout .sequra_white_content')
        );
    });
</script>