<?php
/**
 * Extension of the WidgetSettings class.
 * 
 * @package SeQura\Core\BusinessLogic\DataAccess\PromotionalWidgets\Entities
 */

namespace SeQura\WC\Core\Extension\BusinessLogic\DataAccess\PromotionalWidgets\Entities;

use SeQura\Core\BusinessLogic\DataAccess\PromotionalWidgets\Entities\WidgetSettings;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\PromotionalWidgets\Models\Mini_Widget_Config;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\PromotionalWidgets\Models\Widget_Location_Config;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\PromotionalWidgets\Models\Widget_Settings as Domain_Widget_Settings;

/**
 * Extension of the WidgetSettings class.
 */
class Widget_Settings extends WidgetSettings {

	/**
	 * Returns full class name.
	 *
	 * @return string Fully qualified class name.
	 */
	public static function getClassName() {
		return __CLASS__;
	}

	/**
	 * Sets raw array data to this entity instance properties.
	 *
	 * @param array<string, mixed> $data Raw array data with keys for class fields. @see self::$fields for field names.
	 * @return void
	 */
	public function inflate( array $data ) {
		parent::inflate( $data );
		$data_widget_settings           = isset( $data['widgetSettings'] ) ? (array) $data['widgetSettings'] : array();
		$raw_widget_location_config     = self::getArrayValue( $data_widget_settings, 'widgetLocationConfiguration', array() );
		$raw_cart_mini_widget_config    = self::getArrayValue( $data_widget_settings, 'cartMiniWidgetConfiguration', array() );
		$raw_listing_mini_widget_config = self::getArrayValue( $data_widget_settings, 'listingMiniWidgetConfiguration', array() );

		$widget_location_config = null;
		if ( is_array( $raw_widget_location_config ) ) {
			$widget_location_config = Widget_Location_Config::from_array( $raw_widget_location_config );
		}

		$cart_mini_widget_config = null;
		if ( is_array( $raw_cart_mini_widget_config ) ) {
			$cart_mini_widget_config = Mini_Widget_Config::from_array( $raw_cart_mini_widget_config );
		}

		$listing_mini_widget_config = null;
		if ( is_array( $raw_listing_mini_widget_config ) ) {
			$listing_mini_widget_config = Mini_Widget_Config::from_array( $raw_listing_mini_widget_config );
		}

        // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$this->widgetSettings = Domain_Widget_Settings::from_parent( $this->widgetSettings, $widget_location_config, $cart_mini_widget_config, $listing_mini_widget_config );
	}

	/**
	 * Transforms entity to its array format representation.
	 *
	 * @return array<string, mixed> Entity in array format.
	 */
	public function toArray(): array {
		$data = parent::toArray();

        // phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		if ( $this->widgetSettings instanceof Domain_Widget_Settings ) {
			$location_config                                       = $this->widgetSettings->get_location_config();
			$data['widgetSettings']['widgetLocationConfiguration'] = $location_config ? $location_config->to_array() : array();

			$cart_mini_widget_config                               = $this->widgetSettings->get_cart_mini_widget_config();
			$data['widgetSettings']['cartMiniWidgetConfiguration'] = $cart_mini_widget_config ? $cart_mini_widget_config->to_array() : array();

			$listing_mini_widget_config                               = $this->widgetSettings->get_listing_mini_widget_config();
			$data['widgetSettings']['listingMiniWidgetConfiguration'] = $listing_mini_widget_config ? $listing_mini_widget_config->to_array() : array();
		}
        // phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		return $data;
	}
}
