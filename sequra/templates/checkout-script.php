<?php
/**
 * Script for the checkout page.
 *
 * @package woocommerce-sequra
 */

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable
?>
<script>
	jQuery('.payment_method_sequra').show();
	if (typeof window.sq_product_campaign === 'undefined') {
		window.sq_product_campaign = jQuery('input[name=sq_product_campaign]:first').val()
	}
	jQuery('input[value=' + sq_product_campaign + ']').prop('checked', true);
	jQuery('#payment_method_sequra').removeClass('input-radio').hide();
	jQuery('label[for=payment_method_sequra').hide();
	jQuery('div.payment_method_sequra').removeClass('payment_box');
	jQuery('input[name=sq_product_campaign]').on('click', function () {
		jQuery('#payment_method_sequra').prop('checked', true).click();
		window.sq_product_campaign = jQuery(this).val();
		jQuery('input[name="payment_method"]').trigger('change');
	});
	jQuery('input.input-radio:not(.sq-input-radio)').on('click', function () {
		jQuery('input[name=sq_product_campaign]').prop('checked', false);
	});
	jQuery(document.body).on('update_checkout', function (e) {
		jQuery('.payment_method_sequra').show();
		jQuery('#payment_method_sequra').removeClass('input-radio').hide();
	});
	Sequra.onLoad(function () { Sequra.refreshComponents(); });
</script>
