<?php
/**
 * Platform Provider Interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Platform;

use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Platform;

/**
 * Provide the current platform
 */
interface Interface_Platform_Provider {

	/**
	 * Get the current platform
	 */
	public function get(): Platform;
}
