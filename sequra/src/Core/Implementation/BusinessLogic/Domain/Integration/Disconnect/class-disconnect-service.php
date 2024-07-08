<?php
/**
 * Implementation of the Disconnect service.
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Implementation\BusinessLogic\Domain\Integration\Disconnect;

use SeQura\Core\BusinessLogic\Domain\Integration\Disconnect\DisconnectServiceInterface;
use SeQura\WC\Repositories\Interface_Deletable_Repository;

/**
 * Implementation of the Disconnect service.
 */
class Disconnect_Service implements DisconnectServiceInterface {
	
	/**
	 * The repositories.
	 *
	 * @var Interface_Deletable_Repository[]
	 */
	private $repositories;

	/**
	 * Constructor.
	 * 
	 * @param Interface_Deletable_Repository[] $repositories The repositories.
	 */
	public function __construct( array $repositories ) {
		$this->repositories = $repositories;
	}

	/**
	 * Disconnect integration from store.
	 */
	public function disconnect(): void {
		foreach ( $this->repositories as $repository ) {
			$repository->delete_all();
		}
	}
}
