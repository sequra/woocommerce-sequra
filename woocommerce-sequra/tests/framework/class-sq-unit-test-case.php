<?php
/**
 * Base test case for all WooCommerce tests.
 *
 * @package WooCommerce\Tests
 */

/**
 * WC Unit Test Case.
 *
 * Provides WooCommerce-specific setup/tear down/assert methods, custom factories,
 * and helper functions.
 *
 * @since 2.2
 */
class SQ_Unit_Test_Case extends WC_Unit_Test_Case {

	/**
	 * Setup test case.
	 *
	 * @since 2.2
	 */
	public function setUp() {

		parent::setUp();
		// Add API credentials.
		update_option(
			'woocommerce_sequra_settings ',
			[
				'merchantref'        => 'wcsq_tests',
				'user'               => 'wcsq_tests',
				'password'           => 'dHvxbkpZcNnX6uk36XOf4P51lnkSE4',
				'assets_secret'      => 'i_S5YcXdxZ',
				'enable_for_virtual' => 'no',
				'env'                => 1,
				'debug'              => 'yes',
			]
		);
		update_option(
			'woocommerce_sequra_pp_settings ',
			[
				'enabled'       => 'yes',
				'title'         => __( 'Fraccionar pago', 'wc_sequra' ),
				'widget_theme'  => 'white',
				'price_css_sel' => '.summary .price>.amount,.summary .price ins .amount',
				'dest_css_sel'  => '.summary .price',
			]
		);
		update_option(
			'woocommerce_sequra_i_settings ',
			[
				'enabled'       => 'yes',
				'title'         => __( 'Paga en 7 días', 'wc_sequra' ),
				'max_amount'    => 400,
				'widget_theme'  => 'white',
				'price_css_sel' => '.summary .price>.amount,.summary .price ins .amount',
				'dest_css_sel'  => '.summary .price',
			]
		);
	}
}
