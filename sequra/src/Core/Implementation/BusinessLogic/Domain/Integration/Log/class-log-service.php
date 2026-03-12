<?php
/**
 * Log Service implementation
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Implementation\BusinessLogic\Domain\Integration\Log;

use SeQura\Core\BusinessLogic\Domain\Integration\Log\LogServiceInterface;
use SeQura\Core\BusinessLogic\Domain\Log\Model\Log;
use SeQura\Core\BusinessLogic\Domain\Multistore\StoreContext;
use SeQura\WC\Services\Log\Interface_Logger_Service;

/**
 * Log Service implementation
 */
class Log_Service implements LogServiceInterface {

	/**
	 * Logger
	 * 
	 * @var Interface_Logger_Service
	 */
	private $logger;

	/**
	 * Store context
	 * 
	 * @var StoreContext
	 */
	private $store_context;
	
	/**
	 * Constructor.
	 *
	 * @param Interface_Logger_Service $logger         The logger service.
	 * @param StoreContext $store_context The store context.
	 */
	public function __construct( 
		Interface_Logger_Service $logger,
		StoreContext $store_context
	) {
		$this->logger        = $logger;
		$this->store_context = $store_context;
	}

	/**
	 * Gets the log model.
	 *
	 * @return Log
	 */
	public function getLog(): Log {
		return new Log( $this->logger->get_content( $this->store_context->getStoreId() ) );
	}

	/**
	 * Removes/clears all log content.
	 *
	 * @return void
	 */
	public function removeLog(): void {
		$this->logger->clear( $this->store_context->getStoreId() );
	}
}
