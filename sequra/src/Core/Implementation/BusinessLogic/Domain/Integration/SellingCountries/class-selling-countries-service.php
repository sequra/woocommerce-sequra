<?php
/**
 * Selling Countries Service
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Services\Core;

use SeQura\Core\BusinessLogic\Domain\Integration\SellingCountries\SellingCountriesServiceInterface;

/**
 * Selling Countries Service
 */
class Selling_Countries_Service implements SellingCountriesServiceInterface {

	/**
	 * Return configured selling country ISO2 codes of the shop system.
	 *
	 * @return string[]
	 */
	public function getSellingCountries(): array {
		if ( ! class_exists( 'WC_Countries' ) ) {
			return array();
		}
		$wc_countries      = new \WC_Countries();
		$allowed_countries = $wc_countries->get_allowed_countries();
		return array_keys( $allowed_countries );
	}
}
