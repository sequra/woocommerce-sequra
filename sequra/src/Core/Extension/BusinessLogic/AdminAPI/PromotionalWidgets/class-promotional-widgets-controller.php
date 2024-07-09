<?php
/**
 * Extension of the PromotionalWidgetsController.
 * 
 * @package SeQura\WC\Core\Extension\BusinessLogic\AdminAPI\PromotionalWidgets
 */

namespace SeQura\WC\Core\Extension\BusinessLogic\AdminAPI\PromotionalWidgets;

use Exception;
use SeQura\Core\BusinessLogic\AdminAPI\PromotionalWidgets\PromotionalWidgetsController;
use SeQura\Core\BusinessLogic\AdminAPI\PromotionalWidgets\Responses\WidgetSettingsResponse;
use SeQura\WC\Core\Extension\BusinessLogic\AdminAPI\PromotionalWidgets\Responses\Widget_Settings_Response;

/**
 * Extension of the PromotionalWidgetsController.
 */
class Promotional_Widgets_Controller extends PromotionalWidgetsController {

	/**
	 * Gets active widget settings.
	 *
	 * @return WidgetSettingsResponse
	 *
	 * @throws Exception
	 */
	public function getWidgetSettings(): WidgetSettingsResponse {
        // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		return new Widget_Settings_Response( $this->widgetSettingsService->getWidgetSettings() );
	}
}
