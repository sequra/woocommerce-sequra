<?php
/**
 * Test-only function overrides for the affiliate service namespace.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Services\Affiliate;

if ( ! function_exists( __NAMESPACE__ . '\class_exists' ) ) {
	/**
	 * Test override of the global class_exists() within the affiliate service namespace, so the
	 * standalone-plugin coexistence guard (standalone_plugin_active()) can be exercised without
	 * loading the real standalone plugin. Inert unless a test opts in via the global flag, so every
	 * other test keeps seeing the real class_exists().
	 *
	 * @param string $class_name Class name being checked.
	 * @return bool
	 */
	function class_exists( $class_name ) {
		if ( 'SeQura_Affiliate_Marketing' === $class_name && ! empty( $GLOBALS['sq_test_standalone_affiliate_active'] ) ) {
			return true;
		}
		return \class_exists( $class_name );
	}
}
