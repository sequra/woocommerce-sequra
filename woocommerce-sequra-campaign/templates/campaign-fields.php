<?php
/**
 * Payment fields template.
 *
 * @package woocommerce-sequracampaign
 */

?>
<div class="sequra-promotion-widget"
	data-amount="<?php echo esc_js( $this->get_order_total() * 100 ); ?>"
	data-product="pp5"
	data-campaign="<?php echo esc_js( $this->campaign ); ?>"
	data-type="text"
	data-branding="none"
	data-alignment="left">
</div>
<script>
	Sequra.onLoad( function () {Sequra.refreshComponents();});
</script>
