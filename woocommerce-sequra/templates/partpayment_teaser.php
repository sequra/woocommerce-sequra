<div id='sequra_partpayment_teaser_default_container'></div>
<script type='text/javascript'>
    function displaySequraPatpaymentTeaser() {
        jQuery('#sequra_partpayment_teaser').remove();
        var the_price_container = '<?php echo $price_container; ?>';
        if (jQuery('.woocommerce-variation-price').length) {
            the_price_container = the_price_container.replace(/\.summary/g, '.woocommerce-variation-price');

        }
        jQuery('#sequra_partpayment_teaser_default_container').append("<div id='sequra_partpayment_teaser'></div>");

        partPaymnetTeaser = new SequraPartPaymentTeaser(
            {
                container: '#sequra_partpayment_teaser',
                price_container: the_price_container
            });
        partPaymnetTeaser.draw();
        partPaymnetTeaser.preselect(20);
		<?php if(isset( $atts['dest'] ) && $atts['dest'] != '') {?>
        if (jQuery('<?php echo $atts['dest'];?>').is(':visible')) {
            jQuery('<?php echo $atts['dest'];?>').after(jQuery('#sequra_partpayment_teaser'));
        }
		<?php } ?>
    }

    jQuery(window).on('load', function () {
        Sequra.decimal_separator = '<?php echo wc_get_price_decimal_separator(); ?>';
        displaySequraPatpaymentTeaser();
        jQuery('.variations_form').on('woocommerce_variation_has_changed', displaySequraPatpaymentTeaser);
    });

</script>