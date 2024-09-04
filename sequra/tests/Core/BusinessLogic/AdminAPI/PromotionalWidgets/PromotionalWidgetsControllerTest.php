<?php
/**
 * Tests for the PromotionalWidgetsController class.
 * 
 * @package Sequra\WC\Tests\Core\Extension\BusinessLogic\AdminAPI\GeneralSettings
 */

namespace SeQura\WC\Tests\Core\Extension\BusinessLogic\PromotionalWidgets;

require_once __DIR__ . '/../../../integration-core-test-autoload.php';

use SeQura\Core\BusinessLogic\AdminAPI\AdminAPI;
use SeQura\Core\BusinessLogic\AdminAPI\PromotionalWidgets\PromotionalWidgetsController;
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

		// Extend WidgetSettingsRepository.
		TestServiceRegister::registerService(
			WidgetSettingsRepositoryInterface::class,
			static function () {
				return new Widget_Settings_Repository(
					TestRepositoryRegistry::getRepository( WidgetSettings::getClassName() ),
					TestServiceRegister::getService( StoreContext::class )
				);
			}
		);

		// Extend PromotionalWidgetsController.
		TestServiceRegister::registerService(
			PromotionalWidgetsController::class,
			static function () {
				return new Promotional_Widgets_Controller(
					TestServiceRegister::getService( WidgetSettingsService::class )
				);
			}
		);

		$this->widgetSettingsRepository = TestServiceRegister::getService( WidgetSettingsRepositoryInterface::class );
	}

	public function testGetSettings() {
		$settings = new Widget_Settings(
			true,
			'qwerty',
			false,
			false,
			false,
			'',
			'{"alignment":"center","amount-font-bold":"true","amount-font-color":"#1c1c1c","amount-font-size":"15","background-color":"white","border-color":"#ce5c00","border-radius":"","class":"","font-color":"#1c1c1c","link-font-color":"#1c1c1c","link-underline":"true","no-costs-claim":"","size":"M","starting-text":"only","type":"banner"}',
			new WidgetLabels(
				array(
					'ES' => 'test es',
					'IT' => 'test it',
				),
				array(
					'ES' => 'test test es',
					'IT' => 'test test it',
				)
			),
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
						'pp3 title'
					),
					new Widget_Location(
						true,
						'selector-for-location2',
						'widget-styles-it',
						'i1',
						'IT',
						'i1 title'
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
						'pp3 title'
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
						'pp3 title'
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
						'product'        => 'pp3',
						'country'        => 'ES',
						'sel_for_target' => 'selector-for-location',
						'widget_styles'  => 'widget-styles-es',
						'display_widget' => true,
						'title'          => 'pp3 title',
					),
					array(
						'product'        => 'i1',
						'country'        => 'IT',
						'sel_for_target' => 'selector-for-location2',
						'widget_styles'  => 'widget-styles-it',
						'display_widget' => true,
						'title'          => 'i1 title',
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
						'title'             => 'pp3 title',
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
						'title'             => 'pp3 title',
					),
				),
				
			),
			$result->toArray()
		);
	}

	public function testSetSettings() {
		$settings = new Widget_Settings_Request(
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
					'product'        => 'pp3',
					'country'        => 'ES',
					'sel_for_target' => 'selector-for-location',
					'widget_styles'  => 'widget-styles-es',
					'display_widget' => true,
				),
				array(
					'product'        => 'i1',
					'country'        => 'IT',
					'sel_for_target' => 'selector-for-location2',
					'widget_styles'  => 'widget-styles-it',
					'display_widget' => true,
				),
			)
		);

		AdminAPI::get()->widgetConfiguration( 'store1' )->setWidgetSettings( $settings );

		$savedSettings = StoreContext::doWithStore( 'store1', array( $this->widgetSettingsRepository, 'getWidgetSettings' ) );
		self::assertEquals( $settings->transformToDomainModel(), $savedSettings );
	}
}
