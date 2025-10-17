<?php
/**
 * Order Controller
 *
 * @package    SeQura/WC/NoAddress
 */

namespace SeQura\WC\NoAddress\Controller\Hooks\Order;

if ( ! class_exists( 'SeQura\WC\Controllers\Controller' ) || ! class_exists( 'SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Options' ) ) {
	return;
}

use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Options;
use SeQura\WC\Controllers\Controller;

/**
 * Order Controller
 */
class Order_Controller extends Controller implements Interface_Order_Controller {

	/**
	 * Add no address to the merchant options.
	 * 
	 * @param ?Options $options 
	 * @return ?Options
	 */
	public function add_no_address( $options ) {
		
		if ( ! class_exists( 'SeQura\WC\Core\Extension\BusinessLogic\Domain\Order\Models\OrderRequest\Options' ) ) {
			$this->logger->log_warning(
				'SeQura\WC\Core\Extension\BusinessLogic\Domain\Order\Models\OrderRequest\Options not found. The addon may not be compatible with the current version of the plugin.',
				__FUNCTION__,
				__CLASS__
			);
			return $options;
		}
		if ( ! class_exists( 'SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Options' ) ) {
			$this->logger->log_warning(
				'SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Options not found. The addon may not be compatible with the current version of the plugin.',
				__FUNCTION__,
				__CLASS__
			);
			return $options;
		}

		$is_instance_of_options = $options instanceof \SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Options;
		if ( null !== $options && ! $is_instance_of_options ) {
			$this->logger->log_warning(
				'The options parameter is not an instance of SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Options',
				__FUNCTION__,
				__CLASS__
			);
			return $options;
		}
		
		return new Options(
			$is_instance_of_options ? $options->getHasJquery() : null,
			$is_instance_of_options ? $options->getUsesShippedCart() : null,
			true, // addressesMayBeMissing.
			$is_instance_of_options ? $options->getImmutableCustomerData() : null,
			$is_instance_of_options ? $options->getDesiredFirstChargeOn() : null
		);
	}
}
