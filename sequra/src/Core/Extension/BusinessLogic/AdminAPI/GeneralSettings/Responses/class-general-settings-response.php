<?php
/**
 * Extends the GeneralSettingsResponse class.
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Extension\BusinessLogic\AdminAPI\GeneralSettings\Responses;

use SeQura\Core\BusinessLogic\AdminAPI\GeneralSettings\Responses\GeneralSettingsResponse;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\GeneralSettings\Models\General_Settings;

/**
 * Extends the GeneralSettingsResponse class.
 */
class General_Settings_Response extends GeneralSettingsResponse {

	/**
	 * To array
	 * 
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		$data = parent::toArray();
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		if ( $this->generalSettings instanceof General_Settings ) { 
			$data['enabledForServices']            = $this->generalSettings->is_enabled_for_services();
			$data['allowFirstServicePaymentDelay'] = $this->generalSettings->is_allow_first_service_payment_delay();
			$data['allowServiceRegItems']          = $this->generalSettings->is_allow_service_reg_items();
			$data['defaultServicesEndDate']        = $this->generalSettings->get_default_services_end_date();
		}
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		return $data;
	}
}
