<?php
/**
 * Provides methods to evaluate the current time and date
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Time;

/**
 * Provides methods to evaluate the current time and date
 */
interface Interface_Time_Checker_Service {

	/**
	 * Check if the current hour value is in the range of the given hours
	 * 
	 * @param int $from The start hour in 24-hour format (0-23).
	 * @param int $to The end hour in 24-hour format (0-23).
	 */
	public function is_current_hour_in_range( $from, $to ): bool;
}
