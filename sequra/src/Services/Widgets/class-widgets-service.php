<?php
/**
 * Widgets Service Interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Widgets;

use SeQura\Core\BusinessLogic\CheckoutAPI\CheckoutAPI;
use SeQura\Core\BusinessLogic\CheckoutAPI\PromotionalWidgets\Requests\PromotionalWidgetsCheckoutRequest;
use SeQura\Core\BusinessLogic\Domain\Integration\PromotionalWidgets\WidgetConfiguratorInterface;
use SeQura\Core\BusinessLogic\Domain\Multistore\StoreContext;
use SeQura\WC\Services\I18n\Interface_I18n;
use SeQura\WC\Services\Log\Interface_Logger_Service;
use SeQura\WC\Services\Shopper\Interface_Shopper_Service;

/**
 * Widgets Service Interface
 * 
 * @phpstan-import-type WidgetDataArray from Interface_Widgets_Service
 * @phpstan-import-type WidgetConfigParamsDataArray from Interface_Widgets_Service
 */
class Widgets_Service implements Interface_Widgets_Service {

	/**
	 * Logger service
	 * 
	 * @var Interface_Logger_Service
	 */
	private $logger;

	/**
	 * Internationalization service
	 * 
	 * @var Interface_I18n
	 */
	private $i18n;

	/**
	 * Shopper service
	 * 
	 * @var Interface_Shopper_Service
	 */
	private $shopper_service;

	/**
	 * Widget configurator
	 * 
	 * @var WidgetConfiguratorInterface
	 */
	private $widget_configurator;

	/**
	 * Store context
	 * 
	 * @var StoreContext
	 */
	private $store_context;

	/**
	 * Constructor
	 */
	public function __construct(
		Interface_Logger_Service $logger,
		Interface_I18n $i18n,
		Interface_Shopper_Service $shopper_service,
		WidgetConfiguratorInterface $widget_configurator,
		StoreContext $store_context
	) {
		$this->logger              = $logger;
		$this->i18n                = $i18n;
		$this->shopper_service     = $shopper_service;
		$this->widget_configurator = $widget_configurator;
		$this->store_context       = $store_context;
	}

	/**
	 * Get available widget for cart page
	 * 
	 * @return WidgetDataArray|null The available widget, or null if cannot be determined
	 */
	public function get_widget_for_cart_page(): ?array {
		try {
			$country = $this->i18n->get_current_country();

			/**
			 * Fetch promotional widget data from CheckoutAPI
			 *  
			 * @var WidgetDataArray[] $widgets */
			$widgets = CheckoutAPI::get()
			->promotionalWidgets( $this->store_context->getStoreId() )
			->getAvailableWidgetForCartPage(
				new PromotionalWidgetsCheckoutRequest(
					$country,
					$country,
					$this->widget_configurator->getCurrency() ?? '',
					$this->shopper_service->get_ip() 
				)
			)
			->toArray();

			if ( empty( $widgets ) ) {
				$this->logger->log_info( 'No cart widget available', __FUNCTION__, __CLASS__ );
				return null;
			}

			return $widgets[0];
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			return null;
		}
	}

	/**
	 * Get available widget for product listing page
	 * 
	 * @return WidgetDataArray|null The available widget, or null if cannot be determined
	 */
	public function get_widget_for_product_listing_page(): ?array {
		try {
			$country = $this->i18n->get_current_country();

			/**
			 * Fetch promotional widget data from CheckoutAPI
			 *  
			 * @var WidgetDataArray[] $widgets */
			$widgets = CheckoutAPI::get()
			->promotionalWidgets( $this->store_context->getStoreId() )
			->getAvailableMiniWidgetForProductListingPage(
				new PromotionalWidgetsCheckoutRequest(
					$country,
					$country,
					$this->widget_configurator->getCurrency() ?? '',
					$this->shopper_service->get_ip() 
				)
			)
			->toArray();

			if ( empty( $widgets ) ) {
				$this->logger->log_info( 'No product listing widget available', __FUNCTION__, __CLASS__ );
				return null;
			}

			return $widgets[0];
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			return null;
		}
	}

	/**
	 * Get available widgets for product page
	 * 
	 * @return WidgetDataArray[]|null The available widgets, or null if cannot be determined
	 */
	public function get_widgets_for_product_page( string $product_id ): ?array {
		try {
			$country = $this->i18n->get_current_country();

			/**
			 * Fetch promotional widget data from CheckoutAPI
			 *  
			 * @var WidgetDataArray[] $widgets */
			$widgets = CheckoutAPI::get()
			->promotionalWidgets( $this->store_context->getStoreId() )
			->getAvailableWidgetsForProductPage(
				new PromotionalWidgetsCheckoutRequest(
					$country,
					$country,
					$this->widget_configurator->getCurrency() ?? '',
					$this->shopper_service->get_ip(),
					$product_id
				)
			)
			->toArray();

			return $widgets;
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			return null;
		}
	}

	/**
	 * Get widget configuration parameters required in the frontend
	 * 
	 * @return WidgetConfigParamsDataArray|null The configuration parameters, or null if cannot be determined
	 */
	public function get_widget_config_params(): ?array {
		try {
			$country = $this->i18n->get_current_country();

			/**
			 * Fetch promotional widget data from CheckoutAPI
			 *  
			 * @var WidgetConfigParamsDataArray $config */
			$config = CheckoutAPI::get()
			->promotionalWidgets( $this->store_context->getStoreId() )
			->getPromotionalWidgetInitializeData(
				new PromotionalWidgetsCheckoutRequest( $country, $country )
			)
			->toArray();

			return $config;
		} catch ( \Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			return null;
		}
	}
}
