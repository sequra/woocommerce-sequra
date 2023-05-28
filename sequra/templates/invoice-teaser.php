<?php
/**
 * Invoice teaser template.
 *
 * @package woocommerce-sequra
 */

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable
?>
<div id="sequra_invoice_teaser_container" style="clear:both"><div id="sequra_invoice_teaser"></div></div>
<script type="text/javascript">
	Sequra.onLoad(function(){
		SequraHelper.drawPromotionWidget(
			'<?php echo esc_js( $price_container ); ?>'.replace(/\&gt\;/g, ">"),
			'<?php echo esc_js( $dest ); ?>'.replace(/\&gt\;/g, ">"),
			'<?php echo esc_js( $product ); ?>',
			'<?php echo esc_js( $theme ); ?>'.replace(/\&quot\;/g, "\""),
			0,
			'<?php echo esc_js( $campaign ); ?>'
		);
		Sequra.refreshComponents();
	});
</script>

