<?php
/**
 * Cart service
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Cart;

use DateTime;
use SeQura\WC\Dto\Cart_Info;
use SeQura\WC\Dto\Discount_Item;
use SeQura\WC\Dto\Fee_Item;
use SeQura\WC\Dto\Handling_Item;
use SeQura\WC\Services\Core\Configuration;
use SeQura\WC\Services\Pricing\Interface_Pricing_Service;
use SeQura\WC\Services\Product\Interface_Product_Service;
use WC_Order;

/**
 * Handle use cases related to cart
 */
class Cart_Service implements Interface_Cart_Service {

	private const SESSION_CART_INFO = 'sequra_cart_info';

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
	 * Constructor
	 */
	public function __construct( 
		Interface_Product_Service $product_service,
		Configuration $configuration,
		Interface_Pricing_Service $pricing_service
	) {
		$this->product_service = $product_service;
		$this->configuration   = $configuration;
		$this->pricing_service = $pricing_service;
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
		$_session = WC()->session;
		$raw_data = $_session->get( self::SESSION_CART_INFO, null );
		if ( $raw_data ) {
			return Cart_Info::from_array( $raw_data );
		}
		$cart_info = new Cart_Info();
		$_session->set( self::SESSION_CART_INFO, $cart_info->to_array() );
		return $cart_info;
	}

	/**
	 * Attempt to clear seQura cart info data from session. 
	 */
	public function clear_cart_info_from_session(): void {
		if ( WC()->session ) {
			WC()->session->set( self::SESSION_CART_INFO, null );
		}
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

	/**
	 * Check if cart is eligible for service sale
	 */
	private function is_eligible_for_service_sale(): bool {
		if ( ! WC()->cart ) {
			return false;
		}
		$eligible       = false;
		$services_count = 0;
		foreach ( WC()->cart->cart_contents as $values ) {
			if ( $this->product_service->is_service( $values['product_id'] ) ) {
				$services_count += $values['quantity'];
				$eligible        = ( 1 === $services_count );
			}
		}
		/**
		 * Filter if cart is eligible for service sale
		 *
		 * @since 2.0.0
		 */
		return apply_filters( 'woocommerce_cart_is_elegible_for_service_sale', $eligible );
	}

	/**
	 * Check if cart is eligible for product sale
	 */
	private function is_eligible_for_product_sale(): bool {
		global $wp;
		if ( ! WC()->cart ) {
			return false;
		}
		$eligible = true;
		// Only reject if all products are virtual (don't need shipping).
		if ( isset( $wp->query_vars['order-pay'] ) ) { // if paying an order.
			$order = wc_get_order( $wp->query_vars['order-pay'] );
			if ( ! $order->needs_shipping_address() ) {
				// TODO: process outside this function
				// $this->logger->log_debug( 'Order doesn\'t need shipping address seQura will not be offered.', __FUNCTION__, __CLASS__ );
				$eligible = false;
			}
		} elseif ( ! WC()->cart->needs_shipping() ) { // If paying cart.
			// TODO: process outside this function
			// $this->logger->log_debug( 'Order doesn\'t need shipping seQura will not be offered.', __FUNCTION__, __CLASS__ );
			$eligible = false;
		}
		/**
		 * Filter if cart is eligible for product sale
		 *
		 * @since 2.0.0
		 * @deprecated 3.0.0 Use woocommerce_cart_is_eligible_for_product_sale instead
		 */
		$eligible = apply_filters( 'woocommerce_cart_is_elegible_for_product_sale', $eligible );

		/**
		 * Filter if cart is eligible for product sale
		 *
		 * @since 3.0.0
		 */
		return apply_filters( 'woocommerce_cart_is_eligible_for_product_sale', $eligible );
	}

	/**
	 * Check if conditions are met for showing seQura in checkout
	 */
	public function is_available_in_checkout(): bool {
		$return = ! empty( WC()->cart ) && $this->configuration->is_available_for_ip();
		if ( $return ) {
			$is_enabled_for_services = $this->configuration->is_enabled_for_services();
			if ( $is_enabled_for_services && ! $this->is_eligible_for_service_sale() ) {
					// $this->logger->log_info( 'Order is not eligible for service sale.', __FUNCTION__, __CLASS__ );
					$return = false;
			}
			if ( ! $is_enabled_for_services && ! $this->is_eligible_for_product_sale() ) {
				// $this->logger->log_info( 'Order is not eligible for for product sale.', __FUNCTION__, __CLASS__ );
				$return = false;
			}
			if ( $return ) {
				foreach ( WC()->cart->get_cart_contents() as $values ) {
					if ( $this->product_service->is_banned( $values['product_id'] ) ) {
						// TODO: process outside this function
						// $this->logger->log_debug( 'Banned product in the cart seQura will not be offered. Product Id :' . $values['product_id'], __FUNCTION__, __CLASS__ );
						$return = false;
						break;
					}
				}
			}
		}
		/**
		 * Filter seQura availability at checkout
		 *
		 * @since 2.0.0
		 */
		return apply_filters( 'woocommerce_cart_sq_is_available_in_checkout', $return );
	}
}
