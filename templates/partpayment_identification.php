<div class="sequra_popup" id="sequra_partpayments_identity_form_popup">
	<div class="sequra_white_content">
		<div class="sequra_content_popup">
			<img id="sequra_partpayment_logo"
				 src="<?php echo WP_PLUGIN_URL . "/" . dirname(plugin_basename(__FILE__)); ?>/../assets/img/mrq.png"
				 alt="Fraccionar tu pago. Sin intereses y sólo un coste per cuota"/>
			<h4 id="sequra_text_title">Fraccionar tu pago. Sin intereses y sólo un coste por cuota</h4>

			<p id="sequra_additional_text">
			<ul>
				<li>La aprobación es instantánea, por lo que los productos se envían de forma inmediata</li>
				<li>El único coste es de 3€ por cuota o de 5€ si el pedido es superior a 200€. Sin intereses ocultos ni
					letra pequeña.
				</li>
				<li>El primer pago se hace con tarjeta, pero los siguientes se pueden hacer con tarjeta o por
					transferencia bancaria
				</li>
				<li>Puedes pagar la totalidad cuando tú quieras</li>
				<li>El servicio es ofrecido junto con <a target="_blank"
														 href="https://sequra.es/es/fraccionados">SeQura</a></li>
			</ul>
			</br>
			¿Tienes alguna pregunta? Habla con SeQura a través del 93 176 00 08
			</p>
			<h5 class="sequra_partpayment_steps">1. Escoje en cuantas cuotas quieres fraccionar tu compra</h5>

			<div id="sequra-wrapper"></div>
			<h5 class="sequra_partpayment_steps">2. Completa tus datos para finalizar la compra</h5>

			<div id="sequra_partpayment_steps_section">
				<?php echo $this->identity_form; ?>
			</div>
		</div>
		<div class="clearfix"></div>
		<p class="cart_navigation">
			<a href="<?php echo $back; ?>" id="sequra_back">&#9668; Otros métodos de pago</a>
		</p>
	</div>
</div>
<div class="sequra_popup" id="sequra_partpayments_popup">
	<div class="sequra_white_content">
		<div class="sequra_content_popup">
			<h3>Con este servicio puedes comprar ahora y dividir el pago en peque&ntilde;as cuotas</h3>

			<div>
				<p>Para este servicio nuestro partner <a href="https://sequra.es/es/fraccionados"
														 target="_blank">SeQura</a>
					aplica un peque&ntilde;o coste fijo a cada cuota. Para tu compra es de <span
						id="partPayments_fee"></span>
					por cuota.
					No hay ninguna otra comisi&oacute;n. Puedes pagar el total del importe cuando desees.
				</p>

				<p>En total habr&aacute;s pagado <span id="partPayments_total"></span>, de los cuales <span
						id="partPayments_fees"></span>
					son comisi&oacute;n.
				</p>

				<p>Para esta compra la <abbr title="Tasa Anual Equivalente">TAE</abbr> es <span
						id="partPayments_apr"></span>. La TAE no es el inter&eacute;s de la compra, ya que no
					hay inter&eacute;s (el "TIN" o Tipo de Inter&eacute;s Nominal es fijo al 0%), pero se utiliza
					para comparar
					cr&eacute;ditos. <em>Este n&uacute;mero acostumbra a ser m&aacute;s alto cuando
						el plazo de pago es m&aacute;s corto.</em>
				</p>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
	(function () {
		new SequraFraction({
			totalPrice: <?php echo $total_price;?>,
			element: document.getElementById('sequra-wrapper')
		});
	})();

	jQuery( document ).ready(function($) {
		$('#sequra_partpayments_identity_form_popup').fadeIn();
		$("#sequra_partpayments_identity_form_popup .sequra_popup_close").on('click', function() {
			document.location.href = $('#sequra_back').attr('href');
		});
		$('#sequra-identification  input[type="submit"]').removeClass().addClass("checkout-button button alt wc-forward");
	});
</script>