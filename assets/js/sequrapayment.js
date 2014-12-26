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
		jQuery('.sequra_popup').each(function (){
            SequraHelper.popupize(jQuery( this ));
        });
	},

    popupize: function(pop){
        if(pop.hasClass('popupized'))
            return;

        jQuery("*[rel="+pop.attr('id')+"]").on('click', function() {
            jQuery('#'+jQuery( this ).attr('rel')).fadeToggle();
            return false;
        });
        jQuery('.sequra_white_content',pop).each(function (){
            jQuery( this ).prepend('<a class="sequra_popup_close">close</a>');
        });
        jQuery(".sequra_popup_close",pop).on( 'click', function() {
            jQuery( this ).parent().parent().fadeToggle();
            return false;
        });
        pop.addClass('popupized');
    }
};
jQuery(document).ready(function () { SequraHelper.preparePopup(); });
