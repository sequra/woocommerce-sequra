<?php
/**
 * Tests for the GeneralSettingsModelTest class.
 * 
 * @package Sequra\WC\Tests\Core\Extension\BusinessLogic\AdminAPI\GeneralSettings
 */

namespace SeQura\WC\Tests\Core\Extension\BusinessLogic\Domain\GeneralSettings\Models;

require_once __DIR__ . '/../../../../integration-core-test-autoload.php';

use SeQura\WC\Core\Extension\BusinessLogic\Domain\GeneralSettings\Models\General_Settings;
use SeQura\Core\Tests\BusinessLogic\Common\BaseTestCase;

/**
 * Class GeneralSettingsModelTest
 *
 * @package SeQura\Core\Tests\BusinessLogic\Domain\GeneralSettings\Models
 */
class GeneralSettingsModelTest extends BaseTestCase {

	public function testSettersAndGetters(): void {
		$generalSettings = new General_Settings(
			true,
			true,
			array( 'address 1', 'address 2', 'address 3' ),
			array( 'sku 1', 'sku 2', 'sku 3' ),
			array( '1', '2' ),
			false,
			true,
			true,
			'P1Y'
		);

		$generalSettings->setShowSeQuraCheckoutAsHostedPage( false );
		$generalSettings->setSendOrderReportsPeriodicallyToSeQura( false );
		$generalSettings->setAllowedIPAddresses( array( 'address 4', 'address 5' ) );
		$generalSettings->setExcludedProducts( array( 'sku 4', 'sku 5' ) );
		$generalSettings->setExcludedCategories( array( '3', '4' ) );
		$generalSettings->set_enabled_for_services( true );
		$generalSettings->set_allow_first_service_payment_delay( false );
		$generalSettings->set_allow_service_reg_items( false );
		$generalSettings->set_default_services_end_date( 'P2Y' );

		self::assertFalse( $generalSettings->isShowSeQuraCheckoutAsHostedPage() );
		self::assertFalse( $generalSettings->isSendOrderReportsPeriodicallyToSeQura() );
		self::assertEquals( array( 'address 4', 'address 5' ), $generalSettings->getAllowedIPAddresses() );
		self::assertEquals( array( 'sku 4', 'sku 5' ), $generalSettings->getExcludedProducts() );
		self::assertEquals( array( '3', '4' ), $generalSettings->getExcludedCategories() );
		self::assertTrue( $generalSettings->is_enabled_for_services() );
		self::assertFalse( $generalSettings->is_allow_first_service_payment_delay() );
		self::assertFalse( $generalSettings->is_allow_service_reg_items() );
		self::assertEquals( 'P2Y', $generalSettings->get_default_services_end_date() );
	}
}
