<?php
/**
 * Extension of the StatisticalDataService class.
 *
 * @package SeQura\WC\Core\Extension\BusinessLogic\Domain\StatisticalData\Services
 */

namespace SeQura\WC\Core\Extension\BusinessLogic\Domain\StatisticalData\Services;

use SeQura\Core\BusinessLogic\Domain\SendReport\RepositoryContracts\SendReportRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\StatisticalData\RepositoryContracts\StatisticalDataRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\StatisticalData\Services\StatisticalDataService;
use SeQura\Core\BusinessLogic\Domain\Stores\Services\StoreService;
use SeQura\Core\Infrastructure\Utility\TimeProvider;

//phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

/**
 * Class StatisticalDataService
 *
 * @package SeQura\WC\Core\Extension\BusinessLogic\Domain\StatisticalData\Services
 */
class Statistical_Data_Service extends StatisticalDataService {

	/**
	 * Store service.
	 *
	 * @var StoreService
	 */
	private $store_service;

	/**
	 * Constructor
	 */
	public function __construct(
		StatisticalDataRepositoryInterface $statistical_data_repository,
		SendReportRepositoryInterface $send_report_repository,
		TimeProvider $time_provider,
		StoreService $store_service
	) {
		parent::__construct(
			$statistical_data_repository,
			$send_report_repository,
			$time_provider
		);
		$this->store_service = $store_service;
	}

	// private const SCHEDULE_TIME = '4 am';

	// /**
	// * Calls the repository to save statistical data to the database.
	// */
	// public function saveStatisticalData( StatisticalData $statisticalData ): void {
	// $this->statisticalDataRepository->setStatisticalData( $statisticalData );
	// }

	/**
	 * Get store contexts for sending report
	 */
	public function getContextsForSendingReport(): array {
		// This prevents the report from being sent at any time other than 4 am.
		// if ( $this->timeProvider->getCurrentLocalTime()->getTimestamp()
		// !== strtotime( self::SCHEDULE_TIME ) ) {
		// return array();
		// }

		return $this->store_service->getConnectedStores();
	}
}
