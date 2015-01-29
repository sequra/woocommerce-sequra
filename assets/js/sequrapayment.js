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
        jQuery('.sequra_popup .sequra_white_content').each(function (){
            jQuery(this).prepend('<a class="sequra_popup_close">close</a>');
        });

        jQuery(document).delegate(".sequra_popup_close", 'click', function() {
            jQuery(this).parent().parent().fadeOut();
            return false;
        });
    }
};

(function () { SequraHelper.preparePopup(); });
