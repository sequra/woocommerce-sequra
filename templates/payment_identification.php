<div class="sequra-css-reset sequra_popup" id="sequra_identity_form_popup">
	<div class="sequra_white_content <?php echo $this->identity_form?'':'closeable';?>">
		<div class="sequra_content_popup">
			<?php if($this->identity_form){ ?>
			<div id="before-sequra">
				<h2>Pagarás una vez hayas recibido y comprobado tu pedido.</h2>
			</div>
			<?php
				echo $this->identity_form;
			?>
			<div id="after-sequra">
				<p>Este es un servicio ofrecido por <?php echo bloginfo('name'); ?> para que te sea fácil, rápido y cómodo
					comprar con nosotros. Pagarás con tarjeta o transferencia bancaria en 7 días y después de haber
					comprobado tu pedido. <a class="sequra_other_payment_methods"
																	 href="javascript:history.back(1);">Otros métodos de pago</a>.
				</p>
			</div>
			<?php } else { ?>
				<div id="before-sequra">
					<h2>Lo sentimos, ha habido un error</h2>
				</div>
				<div id="after-sequra">
					<p>Complete su pedido utilizando <a class="sequra_other_payment_methods"
																		 href="javascript:history.back(1);">Otro método de pago</a>.
					</p>
				</div>
			<?php } ?>
		</div>
	</div>
</div>
<script type="text/javascript">
	jQuery(document).ready(function ($) {
		SequraHelper.preparePopup();
		jQuery('#sequra_identity_form_popup').fadeIn();
		jQuery(document).delegate("#sequra_identity_form_popup .sequra_popup_close", 'click', function() {
			history.back(1);
		});
	});
</script>