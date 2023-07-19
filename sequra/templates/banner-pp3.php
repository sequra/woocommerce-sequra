<?php
/**
 * Partpayment teaser template.
 *
 * @package woocommerce-sequra
 */

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable
if ( isset( $atts['color'] ) ) { ?>
	<style>
		#sequra-banner-partpayment .sqblock .sqnoc,
		#sequra-banner-partpayment .sequra-educational-popup {
			color:
				<?php echo esc_html( $atts['color'] ); ?>
			;
		}

		#sequra-banner-partpayment .sequra-educational-popup {
			border-color:
				<?php echo esc_html( $atts['color'] ); ?>
			;
		}

		#sequra-banner-partpayment #block1 {
			background:
				<?php echo esc_html( $atts['color'] ); ?>
			;
		}
	</style>
<?php } ?>
<div id="sequra-banner-partpayment" class="sequra-banner">
	<div id="block1" class="sqblock">
		<span class="sqheader">FRACCIONAR PAGO</span>
	</div>
	<div id="block2" class="sqblock">
		<span class="sqnoc icon-puzzle">&nbsp;</span>
		<div class="sqinner">
			<span class="sqheader">Fracciona tu pago</span>
			<span class="sqcontent">Fracciona tu pago en nuestra tienda.</span>
		</div>
	</div>
	<div id="block3" class="sqblock">
		<span class="sqnoc icon-check-paiper">&nbsp;</span>
		<div class="sqinner">
			<span class="sqheader">Inmediato</span>
			<span class="sqcontent">Sin papeleo, directamente al finalizar el pedido.</span>
		</div>
	</div>
	<div id="block4" class="sqblock">
		<div class="sqinner">
			<span class="sqheader">Un coste fijo por cuota</span>
			<span class="sqcontent sequra-educational-popup" data-product="pp3">Más información</span>
		</div>
	</div>
</div>
