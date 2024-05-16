<?php
/**
 * I18n interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services;

/**
 * I18n interface
 */
interface Interface_I18n {

	/**
	 * Get the language code.
	 * 
	 * @param string|null $locale The locale. By default, it will use the current locale.
	 */
	public function get_lang( $locale = null ): string;
}
