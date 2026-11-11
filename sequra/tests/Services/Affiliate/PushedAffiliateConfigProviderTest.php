<?php
/**
 * Tests for the Pushed_Affiliate_Config_Provider class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Services\Affiliate;

use SeQura\Core\BusinessLogic\Domain\Affiliate\Models\AffiliateSettings;
use SeQura\Core\BusinessLogic\Domain\Affiliate\Services\AffiliateSettingsService;
use SeQura\WC\Services\Affiliate\Pushed_Affiliate_Config_Provider;
use WP_UnitTestCase;

class PushedAffiliateConfigProviderTest extends WP_UnitTestCase {

	/**
	 * Core affiliate settings service mock.
	 *
	 * @var AffiliateSettingsService&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $affiliate_settings_service;

	/**
	 * Provider under test.
	 *
	 * @var Pushed_Affiliate_Config_Provider
	 */
	private $provider;

	public function set_up(): void {
		parent::set_up();
		$this->affiliate_settings_service = $this->createMock( AffiliateSettingsService::class );
		$this->provider                   = new Pushed_Affiliate_Config_Provider( $this->affiliate_settings_service );
	}

	public function testGetSettings_mapsCoreSettingsToPluginShape(): void {
		$this->affiliate_settings_service->method( 'getAffiliateSettings' )
			->willReturn( new AffiliateSettings( true, 'OFFER1', 'TOKEN1' ) );

		$this->assertSame(
			array(
				'enabled'        => true,
				'offer_id'       => 'OFFER1',
				'security_token' => 'TOKEN1',
			),
			$this->provider->get_settings()
		);
	}

	public function testIsEnabled_delegatesToCoreService(): void {
		$this->affiliate_settings_service->method( 'getAffiliateSettings' )
			->willReturn( new AffiliateSettings( true, 'OFFER1', 'TOKEN1' ) );

		$this->assertTrue( $this->provider->is_enabled() );
	}

	public function testGetSettings_returnsDisabledWhenCoreHasNoConfig(): void {
		// The core read service returns a safe disabled default when nothing is stored,
		// so the plugin stays dormant until a real config arrives.
		$this->affiliate_settings_service->method( 'getAffiliateSettings' )
			->willReturn( new AffiliateSettings( false, '', '' ) );

		$this->assertSame(
			array(
				'enabled'        => false,
				'offer_id'       => '',
				'security_token' => '',
			),
			$this->provider->get_settings()
		);
		$this->assertFalse( $this->provider->is_enabled() );
	}
}
