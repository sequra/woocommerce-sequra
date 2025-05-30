<?php
/**
 * Task class
 * 
 * @package SeQura/Helper
 */

namespace SeQura\Helper\Task;

// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/**
 * Task class
 */
class Configure_V2_Task extends Task {

	/**
	 * Set configuration for dummy merchant
	 */
	private function set_dummy_config(): void {
		update_option(
			'woocommerce_sequra_settings',
			array(
				'enabled'                           => 'yes',
				'title'                             => 'Flexible payment with seQura',
				'sign-up-info'                      => '',
				'merchantref'                       => 'dummy_automated_tests',
				'user'                              => 'dummy_automated_tests',
				'password'                          => getenv( 'DUMMY_PASSWORD' ),
				'assets_secret'                     => getenv( 'DUMMY_ASSETS_KEY' ),
				'enable_for_virtual'                => 'no',
				'default_service_end_date'          => 'P1Y',
				'allow_payment_delay'               => 'no',
				'allow_registration_items'          => 'no',
				'env'                               => '1',
				'test_ips'                          => '212.80.211.33',
				'debug'                             => 'no',
				'active_methods_info'               => '',
				'communication_fields'              => '',
				'price_css_sel'                     => '.summary .price>.amount,.summary .price ins .amount',
				'enabled_in_product_i1'             => 'yes',
				'dest_css_sel_i1'                   => '.summary .price>.amount,.summary .price ins .amount',
				'widget_theme_i1'                   => 'L',
				'enabled_in_product_sp1_permanente' => 'yes',
				'dest_css_sel_sp1_permanente'       => '.summary .price>.amount,.summary .price ins .amount',
				'widget_theme_sp1_permanente'       => '{"alignment":"left"}',
				'enabled_in_product_pp3'            => 'yes',
				'dest_css_sel_pp3'                  => '.summary .price>.amount,.summary .price ins .amount',
				'widget_theme_pp3'                  => 'L',
			) 
		);
	}

	/**
	 * Execute the task
	 * 
	 * @throws \Exception If the task fails
	 */
	public function execute( array $args = array() ): void {

		$ref = $args['merchant_ref'] ?? 'dummy_automated_tests';

		if ( 'dummy_automated_tests' !== $ref ) {
			throw new \Exception( 'Invalid merchant ref', 400 );
		}

		$this->set_dummy_config();
	}
}
