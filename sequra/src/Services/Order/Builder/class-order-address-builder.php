<?php
/**
 * Order Address Builder Interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Order\Builder;

use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Address;
use SeQura\WC\Services\Shopper\Interface_Shopper_Service;
use WC_Order;

/**
 * Order Address Builder Interface
 */
class Order_Address_Builder implements Interface_Order_Address_Builder {

	/**
	 * Shopper service
	 * 
	 * @var Interface_Shopper_Service
	 */
	private $shopper_service;

	/**
	 * Constructor
	 */
	public function __construct(
		Interface_Shopper_Service $shopper_service
	) {
		$this->shopper_service = $shopper_service;
	}

	/**
	 * Get address
	 *
	 * @param ?string $fallback_country Country used when the address itself resolves none (e.g. address-less service flows), so the solicitation still carries a country.
	 */
	public function build( ?WC_Order $order, bool $is_delivery, ?string $fallback_country = null ): Address {
		$country = $this->shopper_service->get_country( $order, $is_delivery );
		if ( ! $country ) {
			$country = (string) $fallback_country;
		}
		return new Address(
			$this->shopper_service->get_company( $order, $is_delivery ),
			$this->shopper_service->get_address_1( $order, $is_delivery ),
			$this->shopper_service->get_address_2( $order, $is_delivery ),
			$this->shopper_service->get_postcode( $order, $is_delivery ),
			$this->shopper_service->get_city( $order, $is_delivery ),
			$country,
			$this->shopper_service->get_first_name( $order, $is_delivery ),
			$this->shopper_service->get_last_name( $order, $is_delivery ),
			null, // phone.
			$this->shopper_service->get_phone( $order, $is_delivery ), // mobile phone.
			$this->shopper_service->get_state( $order, $is_delivery ),
			$order ? $order->get_customer_note( 'edit' ) : null, // extra.
			$this->shopper_service->get_vat( $order, $is_delivery )
		);
	}
}
