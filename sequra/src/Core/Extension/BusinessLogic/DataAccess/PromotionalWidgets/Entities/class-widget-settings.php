<?php
/**
 * Extension of the WidgetSettings class.
 * 
 * @package SeQura\Core\BusinessLogic\DataAccess\PromotionalWidgets\Entities
 */

namespace SeQura\WC\Core\Extension\BusinessLogic\DataAccess\PromotionalWidgets\Entities;

use SeQura\Core\BusinessLogic\DataAccess\PromotionalWidgets\Entities\WidgetSettings;
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
	 * @param array $data Raw array data with keys for class fields. @see self::$fields for field names.
	 */
	public function inflate( array $data ) {
		parent::inflate( $data );
		$data_widget_settings = $data['widgetSettings'] ?? array();
        // phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$this->widgetSettings = Domain_Widget_Settings::from_parent( 
			$this->widgetSettings,
			Widget_Location_Config::from_array( (array) self::getArrayValue( $data_widget_settings, 'widgetLocationConfiguration', array() ) )
		);
        // phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	/**
	 * Transforms entity to its array format representation.
	 *
	 * @return array Entity in array format.
	 */
	public function toArray(): array {
		$data = parent::toArray();

        // phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		if ( $this->widgetSettings instanceof Domain_Widget_Settings ) {
			$location_config                                       = $this->widgetSettings->get_location_config();
			$data['widgetSettings']['widgetLocationConfiguration'] = $location_config ? $location_config->to_array() : array();
		}
        // phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		return $data;
	}
}
