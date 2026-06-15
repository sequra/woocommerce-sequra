<?php
/**
 * Tests for the Affiliate_Settings_REST_Controller validation.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Controllers\Rest;

use SeQura\Core\BusinessLogic\Domain\Multistore\StoreContext;
use SeQura\Core\Infrastructure\Utility\RegexProvider;
use SeQura\WC\Controllers\Rest\Affiliate_Settings_REST_Controller;
use SeQura\WC\Services\Affiliate\Interface_Affiliate_Settings_Service;
use SeQura\WC\Services\Log\Interface_Logger_Service;
use WP_UnitTestCase;

// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.Found
class AffiliateSettingsRestControllerValidationTest extends WP_UnitTestCase {

	/**
	 * @var Affiliate_Settings_REST_Controller
	 */
	private $controller;

	public function set_up(): void {
		parent::set_up();
		$this->controller = new Affiliate_Settings_REST_Controller(
			'sequra/v1',
			$this->createMock( Interface_Logger_Service::class ),
			$this->createMock( RegexProvider::class ),
			$this->createMock( StoreContext::class ),
			$this->createMock( Interface_Affiliate_Settings_Service::class )
		);
	}

	public function testSecurityTokenAcceptsAlphanumeric(): void {
		$this->assertTrue( $this->controller->validate_security_token( 'abc123XYZ', null, 'securityToken' ) );
	}

	public function testSecurityTokenRejectsSymbols(): void {
		$this->assertFalse( $this->controller->validate_security_token( 'abc-123_!', null, 'securityToken' ) );
	}

	public function testSecurityTokenRejectsTooLong(): void {
		$this->assertFalse( $this->controller->validate_security_token( str_repeat( 'a', 65 ), null, 'securityToken' ) );
	}

	public function testOfferIdAcceptsUpToFourDigits(): void {
		$this->assertTrue( $this->controller->validate_offer_id( '39', null, 'offerId' ) );
		$this->assertTrue( $this->controller->validate_offer_id( '1234', null, 'offerId' ) );
	}

	public function testOfferIdRejectsNonNumericOrTooLong(): void {
		$this->assertFalse( $this->controller->validate_offer_id( 'ab', null, 'offerId' ) );
		$this->assertFalse( $this->controller->validate_offer_id( '12345', null, 'offerId' ) );
	}
}
