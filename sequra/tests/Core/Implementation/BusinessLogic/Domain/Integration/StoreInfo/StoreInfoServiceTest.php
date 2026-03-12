<?php
/**
 * Tests for the Store_Info_Service class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Core\Implementation\BusinessLogic\Domain\Integration\StoreInfo;

use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Platform;
use SeQura\Core\BusinessLogic\Domain\Stores\Models\StoreInfo;
use SeQura\WC\Core\Implementation\BusinessLogic\Domain\Integration\StoreInfo\Store_Info_Service;
use SeQura\WC\Services\Platform\Interface_Platform_Provider;
use WP_UnitTestCase;

class StoreInfoServiceTest extends WP_UnitTestCase {

	/**
	 * @var Interface_Platform_Provider&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $platform_provider;

	/**
	 * @var Store_Info_Service
	 */
	private $service;

	/**
	 * @var Platform
	 */
	private $platform;

	public function set_up(): void {
		parent::set_up();

		$this->platform = new Platform(
			'WooCommerce',
			'7.0.0',
			'Linux x86_64',
			'MySQL',
			'8.0.0',
			'4.2.0',
			'8.1.0'
		);

		$this->platform_provider = $this->createMock( Interface_Platform_Provider::class );
		$this->service           = new Store_Info_Service( $this->platform_provider );
	}

	public function testGetStoreInfo_returnsStoreInfoInstance(): void {
		$this->platform_provider->method( 'get' )->willReturn( $this->platform );

		$result = $this->service->getStoreInfo();

		$this->assertInstanceOf( StoreInfo::class, $result );
	}

	public function testGetStoreInfo_containsPlatformInfo(): void {
		$this->platform_provider->method( 'get' )->willReturn( $this->platform );

		$result = $this->service->getStoreInfo();

		$this->assertSame( 'WooCommerce', $result->getPlatform() );
		$this->assertSame( '7.0.0', $result->getPlatformVersion() );
		$this->assertSame( '4.2.0', $result->getPluginVersion() );
	}

	public function testGetStoreInfo_containsPhpAndDbInfo(): void {
		$this->platform_provider->method( 'get' )->willReturn( $this->platform );

		$result = $this->service->getStoreInfo();

		$this->assertSame( '8.1.0', $result->getPhpVersion() );
		$this->assertNotEmpty( $result->getDb() );
		$this->assertStringContainsString( 'MySQL', $result->getDb() );
	}

	public function testGetStoreInfo_containsStoreNameAndUrl(): void {
		$this->platform_provider->method( 'get' )->willReturn( $this->platform );

		$result = $this->service->getStoreInfo();

		$this->assertIsString( $result->getStoreName() );
		$this->assertIsString( $result->getStoreUrl() );
	}

	public function testGetStoreInfo_containsActivePlugins(): void {
		$this->platform_provider->method( 'get' )->willReturn( $this->platform );

		$result = $this->service->getStoreInfo();

		$this->assertIsArray( $result->getPlugins() );
	}
}
