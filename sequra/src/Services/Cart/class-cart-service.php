<?php
/**
 * Cart service
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Cart;

use DateTime;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Item\HandlingItem;
use SeQura\WC\Dto\Cart_Info;
use SeQura\WC\Dto\Discount_Item;
use SeQura\WC\Dto\Fee_Item;
use SeQura\WC\Dto\Handling_Item;
use SeQura\WC\Services\Core\Configuration;
use SeQura\WC\Services\Order\Interface_Order_Service;
use SeQura\WC\Services\Pricing\Interface_Pricing_Service;
use SeQura\WC\Services\Product\Interface_Product_Service;
use WC_Cart_Fees;
use WC_Order;

/**
 * Handle use cases related to cart
 */
class Cart_Service implements Interface_Cart_Service {

	/**
	 * Product service
	 *
	 * @var Interface_Product_Service
	 */
	private $product_service;

	/**
	 * Configuration
	 *
	 * @var Configuration
	 */
	private $configuration;

	/**
	 * Pricing service
	 *
	 * @var Interface_Pricing_Service
	 */
	private $pricing_service;

	/**
	 * Order service
	 *
	 * @var Interface_Order_Service
	 */
	private $order_service;
	
	/**
	 * Constructor
	 */
	public function __construct( 
		Interface_Product_Service $product_service,
		Configuration $configuration,
		Interface_Pricing_Service $pricing_service,
		Interface_Order_Service $order_service
	) {
		$this->product_service = $product_service;
		$this->configuration   = $configuration;
		$this->pricing_service = $pricing_service;
		$this->order_service   = $order_service;
	}

	/**
	 * Get closest desired first charge date from cart items
	 */
	public function get_desired_first_charge_on(): ?string {
		if ( null === WC()->cart ) {
			return null;
		}

		$first_charge_on = null;
		foreach ( WC()->cart->get_cart_contents() as $cart_item ) {
			$product_id = $this->get_product_id_from_item( $cart_item );
			$date       = $this->product_service->get_desired_first_charge_date( $product_id );
			if ( $date ) {
				$formatted_date  = $date->format( DateTime::ATOM );
				$first_charge_on = $first_charge_on ? min( $first_charge_on, $formatted_date ) : $formatted_date;
			}
		}
		return $first_charge_on;
	}

	/**
	 * Get seQura cart info data from session. If not exists, then initialize it.
	 */
	public function get_cart_info_from_session(): Cart_Info {
		$raw_data = WC()->session->get( 'sequra_cart_info', null );
		if ( $raw_data ) {
			return new Cart_Info( $raw_data );
		}
		$cart_info = new Cart_Info();
		WC()->session->set( 'sequra_cart_info', $cart_info->to_array() );
		return $cart_info;
	}

	/**
	 * Get product items as an associative array
	 *
	 * @return array<string, mixed>
	 */
	public function get_product_items(): array {
		if ( null === WC()->cart ) {
			return array();
		}
		$items = array();
		foreach ( WC()->cart->get_cart_contents() as $cart_item ) {
			$product_id = $this->get_product_id_from_item( $cart_item );
			$product    = $this->product_service->get_product_instance( $product_id );
			if ( ! $product ) {
				continue;
			}

			$item = array();
			if (
				$this->configuration->is_enabled_for_services()
				&& $this->product_service->is_service( $product )
			) {
				$item['type'] = 'service';
				
				/**
				* Filter the service end date.
				*
				* @since 2.0.0
				*/
				$service_end_date = apply_filters(
					'woocommerce_sequra_add_service_end_date',
					$this->product_service->get_service_end_date( $product->get_parent_id() ? $product->get_parent_id() : $product->get_id() ),
					$product,
					$cart_item
				);
				
				if ( 0 === strpos( $service_end_date, 'P' ) ) {
					$item['ends_in'] = $service_end_date;
				} else {
					$item['ends_on'] = $service_end_date;
				}
			} else {
				$item['type'] = 'product';
			}
			$item['reference'] = $product->get_sku() ? $product->get_sku() : $product->get_id();
			$item['name']      = wp_strip_all_tags( $product->get_title() );
			$item['quantity']  = isset( $cart_item['quantity'] ) ? (int) $cart_item['quantity'] : 1;
			
			$total_price            = $cart_item['line_subtotal'] + $cart_item['line_subtotal_tax'];
			$item['total_with_tax'] = $this->pricing_service->to_cents( $total_price );
			$item['price_with_tax'] = $this->pricing_service->to_cents( $total_price / $item['quantity'] );
			$item['downloadable']   = $product->is_downloadable();

			// OPTIONAL.
			$item['description'] = $product->get_description();
			$item['product_id']  = $product->get_id();
			$item['url']         = (string) get_permalink( $product->get_id() );
			$item['category']    = wc_get_product_category_list( $product->get_id() );
			$items[]             = $item;
		}
		return $items;
	}

	/**
	 * Get handling items as an associative array
	 *
	 * @return array<string, mixed>
	 */
	public function get_handling_items( ?WC_Order $order = null ): array {
		$shipping_total = 0;
		$items          = array();

		if ( ! $order && null !== WC()->cart ) {
			$shipping_total = (float) WC()->cart->shipping_total + (float) WC()->cart->shipping_tax_total;
		} elseif ( $order ) {
			$shipping_total = (float) $order->get_shipping_total() + (float) $order->get_shipping_tax();
		}
		
		if ( $shipping_total ) {
			$items[] = ( new Handling_Item( $this->pricing_service->to_cents( $shipping_total ) ) )->to_array();
		}
		
		return $items;
	}
	
	/**
	 * Get discount items as an associative array
	 *
	 * @return array<string, mixed>
	 */
	public function get_discount_items( ?WC_Order $order = null ): array {
		$items = array();
		if ( ! $order && null !== WC()->cart ) {
			$cart = WC()->cart;
			foreach ( $cart->coupon_discount_amounts as $key => $val ) {
				$amount  = (float) $val + (float) $cart->coupon_discount_tax_amounts[ $key ];
				$amount  = $this->pricing_service->to_cents( $amount );
				$items[] = ( new Discount_Item( $key, $amount ) )->to_array();
			}
		} elseif ( $order ) {
			foreach ( $order->get_items( 'coupon' ) as $key => $val ) {
				$amount  = (float) $val['discount_amount'] + (float) $val['discount_amount_tax'];
				$amount  = $this->pricing_service->to_cents( $amount );
				$items[] = ( new Discount_Item( $val['name'], $amount ) )->to_array();
			}
		}
		return $items;
	}

	/**
	 * Get extra items as an associative array
	 *
	 * @return array<string, mixed>
	 */
	public function get_extra_items( ?WC_Order $order = null ): array {
		$items = array();
		if ( ! $order && null !== WC()->cart ) {
			/**
			 * Fee cart object. Must contain at least props: name, amount, tax
			 *
			 * @var object $fee
			 */
			foreach ( WC()->cart->get_fees() as $fee ) {
				$total_with_tax_in_cents = (float) $fee->amount + ( isset( $fee->tax ) ? (float) $fee->tax : 0 );
				$items[]                 = ( new Fee_Item( 
					$fee->name, 
					$this->pricing_service->to_cents( $total_with_tax_in_cents )
				) )->to_array();
			}
		} elseif ( $order ) {
			foreach ( $order->get_fees() as $fee ) {
				$total_with_tax_in_cents = (float) $fee->get_total( 'edit' ) + (float) $fee->get_total_tax( 'edit' );
				$items[]                 = ( new Fee_Item( 
					$fee->get_name( 'edit' ), 
					$this->pricing_service->to_cents( $total_with_tax_in_cents )
				) )->to_array();
			}
		}
		return $items;
	}

	/**
	 * Get product ID from cart item
	 *
	 * @param array<string, mixed> $cart_item Cart item
	 */
	private function get_product_id_from_item( $cart_item ): int {
		return isset( $cart_item['product_id'] ) ? (int) $cart_item['product_id'] : 0;
	}
}
