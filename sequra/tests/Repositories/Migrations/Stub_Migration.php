<?php
/**
 * Concrete migration stub used by tests.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Repositories\Migrations;

use Exception;
use SeQura\WC\Repositories\Migrations\Migration;

/**
 * Concrete migration stub used by tests.
 */
class Stub_Migration extends Migration {

	/**
	 * Whether execute() should throw an exception.
	 *
	 * @var bool
	 */
	public $should_throw = false;

	/**
	 * Whether execute() was called.
	 *
	 * @var bool
	 */
	public $executed = false;

	/**
	 * Get the plugin version when the changes were made.
	 */
	public function get_version(): string {
		return '99.0.0';
	}

	/**
	 * Execute the migration.
	 *
	 * @throws Exception If $should_throw is true.
	 */
	protected function execute(): void {
		$this->executed = true;
		if ( $this->should_throw ) {
			throw new Exception( 'Migration failed' );
		}
	}
}
