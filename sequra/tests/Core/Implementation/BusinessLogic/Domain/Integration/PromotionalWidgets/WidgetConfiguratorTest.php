<?php
/**
 * Tests for the Widget_Configurator class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Core\Implementation\BusinessLogic\Domain\Integration\PromotionalWidgets;

use SeQura\Core\BusinessLogic\Domain\PromotionalWidgets\Models\WidgetSettings;
use SeQura\WC\Core\Implementation\BusinessLogic\Domain\Integration\PromotionalWidgets\Widget_Configurator;
use SeQura\WC\Services\I18n\Interface_I18n;
use WP_UnitTestCase;

class WidgetConfiguratorTest extends WP_UnitTestCase {

	/**
	 * @var Interface_I18n&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $i18n;

	/**
	 * @var Widget_Configurator
	 */
	private $configurator;

	public function set_up(): void {
		parent::set_up();
		$this->i18n         = $this->createMock( Interface_I18n::class );
		$this->configurator = new Widget_Configurator( $this->i18n );
	}

	public function testGetDefaultWidgetSettings_returnsWidgetSettingsInstance(): void {
		$settings = $this->configurator->getDefaultWidgetSettings();
		$this->assertInstanceOf( WidgetSettings::class, $settings );
	}

	public function testGetDefaultWidgetSettings_hasExpectedSelectors(): void {
		$settings = $this->configurator->getDefaultWidgetSettings();

		$product_settings = $settings->getWidgetSettingsForProduct();
		$this->assertNotNull( $product_settings );
		$this->assertSame( '.summary .price>.amount,.summary .price ins .amount', $product_settings->getPriceSelector() );
		$this->assertSame( '.summary>.price', $product_settings->getLocationSelector() );
		$this->assertSame(
			'.woocommerce-variation-price .price>.amount,.woocommerce-variation-price .price ins .amount',
			$product_settings->getAltPriceSelector()
		);
		$this->assertSame( '.variations', $product_settings->getAltPriceTriggerSelector() );

		$cart_settings = $settings->getWidgetSettingsForCart();
		$this->assertNotNull( $cart_settings );
		$this->assertSame( '.order-total .amount', $cart_settings->getPriceSelector() );
		$this->assertSame( '.order-total', $cart_settings->getLocationSelector() );

		$listing_settings = $settings->getWidgetSettingsForListing();
		$this->assertNotNull( $listing_settings );
		$this->assertSame( '.product .price>.amount:first-child,.product .price ins .amount', $listing_settings->getPriceSelector() );
		$this->assertSame( '.product .price', $listing_settings->getLocationSelector() );
	}
}
