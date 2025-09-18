<?php
/**
 * Tests for the PromotionalWidgetsController class.
 * 
 * @package Sequra\WC\Tests\Core\Extension\BusinessLogic\AdminAPI\GeneralSettings
 */

namespace SeQura\WC\Tests\Core\Extension\BusinessLogic\PromotionalWidgets;

use SeQura\Core\BusinessLogic\AdminAPI\AdminAPI;
use SeQura\Core\BusinessLogic\AdminAPI\PromotionalWidgets\PromotionalWidgetsController;
use SeQura\Core\BusinessLogic\AdminAPI\PromotionalWidgets\Requests\WidgetSettingsRequest;
use SeQura\Core\BusinessLogic\Domain\Integration\SellingCountries\SellingCountriesServiceInterface;
use SeQura\Core\BusinessLogic\Domain\Multistore\StoreContext;
use SeQura\Core\BusinessLogic\Domain\PromotionalWidgets\Models\WidgetLabels;
use SeQura\Core\BusinessLogic\Domain\PromotionalWidgets\RepositoryContracts\WidgetSettingsRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\PromotionalWidgets\Services\WidgetSettingsService;
use SeQura\Core\Tests\BusinessLogic\Common\BaseTestCase;
use SeQura\Core\Tests\BusinessLogic\Common\MockComponents\MockSellingCountriesService;
use SeQura\Core\Tests\Infrastructure\Common\TestServiceRegister;
use SeQura\WC\Core\Extension\BusinessLogic\AdminAPI\PromotionalWidgets\Promotional_Widgets_Controller;
use SeQura\WC\Core\Extension\BusinessLogic\DataAccess\PromotionalWidgets\Repositories\Widget_Settings_Repository;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\PromotionalWidgets\Models\Widget_Location;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\PromotionalWidgets\Models\Widget_Location_Config;
use SeQura\Core\Tests\Infrastructure\Common\TestComponents\ORM\TestRepositoryRegistry;
use SeQura\Core\BusinessLogic\DataAccess\PromotionalWidgets\Entities\WidgetSettings;
use SeQura\Core\BusinessLogic\Domain\PromotionalWidgets\Models\WidgetSettings as DomainWidgetSettings;
use SeQura\WC\Core\Extension\BusinessLogic\AdminAPI\PromotionalWidgets\Requests\Widget_Settings_Request;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\PromotionalWidgets\Models\Mini_Widget;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\PromotionalWidgets\Models\Mini_Widget_Config;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\PromotionalWidgets\Models\Widget_Settings;

class PromotionalWidgetsControllerTest extends BaseTestCase {

	/**
	 * @var WidgetSettingsRepositoryInterface
	 */
	private $widgetSettingsRepository;

	protected function setUp(): void {
		parent::setUp();

		TestServiceRegister::registerService(
			SellingCountriesServiceInterface::class,
			function () {
				return new MockSellingCountriesService();
			}
		);

		$this->widgetSettingsRepository = TestServiceRegister::getService( WidgetSettingsRepositoryInterface::class );
	}

	public function testGetSettings() {
		$settings = new DomainWidgetSettings(
			true,
			false,
			false,
			false,
			'{"alignment":"center","amount-font-bold":"true","amount-font-color":"#1c1c1c","amount-font-size":"15","background-color":"white","border-color":"#ce5c00","border-radius":"","class":"","font-color":"#1c1c1c","link-font-color":"#1c1c1c","link-underline":"true","no-costs-claim":"","size":"M","starting-text":"only","type":"banner"}',

			// TODO: refactor this
			new Widget_Location_Config(
				'selector-for-price',
				'selector-for-alt-price',
				'selector-for-alt-price-trigger',
				'selector-for-default-location',
				array(
					new Widget_Location(
						true,
						'selector-for-location',
						'widget-styles-es',
						'pp3',
						'ES',
						'pp3_campaign'
					),
					new Widget_Location(
						true,
						'selector-for-location2',
						'widget-styles-it',
						'i1',
						'IT',
						'i1_campaign'
					),
				)
			),
			new Mini_Widget_Config(
				'cart-mini-widget-sel-for-price',
				'cart-mini-widget-sel-for-location',
				array(
					new Mini_Widget(
						'cart-mini-widget-sel-for-price-es',
						'cart-mini-widget-sel-for-location-es',
						'message-es',
						'message-below-limit-es',
						'pp3',
						'ES',
						'pp3_campaign'
					),
				)
			),
			new Mini_Widget_Config(
				'listing-mini-widget-sel-for-price',
				'listing-mini-widget-sel-for-location',
				array(
					new Mini_Widget(
						'listing-mini-widget-sel-for-price-es',
						'listing-mini-widget-sel-for-location-es',
						'message-es',
						'message-below-limit-es',
						'pp3',
						'ES',
						'pp3_campaign'
					),
				)
			)
		);
		StoreContext::doWithStore( 'store1', array( $this->widgetSettingsRepository, 'setWidgetSettings' ), array( $settings ) );

		$result = AdminAPI::get()->widgetConfiguration( 'store1' )->getWidgetSettings();

		self::assertEquals(
			array(
				'useWidgets'                            => $settings->isEnabled(),
				'displayWidgetOnProductPage'            => $settings->isDisplayOnProductPage(),
				'showInstallmentAmountInProductListing' => $settings->isShowInstallmentsInProductListing(),
				'showInstallmentAmountInCartPage'       => $settings->isShowInstallmentsInCartPage(),
				'assetsKey'                             => $settings->getAssetsKey(),
				'miniWidgetSelector'                    => '',
				'widgetConfiguration'                   => '{"alignment":"center","amount-font-bold":"true","amount-font-color":"#1c1c1c","amount-font-size":"15","background-color":"white","border-color":"#ce5c00","border-radius":"","class":"","font-color":"#1c1c1c","link-font-color":"#1c1c1c","link-underline":"true","no-costs-claim":"","size":"M","starting-text":"only","type":"banner"}',
				'widgetLabels'                          => array(
					'messages'           => $settings->getWidgetLabels()->getMessages(),
					'messagesBelowLimit' => $settings->getWidgetLabels()->getMessagesBelowLimit(),
				),
				'selForPrice'                           => 'selector-for-price',
				'selForAltPrice'                        => 'selector-for-alt-price',
				'selForAltPriceTrigger'                 => 'selector-for-alt-price-trigger',
				'selForDefaultLocation'                 => 'selector-for-default-location',
				'customLocations'                       => array(
					array(
						'product'       => 'pp3',
						'country'       => 'ES',
						'selForTarget'  => 'selector-for-location',
						'widgetStyles'  => 'widget-styles-es',
						'displayWidget' => true,
						'campaign'      => 'pp3_campaign',
					),
					array(
						'product'       => 'i1',
						'country'       => 'IT',
						'selForTarget'  => 'selector-for-location2',
						'widgetStyles'  => 'widget-styles-it',
						'displayWidget' => true,
						'campaign'      => 'i1_campaign',
					),
				),
				'selForCartPrice'                       => 'cart-mini-widget-sel-for-price',
				'selForCartLocation'                    => 'cart-mini-widget-sel-for-location',
				'cartMiniWidgets'                       => array(
					array(
						'selForPrice'       => 'cart-mini-widget-sel-for-price-es',
						'selForLocation'    => 'cart-mini-widget-sel-for-location-es',
						'message'           => 'message-es',
						'messageBelowLimit' => 'message-below-limit-es',
						'product'           => 'pp3',
						'countryCode'       => 'ES',
						'campaign'          => 'pp3_campaign',
					),
				),
				'selForListingPrice'                    => 'listing-mini-widget-sel-for-price',
				'selForListingLocation'                 => 'listing-mini-widget-sel-for-location',
				'listingMiniWidgets'                    => array(
					array(
						'selForPrice'       => 'listing-mini-widget-sel-for-price-es',
						'selForLocation'    => 'listing-mini-widget-sel-for-location-es',
						'message'           => 'message-es',
						'messageBelowLimit' => 'message-below-limit-es',
						'product'           => 'pp3',
						'countryCode'       => 'ES',
						'campaign'          => 'pp3_campaign',
					),
				),
				
			),
			$result->toArray()
		);
	}

	public function testSetSettings() {
		// TODO: Refactor this
		$settings = new WidgetSettingsRequest(
			false,
			'qqqwerty',
			false,
			true,
			true,
			'',
			'banner',
			array(
				'ES' => 'test es',
				'IT' => 'test it',
			),
			array(
				'ES' => 'test test es',
				'IT' => 'test test it',
			),
			'selector-for-price',
			'selector-for-alt-price',
			'selector-for-alt-price-trigger',
			'selector-for-default-location',
			array(
				array(
					'product'       => 'pp3',
					'country'       => 'ES',
					'selForTarget'  => 'selector-for-location',
					'widgetStyles'  => 'widget-styles-es',
					'displayWidget' => true,
				),
				array(
					'product'       => 'i1',
					'country'       => 'IT',
					'selForTarget'  => 'selector-for-location2',
					'widgetStyles'  => 'widget-styles-it',
					'displayWidget' => true,
				),
			)
		);

		AdminAPI::get()->widgetConfiguration( 'store1' )->setWidgetSettings( $settings );

		$savedSettings = StoreContext::doWithStore( 'store1', array( $this->widgetSettingsRepository, 'getWidgetSettings' ) );
		self::assertEquals( $settings->transformToDomainModel(), $savedSettings );
	}
}
