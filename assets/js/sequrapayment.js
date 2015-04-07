var SequraHelper = {
    postToUrl: function(e) {
        var url = typeof(e) === 'string' ? e : jQuery(e).data('url');
        var form = jQuery("<form method='post'></form>");
        form.attr('action', url).hide().appendTo('body').submit();
        return false;
    },
    /*
     * Let's define all popups in a div with class sequra_popup and a unique id
     * The link that displays the popup will have this unique id in the rel attribute
     */
    preparePopup: function() {
        jQuery('.sequra_popup').not('.sequra_popup_prepared').each(function (){
            /**
             * Avoid attaching event twice if preparePopup is called more than once.
             */
            jQuery(this).addClass('sequra_popup_prepared');
            /**
             * Create relation between link and popup
             */
            popup_identifier = jQuery(this).attr('id');
            jQuery(document).delegate("*[rel="+popup_identifier+"]", 'click', function() {
                jQuery('#'+jQuery( this ).attr('rel')).fadeIn();
                return false;
            });
        });

        /*
        * Add close button to popups
        */
        jQuery('.sequra_popup .sequra_white_content.closeable').each(function (){
            jQuery(this).prepend('<a class="sequra_popup_close">close</a>');
        });

        jQuery(document).delegate(".sequra_popup_close", 'click', function() {
            jQuery(this).parent().parent().fadeOut();
            return false;
        });
    },

    preparePartPaymentAcordion: function() {
        function displayFirstStep() {
            jQuery("#sequra_partpayment_alt_tittle").hide();
            jQuery("#sequra_partpayment_tittle").show();
            jQuery("#first_step_content").slideDown()
            jQuery("#second_step_content").slideUp()
        };
        function displaySecondStep() {
            jQuery("#sequra_partpayment_tittle").hide();
            jQuery("#first_step_content").slideUp();
            jQuery("#sequra_partpayment_alt_tittle").show();
            jQuery("#second_step_content").slideDown()
        };
        jQuery(document).delegate("#sequra_partpayment_tittle2, #part_payment_last_step", 'click', function() {
            displaySecondStep();
            return false;
        });
        jQuery(document).delegate("#sequra_partpayment_alt_tittle,#sequra_partpayment_tittle", 'click', function() {
            displayFirstStep();
            return false;
        })
        jQuery("#sequra_partpayment_alt_tittle").hide();
    }
};

(jQuery(function () { SequraHelper.preparePopup(); }));