<?php
/**
 * Implementation of OrderReportServiceInterface
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Implementation\BusinessLogic\Domain\Integration\OrderReport;

use SeQura\Core\BusinessLogic\Domain\Integration\OrderReport\OrderReportServiceInterface;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Platform;
use SeQura\WC\Core\Extension\Infrastructure\Configuration\Configuration;

/**
 * Implementation of the OrderReportServiceInterface.
 */
class Order_Report_Service implements OrderReportServiceInterface {

	/**
	 * Platform instance.
	 *
	 * @var Platform
	 */
	private $platform;

	/**
	 * Constructor.
	 *
	 * @param Configuration $configuration
	 * @param string[] $woo_data
	 * @param string[] $env_data
	 */
	public function __construct( Configuration $configuration, array $wc_data, array $env_data ) {
		$this->platform = new Platform(
			$configuration->getIntegrationName(),
			$wc_data['Version'],
			$env_data['uname'],
			$env_data['db_name'],
			$env_data['db_version'],
			$configuration->get_module_version(),
			$env_data['php_version']
		);
	}

	/**
	 * Returns reports for all orders made by SeQura payment methods in the last 24 hours.
	 *
	 * @param string[] $orderIds
	 *
	 * @return OrderReport[]
	 */
	public function getOrderReports( array $orderIds ): array { 
		// TODO: implement this?
		return array();
	}

	/**
	 * Returns statistics for all shop orders created in the last 7 days.
	 *
	 * @param string[] $orderIds
	 *
	 * @return OrderStatistics[]
	 */
	public function getOrderStatistics( array $orderIds ): array {
		$statics = array();
		// TODO: implement
		return $statics;
	}

	/**
	 * Returns the Platform instance.
	 *
	 * @return Platform
	 */
	public function getPlatform(): Platform {
		return $this->platform;
	}
}
