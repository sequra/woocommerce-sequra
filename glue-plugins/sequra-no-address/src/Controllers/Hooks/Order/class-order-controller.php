<?php
/**
 * Order Controller
 *
 * @package    SeQura/WC/NoAddress
 */

namespace SeQura\WC\NoAddress\Controller\Hooks\Order;

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
	public function add_no_address($options){
		if(!class_exists('SeQura\WC\Core\Extension\BusinessLogic\Domain\Order\Models\OrderRequest\Options')){
			$this->logger->log_error(
				'SeQura\WC\Core\Extension\BusinessLogic\Domain\Order\Models\OrderRequest\Options not found. The addon may not be compatible with the current version of the plugin.',
				__FUNCTION__,
				__CLASS__
			);
			return $options;
		}
		
		return new \SeQura\WC\Core\Extension\BusinessLogic\Domain\Order\Models\OrderRequest\Options(
			$options->getHasJquery(),
			$options->getUsesShippedCart(),
			true, // addressesMayBeMissing
			$options->getImmutableCustomerData(),
			!$options instanceof \SeQura\WC\Core\Extension\BusinessLogic\Domain\Order\Models\OrderRequest\Options ? null : $options->get_desired_first_charge_on()
		);
	}
}
