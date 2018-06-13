<div class="sequra-promotion-widget"
     data-amount="<?php echo $this->get_order_total()*100;?>"
     data-product="pp5"
     data-campaign="<?php echo $this->campaign;?>"
     data-theme="<?php echo $this->settings['widget_theme'];?>"></div>
<script>
    Sequra.onLoad( function () {Sequra.refreshComponents();});
</script>