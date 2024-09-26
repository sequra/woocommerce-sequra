<?php
/**
 * Report service interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Report;

use SeQura\Core\BusinessLogic\Domain\CountryConfiguration\RepositoryContracts\CountryConfigurationRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\Multistore\StoreContext;
use SeQura\Core\BusinessLogic\Domain\OrderReport\Tasks\OrderReportTask;
use SeQura\Core\BusinessLogic\Domain\StatisticalData\RepositoryContracts\StatisticalDataRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\Stores\Services\StoreService;
use SeQura\Core\BusinessLogic\Webhook\Services\ShopOrderService;
use SeQura\WC\Core\Extension\Infrastructure\Configuration\Configuration;
use SeQura\WC\Services\Order\Interface_Order_Service;
use WC_Order;

/**
 * Report service
 */
class Report_Service implements Interface_Report_Service {

	/**
	 * Configuration
	 *
	 * @var Configuration
	 */
	private $configuration;

	/**
	 * Store service
	 *
	 * @var StoreService
	 */
	private $store_service;

	/**
	 * Shop order service
	 *
	 * @var ShopOrderService
	 */
	private $shop_order_service;

	/**
	 * Statistical data repository
	 *
	 * @var StatisticalDataRepositoryInterface
	 */
	private $statistical_data_repository;

	/**
	 * Country configuration repository
	 *
	 * @var CountryConfigurationRepositoryInterface
	 */
	private $country_configuration_repository;

	/**
	 * Order service
	 *
	 * @var Interface_Order_Service
	 */
	private $order_service;

	/**
	 * Store context
	 *
	 * @var StoreContext
	 */
	private $store_context;

	/**
	 * Constructor.
	 */
	public function __construct( 
		Configuration $configuration,
		StoreService $store_service,
		ShopOrderService $shop_order_service,
		StatisticalDataRepositoryInterface $statistical_data_repository,
		CountryConfigurationRepositoryInterface $country_configuration_repository,
		Interface_Order_Service $order_service,
		StoreContext $store_context
	) {
		$this->configuration                    = $configuration;
		$this->store_service                    = $store_service;
		$this->shop_order_service               = $shop_order_service;
		$this->statistical_data_repository      = $statistical_data_repository;
		$this->country_configuration_repository = $country_configuration_repository;
		$this->order_service                    = $order_service;
		$this->store_context                    = $store_context;
	}

	/**
	 * Get store id for current context
	 *
	 * @throws Throwable
	 */
	public function send_delivery_report_for_current_store(): void {
		$store_id = $this->configuration->get_store_id();
		$this->store_context::doWithStore( $store_id, array( $this, 'send_delivery_report' ) );
	}


	/**
	 * Send the delivery report
	 * 
	 * @throws Throwable
	 */
	public function send_delivery_report(): void {

		$report_order_ids     = array_map( 'strval', $this->shop_order_service->getReportOrderIds( 0, -1 ) );
		$statistics_order_ids = null;
		$statistical_data     = $this->statistical_data_repository->getStatisticalData();
		if ( $statistical_data && $statistical_data->isSendStatisticalData() ) {
			$statistics_order_ids = array_map( 'strval', $this->shop_order_service->getStatisticsOrderIds( 0, -1 ) );
		}

		$merchantId           = null;
		$countryConfiguration = $this->country_configuration_repository->getCountryConfiguration();
		if ( isset( $countryConfiguration[0] ) ) {
			$merchantId = $countryConfiguration[0]->getMerchantId();
		}

		if ( ( empty( $report_order_ids ) && empty( $statistics_order_ids ) ) || ! $merchantId ) {
			return;
		}

		$task = new OrderReportTask( $merchantId, $report_order_ids, $statistics_order_ids );
		$task->execute();

		// Mark orders as reported.
		foreach ( $report_order_ids as $order_id ) {
			$order = wc_get_order( (int) $order_id );
			if ( ! $order instanceof WC_Order ) {
				continue;
			}
			$this->order_service->set_as_sent_to_sequra( $order );
		}
	}
}
