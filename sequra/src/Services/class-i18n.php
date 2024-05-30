<?php
/**
 * Implements the I18n service.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services;

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
}
