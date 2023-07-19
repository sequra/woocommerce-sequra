<?php
/**
 * Invoice banner template.
 *
 * @package woocommerce-sequra
 */

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable
if ( isset( $atts['color'] ) ) { ?>
	<style>
		#sequra-banner-invoice .sqblock .sqnoc,
		#sequra-banner-invoice .sequra-educational-popup {
			color:
				<?php echo esc_html( $atts['color'] ); ?>
			;
		}

		#sequra-banner-invoice .sequra-educational-popup {
			border-color:
				<?php echo esc_html( $atts['color'] ); ?>
			;
		}

		#sequra-banner-invoice #block1 {
			background:
				<?php echo esc_html( $atts['color'] ); ?>
			;
		}
	</style>
<?php } ?>

<div id="sequra-banner-invoice" class="sequra-banner">
	<div id="block1" class="sqblock">
		<span class="sqheader">COMPRA AHORA, PAGA DESPU&Eacute;S</span>
	</div>
	<div id="block2" class="sqblock">
		<span class="sqnoc icon-puzzle">&nbsp;</span>
		<div class="sqinner">
			<span class="sqheader">1. Pide sin tarjeta</span>
			<span class="sqcontent">Haz tu pedido on-line ahora.</span>
		</div>
	</div>
	<div id="block3" class="sqblock">
		<span class="sqnoc icon-check-paiper">&nbsp;</span>
		<div class="sqinner">
			<span class="sqheader">2. Recibe tu pedido</span>
			<span class="sqcontent">Recibe tu pedido y compru&eacute;balo</span>
		</div>
	</div>
	<div id="block4" class="sqblock">
		<div class="sqinner">
			<span class="sqheader">3. Paga después</span>
			<span class="sqcontent sequra-educational-popup" data-product="i1">Más información</span>
		</div>
	</div>
</div>
