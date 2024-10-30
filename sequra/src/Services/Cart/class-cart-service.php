<?php
/**
 * Cart service
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Cart;

use DateTime;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Item\DiscountItem;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Item\HandlingItem;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Item\OtherPaymentItem;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Item\ProductItem;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Item\ServiceItem;
use SeQura\Core\Infrastructure\Logger\LogContextData;
use SeQura\WC\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Item\Registration_Item;
use SeQura\WC\Core\Extension\Infrastructure\Configuration\Configuration;
use SeQura\WC\Dto\Cart_Info;
use SeQura\WC\Services\Interface_Logger_Service;
use SeQura\WC\Services\Pricing\Interface_Pricing_Service;
use SeQura\WC\Services\Product\Interface_Product_Service;
use WC_Coupon;
use WC_Order;
use WC_Order_Item_Coupon;
use WC_Order_Item_Fee;
use WC_Order_Item_Product;
use WC_Order_Refund;
use WC_Product;

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
	 * Logger
	 *
	 * @var Interface_Logger_Service
	 */
	private $logger;
	
	/**
	 * Constructor
	 */
	public function __construct( 
		Interface_Product_Service $product_service,
		Configuration $configuration,
		Interface_Pricing_Service $pricing_service,
		Interface_Logger_Service $logger
	) {
		$this->product_service = $product_service;
		$this->configuration   = $configuration;
		$this->pricing_service = $pricing_service;
		$this->logger          = $logger;
	}

	/**
	 * Get refund items
	 *
	 * @return OtherPaymentItem[] 
	 */
	public function get_refund_items( WC_Order $order = null ): array {
		$items = array();
		/**
		 * Order refund
		 *
		 * @var WC_Order_Refund $refund
		 */
		foreach ( $order->get_refunds() as $refund ) {
			$items[] = $this->create_refund_item( $refund->get_id(), $refund->get_amount( 'edit' ) );
		}
		return $items;
	}

	/**
	 * Create refund item instance
	 */
	public function create_refund_item( ?int $id, float $amount ): OtherPaymentItem {
		$cents = $this->pricing_service->to_cents( $amount );
		if ( ! $id ) {
			$id = $cents;
		}
		return new OtherPaymentItem( 'r_' . $id, 'Refund', -1 * $cents );
	}

	/**
	 * Get closest desired first charge date from cart items
	 */
	private function update_first_charge_on( int $product_id, ?string &$first_charge_on ) {
		$date = $this->product_service->get_desired_first_charge_date( $product_id );
		if ( $date instanceof DateTime ) {
			$formatted_date  = $date->format( DateTime::ATOM );
			$first_charge_on = $first_charge_on ? min( $first_charge_on, $formatted_date ) : $formatted_date;
		}
	}

	/**
	 * Get closest desired first charge date from cart items
	 */
	public function get_desired_first_charge_on( ?WC_Order $order = null ): ?string {
		$first_charge_on = null;
		if ( ! $order && null !== WC()->cart ) {
			foreach ( WC()->cart->get_cart_contents() as $cart_item ) {
				$product_id = $this->get_product_id_from_item( $cart_item );
				$this->update_first_charge_on( $product_id, $first_charge_on );
			}
		} elseif ( $order ) {
			/**
			 * Order item
			 *
			 * @var WC_Order_Item_Product $item
			 */
			foreach ( $order->get_items() as $item ) {
				if ( ! $item instanceof WC_Order_Item_Product ) {
					continue;
				}
				$product = $item->get_product();
				if ( $product ) {
					$this->update_first_charge_on( $product->get_id(), $first_charge_on );
				}
			}
		}
		return $first_charge_on;
	}

	/**
	 * Get seQura cart info data from session. If not exists, then initialize it.
	 */
	public function get_cart_info_from_session(): ?Cart_Info {
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
	 * Get registration item instance
	 */
	private function get_registration_item( WC_Product $product, int $qty ): ?Registration_Item {
		$registration_amount = $this->product_service->get_registration_amount( $product, true );
		if ( $registration_amount <= 0 ) {
			return null;
		}

		$ref  = $product->get_sku() ? $product->get_sku() : $product->get_id();
		$name = wp_strip_all_tags( $product->get_title() );

		return new Registration_Item(
			"$ref-reg",
			"Reg. $name",
			$registration_amount * $qty
		);
	}
	/**
	 * Get item instance
	 *
	 * @param mixed $item The product item.
	 * @return ProductItem|ServiceItem
	 */
	private function get_item( WC_Product $product, float $total_price, ?Registration_Item $reg_item, int $qty, $item ) {
		$ref  = $product->get_sku() ? $product->get_sku() : $product->get_id();
		$name = wp_strip_all_tags( $product->get_title() );
		if ( $this->configuration->is_enabled_for_services() && $this->product_service->is_service( $product ) ) {
			/**
			* Filter the service end date.
			*
			* @since 2.0.0
			*/
			$service_end_date = apply_filters(
				'woocommerce_sequra_add_service_end_date',
				$this->product_service->get_service_end_date( $product->get_parent_id() ? $product->get_parent_id() : $product->get_id() ),
				$product,
				$item
			);

			$is_duration = 0 === strpos( $service_end_date, 'P' );

			return new ServiceItem(
				$ref,
				$name,
				$this->pricing_service->to_cents( $total_price / $qty ),
				$qty,
				$product->is_downloadable(),
				$this->pricing_service->to_cents( $total_price ) - ( $reg_item ? $reg_item->getTotalWithTax() : 0 ),
				! $is_duration ? $service_end_date : null,
				$is_duration ? $service_end_date : null,
				null, // supplier.
				null // rendered.
			);
		} 
		return new ProductItem(
			$ref,
			$name,
			$this->pricing_service->to_cents( $total_price / $qty ),
			$qty,
			$this->pricing_service->to_cents( $total_price ),
			$product->is_downloadable(),
			null, // perishable.
			null, // personalized.
			null, // restockable.
			wc_get_product_category_list( $product->get_id() ),
			$product->get_description(),
			null, // manufacturer.
			null, // supplier.
			$product->get_id(),
			$product->get_permalink(),
			null // tracking reference.
		);
	}

	/**
	 * Get items in cart
	 *
	 * @return array<ProductItem|ServiceItem|RegistrationItem>
	 */
	public function get_items( ?WC_Order $order ): array {

		$items = array();
		
		if ( ! $order && null !== WC()->cart ) {
			// Cart items.
			foreach ( WC()->cart->get_cart_contents() as $cart_item ) {
				$product = $this->product_service->get_product_instance( $this->get_product_id_from_item( $cart_item ) );
				if ( ! $product ) {
					continue;
				}

				// Registration item.
				$reg_item = $this->get_registration_item( $product, (int) $cart_item['quantity'] );
				if ( $reg_item ) {
					$items[] = $reg_item;
				}

				$items[] = $this->get_item(
					$product, 
					(float) $cart_item['line_subtotal'] + (float) $cart_item['line_subtotal_tax'],
					$reg_item, 
					(int) $cart_item['quantity'], 
					$cart_item
				);
			}
		} elseif ( $order ) {
			/**
			 * Order item
			 *
			 * @var WC_Order_Item_Product $item
			 */
			foreach ( $order->get_items() as $item ) {
				if ( ! $item instanceof WC_Order_Item_Product ) {
					continue;
				}
				$product = $item->get_product();
				if ( ! $product ) {
					continue;
				}

				// Registration item.
				$reg_item = $this->get_registration_item( $product, (int) $item->get_quantity( 'edit' ) );
				if ( $reg_item ) {
					$items[] = $reg_item;
				}

				$items[] = $this->get_item(
					$product, 
					(float) $item->get_subtotal( 'edit' ) + (float) $item->get_subtotal_tax( 'edit' ),
					$reg_item, 
					(int) $item->get_quantity( 'edit' ), 
					$item
				);
			}
		}
		
		/**
		 * TODO: Document this filter
		 * Filter cart items. Must return an array of ProductItem|ServiceItem
		 *
		 * @since 3.0.0
		 */
		return apply_filters( 'sequra_cart_service_get_items', $items, $order );
	}

	/**
	 * Get products in cart
	 *
	 * @return array<WC_Product>
	 */
	public function get_products( ?WC_Order $order ): array {
		$items = array();
		
		if ( ! $order && null !== WC()->cart ) {
			// Cart items.
			foreach ( WC()->cart->get_cart_contents() as $cart_item ) {
				$product = $this->product_service->get_product_instance( $this->get_product_id_from_item( $cart_item ) );
				if ( ! $product ) {
					continue;
				}

				$items[] = $product;
			}
		} elseif ( $order ) {
			/**
			 * Order item
			 *
			 * @var WC_Order_Item_Product $item
			 */
			foreach ( $order->get_items() as $item ) {
				if ( ! $item instanceof WC_Order_Item_Product ) {
					continue;
				}
				$product = $item->get_product();
				if ( ! $product ) {
					continue;
				}

				$items[] = $product;
			}
		}

		return $items;
	}

	/**
	 * Get handling items
	 *
	 * @return HandlingItem[]
	 */
	public function get_handling_items( ?WC_Order $order = null ): array {
		$items                   = array();
		$shipping_total_with_tax = 0;
		
		if ( ! $order && null !== WC()->cart ) {
			$shipping_total_with_tax = (float) WC()->cart->shipping_total + (float) WC()->cart->shipping_tax_total;

			/**
			 * Handling fee. Must contain at least props: name, total
			 *
			 * @var object $fee
			 */
			foreach ( WC()->cart->get_fees() as $fee ) {
				$total_with_tax = (float) ( $fee->total ?? 0 );
				if ( $total_with_tax <= 0 ) {
					// Fees with negative total are discount items.
					continue;
				}

				$items[] = new HandlingItem(
					$fee->name ?? 'handling',
					esc_html__( 'Handling cost', 'sequra' ),
					$this->pricing_service->to_cents( $total_with_tax )
				);
			}
		} elseif ( $order ) {
			$shipping_total_with_tax = (float) $order->get_shipping_total() + (float) $order->get_shipping_tax();

			/**
			 * Handling fee order item
			 *
			 * @var WC_Order_Item_Fee $fee
			 */
			foreach ( $order->get_items( 'fee' ) as $fee ) {
				if ( ! $fee instanceof WC_Order_Item_Fee ) {
					continue;
				}

				$total_with_tax = (float) ( $fee->get_total() ?? 0 );
				if ( $total_with_tax <= 0 ) {
					// Fees with negative total are discount items.
					continue;
				}

				$items[] = new HandlingItem(
					$fee->get_name(),
					esc_html__( 'Handling cost', 'sequra' ),
					$this->pricing_service->to_cents( $total_with_tax )
				);
			}
		}
		
		if ( $shipping_total_with_tax ) {
			$items[] = new HandlingItem(
				'handling',
				esc_html__( 'Shipping cost', 'sequra' ),
				$this->pricing_service->to_cents( $shipping_total_with_tax )
			);
		}

		return $items;
	}
	
	/**
	 * Get discount items
	 *
	 * @return DiscountItem[]
	 */
	public function get_discount_items( ?WC_Order $order = null ): array {
		$items = array();
		if ( ! $order && null !== WC()->cart ) {
			$cart = WC()->cart;
			/**
			 * Coupon cart object.
			 *
			 * @var WC_Coupon $coupon
			 */
			foreach ( $cart->get_coupons() as $coupon ) {
				$items[] = new DiscountItem(
					$coupon->get_code(),
					esc_html__( 'Discount', 'sequra' ),
					-1 * $this->pricing_service->to_cents( 
						$cart->get_coupon_discount_amount( $coupon->get_code(), false ) 
					)
				);
			}

			/**
			 * Fee cart object. Must contain at least props: name, total
			 *
			 * @var object $fee
			 */
			foreach ( $cart->get_fees() as $fee ) {
				$total_with_tax = (float) ( $fee->total ?? 0 );
				if ( $total_with_tax >= 0 ) {
					// Fees with positive total are handling items.
					continue;
				}

				$items[] = new DiscountItem(
					$fee->name ?? 'discount',
					esc_html__( 'Discount', 'sequra' ),
					$this->pricing_service->to_cents( $total_with_tax )
				);
			}
		} elseif ( $order ) {
			/**
			 * Coupon order item
			 *
			 * @var WC_Order_Item_Coupon $coupon
			 */
			foreach ( $order->get_items( 'coupon' ) as $coupon ) {
				if ( ! $coupon instanceof WC_Order_Item_Coupon ) {
					continue;
				}

				$items[] = new DiscountItem(
					$coupon->get_code(),
					$coupon->get_name(),
					-1 * $this->pricing_service->to_cents(
						(float) $coupon->get_discount( 'edit' ) + (float) $coupon->get_discount_tax( 'edit' )
					)
				);
			}

			/**
			 * Fee order item
			 *
			 * @var WC_Order_Item_Fee $fee
			 */
			foreach ( $order->get_items( 'fee' ) as $fee ) {
				if ( ! $fee instanceof WC_Order_Item_Fee ) {
					continue;
				}

				$total_with_tax = (float) ( $fee->get_total() ?? 0 );
				if ( $total_with_tax >= 0 ) {
					// Fees with positive total are handling items.
					continue;
				}

				$items[] = new DiscountItem(
					$fee->get_name(),
					esc_html__( 'Discount', 'sequra' ),
					$this->pricing_service->to_cents( $total_with_tax )
				);
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
		return isset( $cart_item['product_id'] ) ? intval( $cart_item['product_id'] ) : 0;
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
		$eligible = true;
		if ( WC()->cart ) {
			global $wp;
			// Only reject if all products are virtual (don't need shipping).
			if ( isset( $wp->query_vars['order-pay'] ) ) { // if paying an order.
				/**
				 * Order
				 *
				 * @var WC_Order $order
				 */
				$order = wc_get_order( (int) $wp->query_vars['order-pay'] );
				if ( ! $order instanceof WC_Order || ! $order->needs_shipping_address() ) {
					$this->logger->log_debug( 'Order doesn\'t need shipping address seQura will not be offered.', __FUNCTION__, __CLASS__ );
					$eligible = false;
				}
			} elseif ( ! WC()->cart->needs_shipping() ) { // If paying cart.
				$this->logger->log_debug( 'Cart doesn\'t need shipping seQura will not be offered.', __FUNCTION__, __CLASS__ );
				$eligible = false;
			}
		} else {
			$eligible = false;
		}
		/**
		 * Filter if cart is eligible for product sale
		 *
		 * @since 2.0.0
		 * @deprecated 3.0.0 Use woocommerce_cart_is_eligible_for_product_sale instead
		 */
		$eligible = apply_filters_deprecated( 'woocommerce_cart_is_elegible_for_product_sale', array( $eligible ), '3.0.0', 'woocommerce_cart_is_eligible_for_product_sale' );

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
	public function is_available_in_checkout( ?WC_Order $order = null ): bool {
		$return = ! empty( WC()->cart ) && $this->configuration->is_available_for_ip();
		if ( ! $return ) {
			$this->logger->log_debug( 'seQura is not available for this IP.', __FUNCTION__, __CLASS__ );
		} else {
			$is_enabled_for_services = $this->configuration->is_enabled_for_services();
			if ( $is_enabled_for_services && ! $this->is_eligible_for_service_sale() ) {
					$this->logger->log_debug( 'Order is not eligible for service sale.', __FUNCTION__, __CLASS__ );
					$return = false;
			}
			if ( ! $is_enabled_for_services && ! $this->is_eligible_for_product_sale() ) {
				$this->logger->log_debug( 'Order is not eligible for for product sale.', __FUNCTION__, __CLASS__ );
				$return = false;
			}
			if ( $return ) {
				if ( ! $order instanceof WC_Order ) {
					foreach ( WC()->cart->get_cart_contents() as $values ) {
						if ( $this->product_service->is_banned( (int) $values['product_id'] ) ) {
							$this->logger->log_debug( 'Banned product in the cart. seQura will not be offered', __FUNCTION__, __CLASS__, array( new LogContextData( 'product_id', $values['product_id'] ) ) );
							$return = false;
						}

						/**
						 * Filter if item is available in checkout
						 * Can receive an array with the cart item values or the WC_Order_Item instance
						 *
						 * @since 3.0.0
						 * TODO: Document this hook
						 */
						$return = apply_filters( 'sequra_is_item_available_in_checkout', $return, $values );
						if ( ! $return ) {
							break;
						}
					}
				} else {
					// Order items.
					foreach ( $order->get_items() as $item ) {
						if ( method_exists( $item, 'get_product_id' ) ) {
							/**
							 * Define type for $item to prevent linting errors
							 *
							 * @var WC_Order_Item_Product $item
							 */
							$product_id = $item->get_product_id();
							if ( $this->product_service->is_banned( $product_id ) ) {
								$this->logger->log_debug( 'Banned product in the order. seQura will not be offered', __FUNCTION__, __CLASS__, array( new LogContextData( 'product_id', $product_id ) ) );
								$return = false;
							}
						}
						/**
						 * Filter if item is available in checkout
						 * Can receive an array with the cart item values or the WC_Order_Item instance
						 * 
						 * @since 3.0.0
						 * TODO: Document this hook
						 */
						$return = apply_filters( 'sequra_is_item_available_in_checkout', $return, $item );
						if ( ! $return ) {
							break;
						}
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
