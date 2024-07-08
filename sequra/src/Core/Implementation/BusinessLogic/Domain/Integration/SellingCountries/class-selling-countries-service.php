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
	 * Return all configured selling country ISO2 codes of the shop system.
	 *
	 * @return array<int, int|string>
	 */
	public function getSellingCountries(): array {
		if ( ! class_exists( 'WC_Countries' ) ) {
			return array();
		}
		$wc_countries = new \WC_Countries();
		return array_keys( $wc_countries->get_allowed_countries() );
	}
}
