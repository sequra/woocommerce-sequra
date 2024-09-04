<?php
/**
 * Extension of the WidgetSettingsRequest class.
 * 
 * @package SeQura\WC\Core\Extension\BusinessLogic\AdminAPI\PromotionalWidgets\Requests
 */

namespace SeQura\WC\Core\Extension\BusinessLogic\AdminAPI\PromotionalWidgets\Requests;

use SeQura\Core\BusinessLogic\AdminAPI\PromotionalWidgets\Requests\WidgetSettingsRequest;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\PromotionalWidgets\Models\Mini_Widget_Config;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\PromotionalWidgets\Models\Widget_Location;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\PromotionalWidgets\Models\Widget_Location_Config;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\PromotionalWidgets\Models\Widget_Settings;

/**
 * Extension of the WidgetSettingsRequest class.
 */
class Widget_Settings_Request extends WidgetSettingsRequest {

	/**
	 * Selector for the price element.
	 *
	 * @var string|null
	 */
	protected $sel_for_price;

	/**
	 * Selector for the alternative price element.
	 *
	 * @var string|null
	 */
	protected $sel_for_alt_price;

	/**
	 * Selector for the alternative price trigger.
	 *
	 * @var string|null
	 */
	protected $sel_for_alt_price_trigger;

	/**
	 * Selector for the default location.
	 *
	 * @var string|null
	 */
	protected $sel_for_default_location;

	/**
	 * Custom locations.
	 *
	 * @var Widget_Location[]
	 */
	protected $custom_locations;

	/**
	 * Cart mini widget configuration.
	 *
	 * @var ?Mini_Widget_Config
	 */
	protected $cart_mini_widget_config;

	/**
	 * Product listing mini widget configuration.
	 *
	 * @var ?Mini_Widget_Config
	 */
	protected $listing_mini_widget_config;

	/**
	 * Constructor.
	 * 
	 * @param bool $enabled Is enabled.
	 * @param string|null $assets_key Assets key.
	 * @param bool $display_on_product_page Display on product page.
	 * @param bool $show_installments_in_product_listing Show installments in product listing.
	 * @param bool $show_installments_in_cart_page Show installments in cart page.
	 * @param string $mini_widget_selector Mini widget selector.
	 * @param string $widget_configuration Widget configuration.
	 * @param array<mixed> $messages Messages.
	 * @param array<mixed> $messages_below_limit Messages below limit.
	 * @param string|null $sel_for_price Selector for price.
	 * @param string|null $sel_for_alt_price Selector for alternative price.
	 * @param string|null $sel_for_alt_price_trigger Selector for alternative price trigger.
	 * @param string|null $sel_for_default_location Selector for default location.
	 * @param array<array<string, string>> $custom_locations Custom locations.
	 * @param array<array<string, string>> $cart_mini_widgets Cart mini widget configuration.
	 * @param string|null $sel_for_listing_price Selector for listing price.
	 * @param string|null $sel_for_listing_location Selector for listing location.
	 */
	public function __construct(
		bool $enabled,
		?string $assets_key,
		bool $display_on_product_page,
		bool $show_installments_in_product_listing,
		bool $show_installments_in_cart_page,
		string $mini_widget_selector,
		string $widget_configuration,
		array $messages = array(),
		array $messages_below_limit = array(),
		?string $sel_for_price = null,
		?string $sel_for_alt_price = null,
		?string $sel_for_alt_price_trigger = null,
		?string $sel_for_default_location = null,
		array $custom_locations = array(),
		?string $sel_for_cart_price = null,
		?string $sel_for_cart_default_location = null,
		array $cart_mini_widgets = array(),
		?string $sel_for_listing_price = null,
		?string $sel_for_listing_location = null,
		array $listing_mini_widgets = array()
	) {
		parent::__construct(
			$enabled,
			$assets_key,
			$display_on_product_page,
			$show_installments_in_product_listing,
			$show_installments_in_cart_page,
			$mini_widget_selector,
			$widget_configuration,
			$messages,
			$messages_below_limit
		);

		$this->sel_for_price             = $sel_for_price;
		$this->sel_for_alt_price         = $sel_for_alt_price;
		$this->sel_for_alt_price_trigger = $sel_for_alt_price_trigger;
		$this->sel_for_default_location  = $sel_for_default_location;
		
		$this->custom_locations = array();
		foreach ( $custom_locations as $location ) {
			$instance = Widget_Location::from_array( $location );
			if ( $instance ) {
				$this->custom_locations[] = $instance;
			}
		}

		$this->cart_mini_widget_config = Mini_Widget_Config::from_array(
			array(
				Mini_Widget_Config::SEL_FOR_PRICE => $sel_for_cart_price,
				Mini_Widget_Config::SEL_FOR_DEFAULT_LOCATION => $sel_for_cart_default_location,
				Mini_Widget_Config::MINI_WIDGETS  => $cart_mini_widgets,
			)
		);

		$this->listing_mini_widget_config = Mini_Widget_Config::from_array(
			array(
				Mini_Widget_Config::SEL_FOR_PRICE => $sel_for_listing_price,
				Mini_Widget_Config::SEL_FOR_DEFAULT_LOCATION => $sel_for_listing_location,
				Mini_Widget_Config::MINI_WIDGETS  => $listing_mini_widgets,
			)
		);
	}

	/**
	 * Transforms the request to a WidgetConfiguration object.
	 *
	 * @return Widget_Settings
	 */
	public function transformToDomainModel(): object {
		$location_config = null;

		if ( null !== $this->sel_for_price 
		&& null !== $this->sel_for_alt_price 
		&& null !== $this->sel_for_alt_price_trigger 
		&& null !== $this->sel_for_default_location 
		&& null !== $this->custom_locations
		) {
			$location_config = new Widget_Location_Config(
				$this->sel_for_price,
				$this->sel_for_alt_price,
				$this->sel_for_alt_price_trigger,
				$this->sel_for_default_location,
				$this->custom_locations
			);
		}
		
		return Widget_Settings::from_parent(
			parent::transformToDomainModel(),
			$location_config,
			$this->cart_mini_widget_config,
			$this->listing_mini_widget_config
		);
	}
}
