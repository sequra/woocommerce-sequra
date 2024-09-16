<?php
/**
 * Receipt page for seQura payment gateway.
 *
 * @package    SeQura/WC
 * @var array<string, string> $args The arguments. Must contain:
 *  - string 'form' The identification form HTML code.
 */

defined( 'WPINC' ) || die;

if ( empty( $args['form'] ) ) {
	return;
}

?>

<p>
<?php 
echo wp_kses_post( __( 'Thank you for your order. Please fill the required data to pay with seQura...', 'sequra' ) );
?>
</p>

<?php

echo wp_kses(
	$args['form'],
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

?>

<script type="text/javascript">
	(function(){
		const tryToShowForm = () => {
			try {
				window.SequraFormInstance.setCloseCallback(function () {
					document.location.href = '<?php echo esc_js( wc_get_checkout_url() ); ?>';
				});
				window.SequraFormInstance.show();
				const iframe = document.querySelector('.sq-identification-iframe');
				document.body.append(iframe);
				// jQuery('.sq-identification-iframe').appendTo('body');
			} catch (e) {
				setTimeout(tryToShowForm, 100);
			}
		};
		document.addEventListener("DOMContentLoaded", tryToShowForm);

	})();
</script>