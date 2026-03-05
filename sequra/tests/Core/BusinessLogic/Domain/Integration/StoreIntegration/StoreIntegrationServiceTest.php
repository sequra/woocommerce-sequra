<?php
/**
 * Tests for the Store_Integration_Service class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Core\BusinessLogic\Domain\Integration\StoreIntegration;

use SeQura\Core\BusinessLogic\Domain\StoreIntegration\Models\Capability;
use SeQura\Core\BusinessLogic\Domain\URL\Model\URL;
use SeQura\WC\Core\Implementation\BusinessLogic\Domain\Integration\StoreIntegration\Store_Integration_Service;
use WP_UnitTestCase;

class StoreIntegrationServiceTest extends WP_UnitTestCase {

	/**
	 * @var Store_Integration_Service
	 */
	private $service;

	public function set_up(): void {
		parent::set_up();
		$this->service = new Store_Integration_Service();
	}

	public function testGetEndpoint_returnsStoreIntegration(): void {
		$this->assertSame( 'store-integration', $this->service->get_endpoint() );
	}

	public function testGetRestBase_returnsWebhook(): void {
		$this->assertSame( '/webhook', $this->service->get_rest_base() );
	}

	public function testGetWebhookUrl_returnsCorrectUrl(): void {
		$url = $this->service->getWebhookUrl();
		$this->assertInstanceOf( URL::class, $url );
		$this->assertStringContainsString( 'sequra/v1/webhook/store-integration', $url->getPath() );
	}

	public function testGetSupportedCapabilities_returnsExpectedCapabilities(): void {
		$capabilities = $this->service->getSupportedCapabilities();
		$this->assertCount( 7, $capabilities );
	}

	public function testGetSupportedCapabilities_returnsCapabilityInstances(): void {
		$capabilities = $this->service->getSupportedCapabilities();
		foreach ( $capabilities as $capability ) {
			$this->assertInstanceOf( Capability::class, $capability );
		}
	}
}
