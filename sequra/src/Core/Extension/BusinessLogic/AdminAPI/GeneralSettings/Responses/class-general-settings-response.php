<?php
/**
 * Extends the GeneralSettingsResponse class.
 *
 * @package SeQura\WC
 */

namespace Sequra\WC\Core\Extension\BusinessLogic\AdminAPI\GeneralSettings\Responses;

use SeQura\Core\BusinessLogic\AdminAPI\GeneralSettings\Responses\GeneralSettingsResponse;
use SeQura\Core\BusinessLogic\Domain\GeneralSettings\Models\GeneralSettings;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\GeneralSettings\Models\General_Settings;

/**
 * Extends the GeneralSettingsResponse class.
 */
class General_Settings_Response extends GeneralSettingsResponse {

	/**
	 * General settings
	 * 
	 * @var ?GeneralSettings
	 */
	protected $general_settings;

	/**
	 * Constructor
	 */
	public function __construct( ?GeneralSettings $general_settings ) {
		parent::__construct( $general_settings );
		$this->general_settings = $general_settings;
	}

	/**
	 * To array
	 * 
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		$data = parent::toArray();
		if ( $this->general_settings instanceof General_Settings ) { 
			$data['enabledForServices']            = $this->general_settings->is_enabled_for_services();
			$data['allowFirstServicePaymentDelay'] = $this->general_settings->is_allow_first_service_payment_delay();
			$data['allowServiceRegItems']          = $this->general_settings->is_allow_service_reg_items();
			$data['defaultServicesEndDate']        = $this->general_settings->get_default_services_end_date();
		}
		return $data;
	}
}
