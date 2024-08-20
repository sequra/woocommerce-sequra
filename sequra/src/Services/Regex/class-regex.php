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
class Regex implements Interface_Regex {

	/**
	 * Get regular expression to validate an IPv4 or IPv6 address
	 */
	public function ip( bool $include_slashes = true ): string {
		return $this->maybe_strip_slashes(
			'/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/',
			$include_slashes
		);
	}

	/**
	 * Get regular expression to validate a date or duration following ISO 8601
	 */
	public function date_or_duration( bool $include_slashes = true ): string {
		return $this->maybe_strip_slashes(
			'/^((?:\d{4}-(?:0[1-9]|1[0-2])-(?:0[1-9]|1\d|2[0-8]))|(?:\d{4}-(?:0[13-9]|1[0-2])-(?:29|30))|(?:\d{4}-(?:0[13578]|1[012])-(?:31))|(?:\d{2}(?:[02468][048]|[13579][26])-(?:02)-29)|(P(?:\d+Y)?(?:\d+M)?(?:\d+W)?(?:\d+D)?(?:T(?:\d+H)?(?:\d+M)?(?:\d+S)?)?))$/',
			$include_slashes
		);
	}

	/**
	 * Maybe strip slashes from a regex
	 */
	private function maybe_strip_slashes( string $regex, bool $include_slashes ): string {
		return $include_slashes ? $regex : substr( $regex, 1, -1 );
	}
}
