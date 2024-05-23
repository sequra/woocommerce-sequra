<?php
/**
 * Wrapper to ease the read and write of configuration values.
 * Delegate to the ConfigurationManager instance to access the data in the database.
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Services\Core;

use SeQura\Core\BusinessLogic\Domain\Integration\Disconnect\DisconnectServiceInterface;
use SeQura\WC\Repositories\Interface_Deletable_Repository;

/**
 * Wrapper to ease the read and write of configuration values.
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
	public function __construct( $repositories ) {
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
