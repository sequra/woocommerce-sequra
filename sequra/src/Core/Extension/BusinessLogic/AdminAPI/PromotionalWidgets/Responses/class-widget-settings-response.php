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
			$location_config  = $this->widgetSettings->get_location_config();
			$custom_locations = array();
			if ( $location_config ) {
				foreach ( $location_config->get_custom_locations() as $loc ) {
					$custom_locations[] = $loc->to_array();
				}
			}

			$data['selForPrice']           = $location_config ? $location_config->get_sel_for_price() : null;
			$data['selForAltPrice']        = $location_config ? $location_config->get_sel_for_alt_price() : null;
			$data['selForAltPriceTrigger'] = $location_config ? $location_config->get_sel_for_alt_price_trigger() : null;
			$data['selForDefaultLocation'] = $location_config ? $location_config->get_sel_for_default_location() : null;
			$data['customLocations']       = $custom_locations;
		}
        // phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		return $data;
	}
}
