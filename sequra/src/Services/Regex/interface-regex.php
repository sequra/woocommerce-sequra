<?php
/**
 * RegEx service interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Regex;

/**
 * Define methods to get regular expressions to validate data
 */
interface Interface_Regex {

	/**
	 * Get regular expression to validate an IPv4 or IPv6 address
	 */
	public function ip( bool $include_slashes = true ): string;

	/**
	 * Get regular expression to validate a date or duration following ISO 8601
	 */
	public function date_or_duration( bool $include_slashes = true ): string;
}
