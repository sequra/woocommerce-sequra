<?php
/**
 * Identification page template.
 *
 * @package woocommerce-sequra
 */

if ( $identity_form ) {
	// phpcs:disable
	echo wp_kses(
		$identity_form,
		[
			'iframe',
			'script',
		],
		['https']
	);
	// phpcs:enable
	?>
<script type="text/javascript">
	function tryToOpenPumbaa(){
		try{
			window.SequraFormInstance.setCloseCallback(function (){
				document.location.href = '<?php echo esc_js( wc_get_checkout_url() ); ?>';
			});
			window.SequraFormInstance.show();
			jQuery('.sq-identification-iframe').appendTo('body');
		}catch(e){
			setTimeout(tryToOpenPumbaa,500);
		}
	}

	document.addEventListener("DOMContentLoaded", function () {
		tryToOpenPumbaa();
	});
</script>
<?php } else { ?>
	<script type="text/javascript">
		alert("Lo sentimos, ha habido un error.\n Contacte con el comercio, por favor.");
		document.location.href = '<?php echo esc_js( wc_get_checkout_url() ); ?>';
	</script>
<?php } ?>
