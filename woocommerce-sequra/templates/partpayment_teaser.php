<script type='text/javascript'>
    /*Customize if needed*/
    VARIATION_PRICE_SEL = '.woocommerce-variation-price .price>.amount,.woocommerce-variation-price .price ins .amount';
    /*********************/

    function displaySequraPatpaymentTeaser() {
        var the_price_container = '<?php echo $price_container; ?>';
        var initial_dest = 'body';
        if(jQuery('.woocommerce-variation-price').length){
            initial_dest = '.woocommerce-variation-price';
        }
        SequraHelper.drawPromotionWidget(the_price_container,initial_dest,'pp3','<?php echo $theme;?>',0);
        placeSequraPartpaymentTeaser();
    }

    function placeSequraPartpaymentTeaser(){
        var dest = '#sequra_partpayment_teaser_default_container';
        <?php if(isset( $atts['dest'] ) && $atts['dest'] != '') {?>
        if (jQuery('<?php echo $atts['dest'];?>').is(':visible')) {
            dest = '<?php echo $atts['dest'];?>';
        }
	    <?php } ?>
        jQuery(dest).append(jQuery('[data-product=pp3]'));
        Sequra.onLoad( function () {Sequra.refreshComponents();});
    }

    function updateSequraPatpaymentTeaser(e){
        var the_price_container = '<?php echo $price_container; ?>';
        if (e.type == 'show_variation' && jQuery(VARIATION_PRICE_SEL).length) {
            the_price_container = VARIATION_PRICE_SEL;
        }
        var price_in_cents = SequraHelper.selectorToCents(the_price_container);
        jQuery('[data-product=pp3]').attr('data-amount',price_in_cents);
        Sequra.onLoad( function () {Sequra.refreshComponents();});
    }

    jQuery(window).on('load', function () {
        displaySequraPatpaymentTeaser();
        jQuery('.variations_form')
            .on('hide_variation', updateSequraPatpaymentTeaser)
            .on('show_variation', updateSequraPatpaymentTeaser);
    });

</script>