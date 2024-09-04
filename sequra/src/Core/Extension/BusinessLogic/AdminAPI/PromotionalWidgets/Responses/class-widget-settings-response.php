<?php
/**
 * Extends Widget Settings Response
 *
 * @package SeQura\WC\Core\Extension\BusinessLogic\AdminAPI\PromotionalWidgets\Responses
 */

namespace SeQura\WC\Core\Extension\BusinessLogic\AdminAPI\PromotionalWidgets\Responses;

use SeQura\Core\BusinessLogic\AdminAPI\PromotionalWidgets\Responses\WidgetSettingsResponse;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\PromotionalWidgets\Models\Widget_Settings;

/**
 * Extends Widget Settings Response
 */
class Widget_Settings_Response extends WidgetSettingsResponse {
	
	/**
	 * To array
	 * 
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		$data = parent::toArray();
        // phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		if ( $this->widgetSettings instanceof Widget_Settings ) { 
			$location_config            = $this->widgetSettings->get_location_config();
			$cart_mini_widget_config    = $this->widgetSettings->get_cart_mini_widget_config();
			$listing_mini_widget_config = $this->widgetSettings->get_listing_mini_widget_config();
			
			$custom_locations = array();
			if ( $location_config ) {
				foreach ( $location_config->get_custom_locations() as $loc ) {
					$custom_locations[] = $loc->to_array();
				}
			}

			$cart_mini_widgets = array();
			if ( $cart_mini_widget_config ) {
				foreach ( $cart_mini_widget_config->get_mini_widgets() as $widget ) {
					$cart_mini_widgets[] = $widget->to_array();
				}
			}

			$listing_mini_widgets = array();
			if ( $listing_mini_widget_config ) {
				foreach ( $listing_mini_widget_config->get_mini_widgets() as $widget ) {
					$listing_mini_widgets[] = $widget->to_array();
				}
			}

			$data['selForPrice']           = $location_config ? $location_config->get_sel_for_price() : null;
			$data['selForAltPrice']        = $location_config ? $location_config->get_sel_for_alt_price() : null;
			$data['selForAltPriceTrigger'] = $location_config ? $location_config->get_sel_for_alt_price_trigger() : null;
			$data['selForDefaultLocation'] = $location_config ? $location_config->get_sel_for_default_location() : null;
			$data['customLocations']       = $custom_locations;
			$data['selForCartPrice']       = $cart_mini_widget_config ? $cart_mini_widget_config->get_sel_for_price() : null;
			$data['selForCartLocation']    = $cart_mini_widget_config ? $cart_mini_widget_config->get_sel_for_default_location() : null;
			$data['cartMiniWidgets']       = $cart_mini_widgets ? $cart_mini_widgets : null;
			$data['selForListingPrice']    = $listing_mini_widget_config ? $listing_mini_widget_config->get_sel_for_price() : null;
			$data['selForListingLocation'] = $listing_mini_widget_config ? $listing_mini_widget_config->get_sel_for_default_location() : null;
			$data['listingMiniWidgets']    = $listing_mini_widgets ? $listing_mini_widgets : null;
		}
        // phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		return $data;
	}
}
