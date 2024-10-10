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
			$locale = $this->get_locale( '_' );
		}
		return strtolower( explode( '_', $locale )[0] );
	}

	/**
	 * Get locale.
	 */
	public function get_locale( string $separator = '-' ): string {
		/**
		 * Filters the current language using WPML.
		 *
		 * @since 3.0.0
		 */
		$locale = apply_filters( 'wpml_current_language', null );

		if ( empty( $locale ) && function_exists( 'pll_current_language' ) ) {
			// Get the language using Polylang function.
			$locale = pll_current_language( 'slug' );
		}
		if ( empty( $locale ) && function_exists( 'qtrans_getLanguage' ) ) {
			// Get the language using qTranslate function.
			$locale = qtrans_getLanguage();
		}
		if ( empty( $locale ) ) {
			// Falling back to the default locale.
			$locale = get_user_locale();
		}

		if ( '_' !== $separator ) {
			$locale = str_replace( '_', $separator, $locale );
		}
		return $locale;
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
			$customer = WC()->customer;
			$country  = $customer ? $customer->get_shipping_country() : null;
			if ( empty( $country ) ) {
				$country = $customer ? $customer->get_billing_country() : null;
			}
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
