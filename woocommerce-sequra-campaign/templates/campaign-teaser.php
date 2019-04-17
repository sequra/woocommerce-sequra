<?php
/**
 * Campaign teaser template.
 *
 * @package woocommerce-sequracampaign
 */

if ( $sequracampaign->is_available() ) { ?>
	<div id="sequra_campaign_teaser_container" style="clear:both"><div id="sequra_campaign_teaser"></div></div>
	<script type="text/javascript">
		Sequra.onLoad(function(){
			SequraHelper.drawPromotionWidget(
				'<?php echo esc_js( $price_container ); ?>'.replace(/\&gt\;/g, ">",
				'<?php echo esc_js( $dest ); ?>'.replace(/\&gt\;/g, ">",
				'<?php echo esc_js( $sequracampaign->product ); ?>',
				'<?php echo esc_js( $sequracampaign->settings['widget_theme'] ); ?>'.replace(/\&quot\;/g, "\""),
				0,
				'<?php echo esc_js( $sequracampaign->campaign ); ?>'
			);
			Sequra.refreshComponents();
		});
	</script>
	<?php
}
