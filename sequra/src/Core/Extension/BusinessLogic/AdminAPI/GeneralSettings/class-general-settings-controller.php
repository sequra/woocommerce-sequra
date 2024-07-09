<?php
/**
 * Extension of the GeneralSettingsController.
 * 
 * @package SeQura\WC\Core\Extension\BusinessLogic\AdminAPI\GeneralSettings
 */

namespace SeQura\WC\Core\Extension\BusinessLogic\AdminAPI\GeneralSettings;

use SeQura\Core\BusinessLogic\AdminAPI\GeneralSettings\GeneralSettingsController;

use SeQura\Core\BusinessLogic\AdminAPI\GeneralSettings\Responses\GeneralSettingsResponse;
use SeQura\Core\BusinessLogic\Domain\GeneralSettings\Services\CategoryService;
use SeQura\Core\BusinessLogic\Domain\GeneralSettings\Services\GeneralSettingsService;
use Sequra\WC\Core\Extension\BusinessLogic\AdminAPI\GeneralSettings\Responses\General_Settings_Response;

/**
 * Extension of the GeneralSettingsController.
 */
class General_Settings_Controller extends GeneralSettingsController {

	/**
	 * General settings service.
	 *
	 * @var GeneralSettingsService
	 */
	private $general_settings_service;

	/**
	 * Constructor
	 */
	public function __construct(
		GeneralSettingsService $general_settings_service,
		CategoryService $category_service
	) {
		parent::__construct( $general_settings_service, $category_service );
		$this->general_settings_service = $general_settings_service;
	}

	/**
	 * Gets active general settings.
	 *
	 * @return GeneralSettingsResponse
	 */
	public function getGeneralSettings(): GeneralSettingsResponse {
		return new General_Settings_Response( $this->general_settings_service->getGeneralSettings() );
	}
}
