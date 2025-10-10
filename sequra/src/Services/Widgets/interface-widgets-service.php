<?php
/**
 * Widgets Service Interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Widgets;

/**
 * Widgets Service Interface
 * 
 * @phpstan-type WidgetDataArray array{
 *     product: string,
 *     dest: string,
 *     theme: string,
 *     reverse: string,
 *     campaign: string,
 *     priceSel: string,
 *     altPriceSel: string,
 *     altTriggerSelector: string,
 *     minAmount: int,
 *     maxAmount: int,
 *     miniWidgetMessage: string,
 *     miniWidgetBelowLimitMessage: string
 * }
 * 
 * @phpstan-type WidgetConfigParamsDataArray array{
 *     assetKey: string,
 *     merchantId: string,
 *     products: string[],
 *     scriptUri: string,
 *     locale: string,
 *     currency: string,
 *     decimalSeparator: string,
 *     thousandSeparator: string,
 *     isProductListingEnabled: bool,
 *     isProductEnabled: bool,
 *     widgetConfig: string
 * }
 */
interface Interface_Widgets_Service {

	/**
	 * Get available widget for cart page
	 * 
	 * @return WidgetDataArray|null The available widget, or null if cannot be determined
	 */
	public function get_widget_for_cart_page(): ?array;

	/**
	 * Get available widget for product listing page
	 * 
	 * @return WidgetDataArray|null The available widget, or null if cannot be determined
	 */
	public function get_widget_for_product_listing_page(): ?array;

	/**
	 * Get available widgets for product page
	 * 
	 * @return WidgetDataArray[]|null The available widgets, or null if cannot be determined
	 */
	public function get_widgets_for_product_page( string $product_id ): ?array;

	/**
	 * Get widget configuration parameters required in the frontend
	 * 
	 * @return WidgetConfigParamsDataArray|null The configuration parameters, or null if cannot be determined
	 */
	public function get_widget_config_params(): ?array;
}
