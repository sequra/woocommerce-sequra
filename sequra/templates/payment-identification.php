<?php
/**
 * Identification page template.
 *
 * @package woocommerce-sequra
 */

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable
if ( $identity_form ) {
	add_filter( 'safe_style_css', 'SequraHelper::allow_css_attributes' );
	echo wp_kses(
		$identity_form,
		array(
			'iframe' => array(
				'id'          => array(),
				'name'        => array(),
				'class'       => array(),
				'src'         => array(),
				'frameborder' => array(),
				'style'       => array(),
				'type'        => array(),
			),
			'script' => array(
				'type' => array(),
				'src'  => array(),
			),
		),
		array( 'https' )
	);
	remove_filter( 'safe_style_css', 'SequraHelper::allow_css_attributes' );
	?>
	<script type="text/javascript">
		function tryToOpenPumbaa() {
			try {
				window.SequraFormInstance.setCloseCallback(function () {
					document.location.href = '<?php echo esc_js( wc_get_checkout_url() ); ?>';
				});
				window.SequraFormInstance.show();
				jQuery('.sq-identification-iframe').appendTo('body');
			} catch (e) {
				setTimeout(tryToOpenPumbaa, 500);
			}
		}
		document.addEventListener("DOMContentLoaded", function () {
			tryToOpenPumbaa();
		});
	</script>
<?php } else { ?>
	<script type="text/javascript">
		alert("<?php echo esc_js( __( 'Sorry, something went wrong.\n Please contact the merchant.', 'sequra' ) ); ?>");
		document.location.href = '<?php echo esc_js( wc_get_checkout_url() ); ?>';
	</script>
<?php } ?>
