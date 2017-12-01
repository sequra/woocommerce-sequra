<script type='text/javascript'>
    function displaySequraPatpaymentTeaser() {
        jQuery('#sequra_partpayment_teaser').remove();
        var the_price_container = '<?php echo $price_container; ?>';
        if (jQuery('.woocommerce-variation-price .price>.amount,.woocommerce-variation-price .price ins .amount').length) {
            the_price_container = '.woocommerce-variation-price .price>.amount,.woocommerce-variation-price .price ins .amount';
        }
        jQuery('#sequra_partpayment_teaser_default_container').append("<div id='sequra_partpayment_teaser'></div>");

        partPaymnetTeaser = new SequraPartPaymentTeaser(
            {
                container: '#sequra_partpayment_teaser',
                price_container: the_price_container
            });
        partPaymnetTeaser.draw();
        partPaymnetTeaser.preselect(20);
        placeSequraPartpaymentTeaser();
    }

    function placeSequraPartpaymentTeaser(){
	    <?php if(isset( $atts['dest'] ) && $atts['dest'] != '') {?>
        if (jQuery('<?php echo $atts['dest'];?>').is(':visible')) {
            jQuery('<?php echo $atts['dest'];?>').after(jQuery('#sequra_partpayment_teaser'));
            return;
        }
	    <?php } ?>
        var dest = '#sequra_partpayment_teaser_default_container';
        if (jQuery('.woocommerce-variation-price .price>.amount,.woocommerce-variation-price .price ins .amount').length) {
            dest = '.woocommerce-variation-price';
        }
        jQuery(dest).append(jQuery('#sequra_partpayment_teaser'));
    }

    jQuery(window).on('load', function () {
        Sequra.decimal_separator = '<?php echo wc_get_price_decimal_separator(); ?>';
        displaySequraPatpaymentTeaser();
        jQuery('.variations_form')
            .on('hide_variation', displaySequraPatpaymentTeaser)
            .on('show_variation', displaySequraPatpaymentTeaser);
    });

</script>