<?php
if ( $sequracampaign->is_available() ) { ?>
    <div id="sequra_campaign_teaser"></div>
    <script type="text/javascript">
        jQuery(function () {
            campaignTeaser = new SequraCampaignTeaser(
                {
                    container: '#sequra_campaign_teaser',
                    product: '<?php echo $sequracampaign->product;?>',
                    campaign: '<?php echo $sequracampaign->campaign;?>'
                }
            );
            campaignTeaser.draw();
        });
    </script>
	<?php
}