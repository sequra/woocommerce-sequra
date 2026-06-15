<?php
/**
 * Tests for the Affiliate_Settings_Service class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Services\Affiliate;

use SeQura\Core\BusinessLogic\Domain\Multistore\StoreContext;
use SeQura\WC\Services\Affiliate\Affiliate_Settings_Service;
use WP_UnitTestCase;

// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.Found
class AffiliateSettingsServiceTest extends WP_UnitTestCase {

	/**
	 * @var Affiliate_Settings_Service
	 */
	private $service;

	public function set_up(): void {
		parent::set_up();
		$store_context = $this->createMock( StoreContext::class );
		$this->service = new Affiliate_Settings_Service( $store_context );
	}

	public function testSaveAndGetRoundtrip(): void {
		$this->service->save_settings( '1', true, '39', 'abc123TOKEN0000000000000000000000' );

		$settings = $this->service->get_settings( '1' );

		$this->assertTrue( $settings['enabled'] );
		$this->assertSame( '39', $settings['offer_id'] );
		$this->assertSame( 'abc123TOKEN0000000000000000000000', $settings['security_token'] );
	}

	public function testGetReturnsDefaultsWhenMissing(): void {
		$settings = $this->service->get_settings( 'missing-store' );

		$this->assertFalse( $settings['enabled'] );
		$this->assertSame( '', $settings['offer_id'] );
		$this->assertSame( '', $settings['security_token'] );
	}

	public function testIsEnabledRequiresAllValues(): void {
		$this->service->save_settings( '1', true, '', '' );
		$this->assertFalse( $this->service->is_enabled( '1' ) );

		$this->service->save_settings( '1', false, '39', 'token0000000000000000000000000000' );
		$this->assertFalse( $this->service->is_enabled( '1' ) );

		$this->service->save_settings( '1', true, '39', 'token0000000000000000000000000000' );
		$this->assertTrue( $this->service->is_enabled( '1' ) );
	}
}
