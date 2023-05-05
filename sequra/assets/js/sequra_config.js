
jQuery( document ).ready(
	function () {
		function sq_update_dependent_fields(){
			jQuery( ".form-table input[dependson]" ).each(
				function(e){
					jQuery( '#woocommerce_sequra_' + jQuery( this ).attr( 'dependson' ) ).off();
					jQuery( '#woocommerce_sequra_' + jQuery( this ).attr( 'dependson' ) ).on( 'click',sq_update_dependent_fields );
					var el = jQuery( this ).parent().parent().parent();
					if (jQuery( this ).attr( 'type' ) == 'checkbox') {
						el = el.parent();
					}
					if (jQuery( '#woocommerce_sequra_' + jQuery( this ).attr( 'dependson' ) ).is( ':checked' )) {
						el.show();
					} else {
						el.hide();
					}
				}
			);
		}
		sq_update_dependent_fields();
	}
);
