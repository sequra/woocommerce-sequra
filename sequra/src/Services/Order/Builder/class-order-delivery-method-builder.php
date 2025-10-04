<?php
/**
 * Order Delivery Method Builder Interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Order;

use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\DeliveryMethod;
use WC_Order;

/**
 * Order Delivery Method Builder Interface
 */
class Order_Delivery_Method_Builder implements Interface_Order_Delivery_Method_Builder {

	/**
	 * Get Delivery Method
	 */
	public function build( ?WC_Order $order ): DeliveryMethod {

		if ( ! $order ) {
			$session          = WC()->session;
			$shipping_methods = $session ? WC()->session->chosen_shipping_methods : array();
			
			if ( ! $shipping_methods || empty( WC()->shipping->get_packages() ) ) {
				return new DeliveryMethod( 'default', null, 'default' );
			}
			$package         = current( WC()->shipping->get_packages() );
			$shipping_method = current( $shipping_methods );
			
			if ( ! isset( $package['rates'][ $shipping_method ] ) ) {
				return new DeliveryMethod( 'default', null, 'default' );
			}
			
			$rate = $package['rates'][ $shipping_method ];
			return new DeliveryMethod( $rate->label, null, $rate->id );
		}
		
		$shipping_method = current( $order->get_shipping_methods() );

		return new DeliveryMethod(
			$shipping_method['name'] ?? 'default',
			null,
			$shipping_method['method_id'] ?? 'default'
		);
	}
}
