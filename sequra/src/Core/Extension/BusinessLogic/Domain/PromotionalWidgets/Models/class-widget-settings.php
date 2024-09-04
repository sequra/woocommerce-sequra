<?php
/**
 * Extension of the WidgetSettings class.
 *
 * @package SeQura\Core\BusinessLogic\Domain\PromotionalWidgets\Models
 */

namespace SeQura\WC\Core\Extension\BusinessLogic\Domain\PromotionalWidgets\Models;

use SeQura\Core\BusinessLogic\Domain\PromotionalWidgets\Models\WidgetLabels;
use SeQura\Core\BusinessLogic\Domain\PromotionalWidgets\Models\WidgetSettings;

/**
 * Extension of the WidgetSettings class.
 */
class Widget_Settings extends WidgetSettings {

	/**
	 * Widget Location Config
	 *
	 * @var ?Widget_Location_Config
	 */
	protected $location_config;

	/**
	 * Mini Widget Config
	 *
	 * @var ?Mini_Widget_Config
	 */
	protected $cart_mini_widget_config;

	/**
	 * Mini Widget Config
	 *
	 * @var ?Mini_Widget_Config
	 */
	protected $listing_mini_widget_config;

	/**
	 * Constructor.
	 */
	public function __construct(
		bool $enabled,
		string $assets_key = '',
		bool $display_on_product_page = false,
		bool $show_installments_in_product_listing = false,
		bool $show_installments_in_cart_page = false,
		string $mini_widget_selector = '',
		?string $widget_config = null,
		?WidgetLabels $widget_labels = null,
		?Widget_Location_Config $location_config = null,
		?Mini_Widget_Config $cart_mini_widget_config = null,
		?Mini_Widget_Config $listing_mini_widget_config = null
	) {
		parent::__construct(
			$enabled,
			$assets_key,
			$display_on_product_page,
			$show_installments_in_product_listing,
			$show_installments_in_cart_page,
			$mini_widget_selector,
			$widget_config,
			$widget_labels
		);
		$this->location_config            = $location_config;
		$this->cart_mini_widget_config    = $cart_mini_widget_config;
		$this->listing_mini_widget_config = $listing_mini_widget_config;
	}

	/**
	 * Create a new Widget_Settings instance from a WidgetSettings instance.
	 */
	public static function from_parent(
		WidgetSettings $instance,
		?Widget_Location_Config $location_config = null,
		?Mini_Widget_Config $cart_mini_widget_config = null,
		?Mini_Widget_Config $listing_mini_widget_config = null
	): Widget_Settings {
		return new self(
			$instance->isEnabled(),
			$instance->getAssetsKey(),
			$instance->isDisplayOnProductPage(),
			$instance->isShowInstallmentsInProductListing(),
			$instance->isShowInstallmentsInCartPage(),
			$instance->getMiniWidgetSelector(),
			$instance->getWidgetConfig(),
			$instance->getWidgetLabels(),
			$location_config,
			$cart_mini_widget_config,
			$listing_mini_widget_config
		);
	}

	/**
	 * Getter
	 */
	public function get_location_config(): ?Widget_Location_Config {
		return $this->location_config;
	}

	/**
	 * Setter
	 */
	public function set_location_config( ?Widget_Location_Config $location_config ): void {
		$this->location_config = $location_config;
	}

	/**
	 * Getter
	 */
	public function get_cart_mini_widget_config(): ?Mini_Widget_Config {
		return $this->cart_mini_widget_config;
	}

	/**
	 * Setter
	 */
	public function set_cart_mini_widget_config( ?Mini_Widget_Config $cart_mini_widget_config ): void {
		$this->cart_mini_widget_config = $cart_mini_widget_config;
	}

	/**
	 * Getter
	 */
	public function get_listing_mini_widget_config(): ?Mini_Widget_Config {
		return $this->listing_mini_widget_config;
	}

	/**
	 * Setter
	 */
	public function set_listing_mini_widget_config( ?Mini_Widget_Config $listing_mini_widget_config ): void {
		$this->listing_mini_widget_config = $listing_mini_widget_config;
	}
}
