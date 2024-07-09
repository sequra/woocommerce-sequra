<?php
/**
 * Extends the GeneralSettings class.
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Extension\BusinessLogic\DataAccess\GeneralSettings\Entities;

use SeQura\Core\BusinessLogic\DataAccess\GeneralSettings\Entities\GeneralSettings;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\GeneralSettings\Models\General_Settings as Domain_General_Settings;

/**
 * Extends the GeneralSettings class.
 */
class General_Settings extends GeneralSettings {

	/**
	 * Sets raw array data to this entity instance properties.
	 *
	 * @param array<string, mixed> $data Raw array data with keys for class fields. @see self::$fields for field names.
	 */
	public function inflate( array $data ): void {
		parent::inflate( $data );
		$data_general_settings = isset( $data['generalSettings'] ) ? (array) $data['generalSettings'] : array();
        // phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$this->generalSettings = Domain_General_Settings::from_parent( 
			$this->generalSettings,
			boolval( static::getDataValue( $data_general_settings, 'enabledForServices', false ) ),
			boolval( static::getDataValue( $data_general_settings, 'allowFirstServicePaymentDelay', true ) ),
			boolval( static::getDataValue( $data_general_settings, 'allowServiceRegItems', true ) ),
			strval( static::getDataValue( $data_general_settings, 'defaultServicesEndDate', 'P1Y' ) )
		);
        // phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	/**
	 * Returns full class name.
	 *
	 * @return string Fully qualified class name.
	 */
	public static function getClassName() {
		return __CLASS__;
	}

	/**
	 * Transforms entity to its array format representation.
	 *
	 * @return array<string, mixed> Entity in array format.
	 */
	public function toArray(): array {
		$data = parent::toArray();
        // phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		if ( $this->generalSettings instanceof Domain_General_Settings ) { 
			$data['generalSettings']['enabledForServices']            = $this->generalSettings->is_enabled_for_services();
			$data['generalSettings']['allowFirstServicePaymentDelay'] = $this->generalSettings->is_allow_first_service_payment_delay();
			$data['generalSettings']['allowServiceRegItems']          = $this->generalSettings->is_allow_service_reg_items();
			$data['generalSettings']['defaultServicesEndDate']        = $this->generalSettings->get_default_services_end_date();
		}
        // phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		return $data;
	}
}
