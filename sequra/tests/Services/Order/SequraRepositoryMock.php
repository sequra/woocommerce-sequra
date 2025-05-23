<?php
/**
 * Tests for the Async_Process_Controller class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Services\Order;

use SeQura\Core\BusinessLogic\Domain\Order\RepositoryContracts\SeQuraOrderRepositoryInterface;
use SeQura\WC\Repositories\Interface_Table_Migration_Repository;

abstract class SequraRepositoryMock implements SeQuraOrderRepositoryInterface, Interface_Table_Migration_Repository {
}
