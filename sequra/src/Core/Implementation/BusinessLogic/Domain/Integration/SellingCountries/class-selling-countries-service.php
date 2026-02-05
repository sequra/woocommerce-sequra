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
	 * @param ?int $page The page number for pagination (optional).
	 * @param ?int $limit The number of items per page for pagination (optional).
	 * @param ?string $search A search term to filter countries by name or code (optional).
	 *
	 * @return string[]
	 */
	public function getSellingCountries( ?int $page = null, ?int $limit = null, ?string $search = null ): array {
		if ( ! class_exists( 'WC_Countries' ) ) {
			return array();
		}
		$wc_countries      = new \WC_Countries();
		$allowed_countries = $wc_countries->get_allowed_countries();
		
		// Apply search filter if provided.
		if ( null !== $search && '' !== trim( $search ) ) {
			$search_term       = strtolower( trim( $search ) );
			$allowed_countries = array_filter(
				$allowed_countries,
				function ( $country_name, $country_code ) use ( $search_term ) {
					return false !== stripos( $country_code, $search_term ) || 
							false !== stripos( $country_name, $search_term );
				},
				ARRAY_FILTER_USE_BOTH
			);
		}
		
		$country_codes = array_keys( $allowed_countries );
		
		// Apply pagination if both page and limit are provided.
		if ( null !== $page && null !== $limit ) {
			$offset        = ( $page - 1 ) * $limit;
			$country_codes = array_slice( $country_codes, $offset, $limit );
		}
		
		return $country_codes;
	}
}
