<?php
/**
 * Implements the I18n service.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\I18n;

/**
 * Implements the I18n service.
 */
class I18n implements Interface_I18n {

	/**
	 * Get the language code.
	 * 
	 * @param string|null $locale The locale. By default, it will use the current locale.
	 */
	public function get_lang( $locale = null ): string {
		if ( null === $locale ) {
			$locale = get_user_locale();
		}
		return strtolower( explode( '_', $locale )[0] );
	}

	/**
	 * TODO: Make Unit Test for this
	 * Get the current country. ISO-3166-1 alpha-2 code.
	 * Looks for the country in the following order:
	 * 1. The country set in WooCommerce checkout billing address.
	 * 2. The country set in user profile.
	 * 3. The country by current locale.
	 */
	public function get_current_country(): string {
		$country = null;
		if ( function_exists( 'WC' ) ) {
			$country = WC()->customer->get_billing_country();
		} 
		
		if ( empty( $country ) && is_user_logged_in() ) {
			$country = get_user_meta( get_current_user_id(), 'billing_country', true );
		} 
		
		if ( empty( $country ) ) {
			$country = $this->get_lang();
		}
		return strtoupper( $country );
	}
}
