<?php
/**
 * Extension of the GeneralSettingsController.
 * 
 * @package SeQura\WC\Core\Extension\BusinessLogic\AdminAPI\GeneralSettings
 */

namespace SeQura\WC\Core\Extension\BusinessLogic\AdminAPI\GeneralSettings;

use SeQura\Core\BusinessLogic\AdminAPI\GeneralSettings\GeneralSettingsController;

use SeQura\Core\BusinessLogic\AdminAPI\GeneralSettings\Responses\GeneralSettingsResponse;
use SeQura\WC\Core\Extension\BusinessLogic\AdminAPI\GeneralSettings\Responses\General_Settings_Response;

/**
 * Extension of the GeneralSettingsController.
 */
class General_Settings_Controller extends GeneralSettingsController {

	/**
	 * Gets active general settings.
	 *
	 * @return GeneralSettingsResponse
	 */
	public function getGeneralSettings(): GeneralSettingsResponse {
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		return new General_Settings_Response( $this->generalSettingsService->getGeneralSettings() );
	}
}
