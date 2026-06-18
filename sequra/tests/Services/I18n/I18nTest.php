<?php
/**
 * Tests for the I18n widget-surface country resolution.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Services\I18n;

use SeQura\WC\Services\I18n\I18n;
use WC_Customer;
use WP_UnitTestCase;

class I18nTest extends WP_UnitTestCase {

	/** @var I18n */
	private $i18n;

	private $original_customer;

	public function set_up(): void {
		$this->original_customer = isset( WC()->customer ) ? WC()->customer : null;
		$this->i18n              = new I18n();
	}

	public function tear_down(): void {
		remove_all_filters( 'sequra_shopper_country' );
		WC()->customer = $this->original_customer;
	}

	public function testGetCurrentCountry_withShippingCountry_returnsShipping(): void {
		$customer = new WC_Customer();
		$customer->set_shipping_country( 'FR' );
		$customer->set_billing_country( 'ES' );
		WC()->customer = $customer;

		$this->assertSame( 'FR', $this->i18n->get_current_country() );
	}

	public function testGetCurrentCountry_shippingEmpty_fallsBackToBilling(): void {
		$customer = new WC_Customer();
		$customer->set_billing_country( 'IT' );
		WC()->customer = $customer;

		$this->assertSame( 'IT', $this->i18n->get_current_country() );
	}

	public function testGetCurrentCountry_filterOverridesAndUppercases(): void {
		$customer = new WC_Customer();
		$customer->set_shipping_country( 'ES' );
		WC()->customer = $customer;

		add_filter(
			'sequra_shopper_country',
			function () {
				return 'pt';
			}
		);

		$this->assertSame( 'PT', $this->i18n->get_current_country() );
	}
}
