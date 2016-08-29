Sin intereses, en
<?php
$i = 1;
$last = count($this->credit_agreements[$this->pp_product]);
foreach ($this->credit_agreements[$this->pp_product] as $ca) {
	echo $i==$last?'o ':'';
	echo sprintf(__('<b>%s</b> cuotas de <b>%s/mes</b>', 'wc_sequra'), $ca['instalment_count'], $ca['instalment_total']['string']);
	echo ($i==$last?'':', ');
	$i++;
}
?>. Inmediato, sin papeleo y con sólo un coste fijo por cuota.

<b>¿Cómo funciona?</b>
<p>
	1) Elige Fracciona tu pago con SeQura al realizar tu pedido y paga sólo la primera cuota.<br/>
	2) ​Recibe tu pedido.<br/>
	3) ​El resto de pagos se cargarán automáticamente a tu tarjeta.
</p>
<p>Además puedes pagar el total de tu pedido cuando quieras sin costes adicionales.</p>

<b>¿Cuánto cuesta este servicio?</b>
<p>El coste del servicio es <?php $this->credit_agreements[$this->pp_product][0]['instalment_fee']['string']?> por cuota, dependiendo del importe del pedido. No hay intereses ni existe ningún otro pago adicional.</p>

<a href="https://www.sequra.es/preguntas-frecuentes.html" target="_blank">Tengo más preguntas</a>