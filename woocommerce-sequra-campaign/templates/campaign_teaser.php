<?php
if ( $sequracampaign->is_available() ) { ?>
    <div id="sequra_campaign_teaser_container" style="clear:both"><div id="sequra_campaign_teaser"></div></div>
    <script type="text/javascript">
        Sequra.onLoad(function(){
            SequraHelper.drawPromotionWidget(
                '<?php echo $price_container; ?>',
                '#sequra_campaign_teaser',
                '<?php echo $sequracampaign->product;?>',
                '<?php echo $sequracampaign->settings['widget_theme'];?>',
                0,
                '<?php echo $sequracampaign->campaign;?>'
            );
            Sequra.refreshComponents();
        });
    </script>
	<?php
}