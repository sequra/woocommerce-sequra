(function ($) {

	// Remove duplicated objects from sequraConfigParams.widgets.
	const uniqueWidgets = [];
	sequraConfigParams.widgets.forEach(widget => {
		if (!uniqueWidgets.some(w => w.price_src === widget.price_src && w.dest === widget.dest && w.product === widget.product && w.theme === widget.theme && w.reverse === widget.reverse && w.campaign === widget.campaign)) {
			uniqueWidgets.push(widget);
		}
	});
	sequraConfigParams.widgets = uniqueWidgets;


	Sequra.onLoad(function () {
		SequraHelper.widgets = sequraConfigParams.widgets;
		SequraHelper.drawWidgetsOnPage();
		const variationForm = $('.variations_form');
		if (variationForm.length) {
			variationForm.on('show_variation', () => SequraHelper.drawWidgetsOnPage(false));
			variationForm.on('hide_variation', () => SequraHelper.drawWidgetsOnPage());
		}
	});
})(jQuery);