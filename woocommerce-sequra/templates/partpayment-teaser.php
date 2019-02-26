<?php
/**
 * Part payment teaser template.
 *
 * @package woocommerce-sequra
 */

?>
<script type='text/javascript'>
	/*Customize if needed*/
	VARIATION_PRICE_SEL = '.woocommerce-variation-price .price>.amount,.woocommerce-variation-price .price ins .amount';

	/*********************/

	function displaySequraPatpaymentTeaser() {
		<?php // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		var the_price_container = '<?php echo $price_container; ?>';
		<?php // phpcs:enable ?>
		var dest = '#sequra_partpayment_teaser_default_container';
		if (jQuery('.woocommerce-variation-price').length) {
			dest = '.woocommerce-variation-price';
		}
		<?php if ( isset( $atts['dest'] ) && '' !== trim( $atts['dest'] ) ) { ?>
		if (jQuery('<?php echo esc_js( $atts['dest'] ); ?>').is(':visible')) {
			dest = '<?php echo esc_js( $atts['dest'] ); ?>';
		}
		<?php } ?>
		SequraHelper.drawPromotionWidget(the_price_container, dest, 'pp3', '<?php echo esc_js( $theme ); ?>', 0);
		Sequra.onLoad(function () {
			Sequra.refreshComponents();
		});
	}

	function updateSequraPatpaymentTeaser(e) {
		var the_price_container = '<?php echo esc_js( $price_container ); ?>';
		if (e.type == 'show_variation' && jQuery(VARIATION_PRICE_SEL).length) {
			the_price_container = VARIATION_PRICE_SEL;
		}
		var price_in_cents = SequraHelper.selectorToCents(the_price_container);
		jQuery('[data-product=pp3]').attr('data-amount', price_in_cents);
		Sequra.onLoad(function () {
			Sequra.refreshComponents();
		});
	}

	jQuery(window).on('load', function () {
		displaySequraPatpaymentTeaser();
		jQuery('.variations_form')
			.on('hide_variation', updateSequraPatpaymentTeaser)
			.on('show_variation', updateSequraPatpaymentTeaser);
	});

</script>
