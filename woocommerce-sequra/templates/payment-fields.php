<?php
/**
 * Payment fields template.
 *
 * @package woocommerce-sequra
 */

?>
<div class="sequra-promotion-widget"
	data-amount="<?php echo intval( $this->get_order_total() * 100 ); ?>"
	data-product="i1"
	data-type="text"
	data-branding="none"
	data-alignment="left"></div>
<script>
	Sequra.onLoad( function () {Sequra.refreshComponents();});
</script>
