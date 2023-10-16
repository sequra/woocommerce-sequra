<?php
/**
 * Part payment teaser template.
 *
 * @package woocommerce-sequra
 */

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable
?>
<script type='text/javascript'>
	/*Customize if needed*/
	VARIATION_PRICE_SEL = '.woocommerce-variation-price .price>.amount,.woocommerce-variation-price .price ins .amount,.woocommerce-variation-price .price .amount';

	/*********************/

	function displaySequra<?php echo esc_js( $product ); ?>Teaser(the_price_container, sq_product, campaign, theme) {
		var dest = '#sequra_partpayment_teaser_default_container';
		if (jQuery('.woocommerce-variation-price').length) {
			dest = '.woocommerce-variation-price';
			the_price_container = VARIATION_PRICE_SEL;
		}

		<?php if ( isset( $atts['dest'] ) && '' !== trim( $atts['dest'] ) ) { ?>
			var custom_dest = '<?php echo esc_js( $atts['dest'] ); ?>'.replace(/\&gt\;/g, ">");
			if (jQuery(custom_dest).is(':visible')) {
				dest = custom_dest;
			}
		<?php } ?>
		SequraHelper.waitForElement(the_price_container).then(function () {
			SequraHelper.waitForElement(dest).then(function () {
				SequraHelper.drawPromotionWidget(the_price_container, dest, sq_product, theme, 0, campaign, <?php echo esc_js( $registration_amount ); ?>);
				Sequra.onLoad(function () {
					Sequra.refreshComponents();
				});
			});
		});
	}

	function updateSequra<?php echo esc_js( $product ); ?>Teaser(e) {
		var the_price_container = '<?php echo esc_js( $price_container ); ?>'.replace(/\&gt\;/g, ">");
		if (e.type == 'show_variation' && jQuery(VARIATION_PRICE_SEL).length) {
			the_price_container = VARIATION_PRICE_SEL;
		}
		var price_in_cents = SequraHelper.selectorToCents(the_price_container);
		jQuery('[data-product=<?php echo esc_js( $product ); ?>]').attr('data-amount', price_in_cents);
		Sequra.onLoad(function () {
			Sequra.refreshComponents();
		});
	}

	document.addEventListener("DOMContentLoaded", function () {
		displaySequra<?php echo esc_js( $product ); ?>Teaser(
		'<?php echo esc_js( $price_container ); ?>'.replace(/\&gt\;/g, ">"),
		'<?php echo esc_js( $product ); ?>',
		'<?php echo esc_js( $campaign ); ?>',
		'<?php echo esc_js( $theme ); ?>'.replace(/\&quot\;/g, "\"")
	);
		jQuery('.variations_form')
			.on('hide_variation', updateSequra<?php echo esc_js( $product ); ?>Teaser)
			.on('show_variation', updateSequra<?php echo esc_js( $product ); ?>Teaser);
	});
</script>
