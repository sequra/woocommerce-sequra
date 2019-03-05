<?php
/**
 * Header script for widgets
 *
 * @package woocommerce-sequra
 */

?>
<script type="text/javascript">
<?php // phpcs:disable ?>
var sequraProducts = ['<?php echo implode( "','", $available_products ); ?>'];
<?php // phpcs:enable ?>
var sequraConfigParams = {
	merchant: '<?php echo esc_html( $core_settings['merchantref'] ); ?>',
	assetKey: '<?php echo esc_html( $core_settings['assets_secret'] ); ?>',
	products: sequraProducts,
	scriptUri: '<?php echo esc_html( $script_base_uri ); ?>sequra-checkout.min.js',
	decimalSeparator: '<?php echo esc_html( wc_get_price_decimal_separator() ); ?>',
	thousandSeparator: '<?php echo esc_html( wc_get_price_thousand_separator() ); ?>',
	rebranding: true,
};

(function (i, s, o, g, r, a, m) {i['SequraConfiguration'] = g;i['SequraOnLoad'] = [];i[r] = {};i[r][a] = function (callback) {i['SequraOnLoad'].push(callback);};(a = s.createElement(o)), (m = s.getElementsByTagName(o)[0]);a.async = 1;a.src = g.scriptUri;m.parentNode.insertBefore(a, m);})(window, document, 'script', sequraConfigParams, 'Sequra', 'onLoad');


//Helper
var SequraHelper = {
	presets: {
		legacy: '{"type":"legacy"}'
	},
	drawnWidgets: [],
	getText: function (selector) {
		return  selector && document.querySelector(selector)?document.querySelector(selector).innerText:"0";
	},

	selectorToCents: function (selector) {
		return SequraHelper.textToCents(SequraHelper.getText(selector));
	},

	textToCents: function (text) {
		return SequraHelper.floatToCents(
			parseFloat(
				text.replace(/^\D*/,'')
					.replace(sequraConfigParams.thousandSeparator,'')
					.replace(sequraConfigParams.decimalSeparator,'.')
			)
		);
	},

	floatToCents: function (value) { 
		return parseInt(value.toFixed(2).replace('.', ''), 10);
	},

	mutationCallback: function(mutationlist, mutationobserver) {
		var price_src = mutationobserver.observed_as;
		var new_amount = SequraHelper.selectorToCents(price_src);
		document.querySelectorAll('[observes=\"' + price_src + '\"]').forEach(function(item) {
			item.setAttribute('data-amount', new_amount);
	});
		Sequra.refreshComponents();
	},

	drawPromotionWidget: function (price_src,dest,product,theme,reverse,campaign) {
		if(this.drawnWidgets.indexOf(price_src+dest+product+theme+reverse+campaign)>=0){
			return;
		}
		this.drawnWidgets.push(price_src+dest+product+theme+reverse+campaign);
		var promoWidgetNode = document.createElement('div');
		var price_in_cents = 0;
		try{
			var srcNode = document.querySelector(price_src);
			var MutationObserver    = window.MutationObserver || window.WebKitMutationObserver;
			if(MutationObserver && srcNode){//Don't break if not supported in browser
				if(!srcNode.getAttribute('observed-by-sequra-promotion-widget')){//Define only one observer per price_src
					var mo = new MutationObserver(SequraHelper.mutationCallback);
					mo.observe(srcNode, {childList: true, subtree: true});
					mo.observed_as = price_src;
					srcNode.setAttribute('observed-by-sequra-promotion-widget',1);
				}
			}
			promoWidgetNode.setAttribute('observes',price_src);
			price_in_cents = SequraHelper.selectorToCents(price_src)
		}
		catch(e){
			if(price_src){
				console.error(price_src + ' is not a valid css selector to read the price from, for sequra widget.');
				return;
			}
		}
		try{
			var destNode = document.querySelector(dest);
		}
		catch(e){
			console.error(dest + ' is not a valid css selector to write sequra widget to.');
			return;
		}
		promoWidgetNode.className = 'sequra-promotion-widget';
		promoWidgetNode.setAttribute('data-amount',price_in_cents);
		promoWidgetNode.setAttribute('data-product',product);
		if(this.presets[theme]){
			theme = this.presets[theme]
		}
		try {
			attributes = JSON.parse(theme);
			for (var key in attributes) {
				promoWidgetNode.setAttribute('data-'+key,""+attributes[key]);
			}
		} catch(e){
			promoWidgetNode.setAttribute('data-type','text');
		}
		if(reverse){
			promoWidgetNode.setAttribute('data-reverse',reverse);
		}
		if(campaign){
			promoWidgetNode.setAttribute('data-campaign',campaign);
		}
		if (destNode.nextSibling) {//Insert after
			destNode.parentNode.insertBefore(promoWidgetNode, destNode.nextSibling);
		}
		else {
			destNode.parentNode.appendChild(promoWidgetNode);
		}
		Sequra.onLoad(
			function(){
				Sequra.refreshComponents();
			}
		);
	}
}
</script>
<style>
.sequra-educational-popup {
	white-space: nowrap;
	color: #009C5C;
	cursor: pointer;
}
.sequra-promotion-widget[data-type="legacy"] {
	max-width: 320px;
}
</style>
