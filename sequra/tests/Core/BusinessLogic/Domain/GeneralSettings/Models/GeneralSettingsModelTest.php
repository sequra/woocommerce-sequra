<?php
/**
 * Tests for the GeneralSettingsModelTest class.
 * 
 * @package Sequra\WC\Tests\Core\Extension\BusinessLogic\AdminAPI\GeneralSettings
 */

namespace SeQura\WC\Tests\Core\Extension\BusinessLogic\Domain\GeneralSettings\Models;

use SeQura\Core\BusinessLogic\Domain\GeneralSettings\Models\GeneralSettings;
use SeQura\Core\Tests\BusinessLogic\Common\BaseTestCase;

/**
 * Class GeneralSettingsModelTest
 *
 * @package SeQura\Core\Tests\BusinessLogic\Domain\GeneralSettings\Models
 */
class GeneralSettingsModelTest extends BaseTestCase {

	public function testSettersAndGetters(): void {
		$generalSettings = new GeneralSettings(
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
		$generalSettings->setEnabledForServices( true );
		$generalSettings->setAllowFirstServicePaymentDelay( false );
		$generalSettings->setAllowServiceRegistrationItems( false );
		$generalSettings->setDefaultServicesEndDate( 'P2Y' );

		self::assertFalse( $generalSettings->isShowSeQuraCheckoutAsHostedPage() );
		self::assertFalse( $generalSettings->isSendOrderReportsPeriodicallyToSeQura() );
		self::assertEquals( array( 'address 4', 'address 5' ), $generalSettings->getAllowedIPAddresses() );
		self::assertEquals( array( 'sku 4', 'sku 5' ), $generalSettings->getExcludedProducts() );
		self::assertEquals( array( '3', '4' ), $generalSettings->getExcludedCategories() );
		self::assertTrue( $generalSettings->isEnabledForServices() );
		self::assertFalse( $generalSettings->isAllowFirstServicePaymentDelay() );
		self::assertFalse( $generalSettings->isAllowServiceRegistrationItems() );
		self::assertEquals( 'P2Y', $generalSettings->getDefaultServicesEndDate() );
	}
}
