<?php
/**
 * Wrapper to ease the read and write of configuration values.
 * Delegate to the ConfigurationManager instance to access the data in the database.
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Services\Core;

use SeQura\Core\BusinessLogic\Domain\Integration\SellingCountries\SellingCountriesServiceInterface;

/**
 * Wrapper to ease the read and write of configuration values.
 */
class Selling_Countries_Service implements SellingCountriesServiceInterface {

	/**
	 * Return all configured selling country ISO2 codes of the shop system.
	 *
	 * @return array
	 */
	public function getSellingCountries(): array {
		if ( ! class_exists( 'WC_Countries' ) ) {
			return array();
		}
		$wc_countries = new \WC_Countries();
		return array_keys( $wc_countries->get_allowed_countries() );
	}
}
