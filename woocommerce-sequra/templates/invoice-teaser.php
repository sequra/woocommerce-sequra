<?php
/**
 * Invoice teaser template.
 *
 * @package woocommerce-sequra
 */

if ( $sequra->helper->is_available( $product->get_id() ) && $product->get_price() < $sequra->max_amount ) { ?>
<div id="sequra_invoice_teaser_container" style="clear:both"><div id="sequra_invoice_teaser"></div></div>
	<script type="text/javascript">
		Sequra.onLoad(function(){
			SequraHelper.drawPromotionWidget(
				'<?php echo esc_js( $price_container ); ?>'.replace(/\&gt\;/g, ">"),
				'<?php echo esc_js( $dest ); ?>'.replace(/\&gt\;/g, ">"),
				'i1',
				'<?php echo esc_js( $theme ); ?>'.replace(/\&quot\;/g, "\""),
				0
			);
			Sequra.refreshComponents();
		});
	</script>
	<?php
}
