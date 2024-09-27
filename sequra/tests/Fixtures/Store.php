<?php
/**
 * Helper to do common operations with the store
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Tests\Fixtures;

use DateTime;
use WC_Coupon;
use WC_DateTime;
use WC_Order;
use WC_Order_Item_Fee;
use WC_Order_Item_Shipping;
use WC_Product;
use WC_Product_Simple;

/**
 * Helper to do common operations with the store
 */
class Store {

	private $orders;
	private $products;
	private $coupons;

	public function __construct() {
		$this->orders   = array();
		$this->products = array();
		$this->coupons  = array();
	}

	public function get_shopper_address(): array {
		return array(
			'first_name' => 'Name',
			'last_name'  => 'Last Name',
			'company'    => 'seQura',
			'email'      => 'test@sequra.es',
			'phone'      => '+34666666666',
			'address_1'  => "Carrer d'Alí Bei, 7, L'Eixample",
			'address_2'  => '', 
			'city'       => 'Barcelona',
			'state'      => 'B',
			'postcode'   => '08010',
			'country'    => 'ES',
		);
	}

	public function set_up(): void {
		$this->populate_db_with_products();
		$this->populate_db_with_coupons();
		$this->populate_db_with_orders();
	}

	public function tear_down(): void {
		$this->remove_orders_from_db();
		$this->remove_products_from_db();
		$this->remove_coupons_from_db();
	}

	/**
	 * Get products
	 * @return WC_Product[]
	 */
	public function get_products() {
		return $this->products;
	}

	/**
	 * Get orders
	 * @return WC_Order[]
	 */
	public function get_orders() {
		return $this->orders;
	}

	private function populate_db_with_products(): void {
		$products = array();
		$data     = array(
			array(
				'name'  => 'Product A',
				'price' => 90,
			),
			array(
				'name'  => 'Product B',
				'price' => 85.99,
			),
		);

		foreach ( $data as $product_data ) {
			$product = new WC_Product_Simple();
			$product->set_name( $product_data['name'] );
			$product->set_regular_price( $product_data['price'] );
			$product->save();
			$products[] = $product;
		}

		$this->products = $products;
	}

	private function add_order( $payment_method, $datetime ): WC_Order {
		/*
		Each order must have the following cart content:
			1 x Product A with a value of 90.00
			2 x Product B with a value of 85.99
			1 x Shipping with a value of 10.00
			1 x Discount with a value of -10.00
			1 x Fee with a value of 15.00
		*/
		$order = new WC_Order();
		
		$date = new WC_DateTime( $datetime );
		
		$order->set_currency( 'EUR' );
		$order->set_date_created( $date->getTimestamp() );
		$order->set_date_modified( $date->getTimestamp() );
		$order->set_date_completed( $date->getTimestamp() );
		$order->set_status( 'wc-completed' );
		$order->set_payment_method( $payment_method );

		$order->add_product( $this->products[0] );
		$order->add_product( $this->products[1], 2 );

		$shipping = new WC_Order_Item_Shipping();
		$shipping->set_method_title( 'Shipping' );
		$shipping->set_method_id( 'free_shipping:1' );
		$shipping->set_total( 10 );
		$order->add_item( $shipping );
		
		$fee = new WC_Order_Item_Fee();
		$fee->set_name( 'Ecotax' );
		$fee->set_amount( 15 );
		$fee->set_total( 15 );
		$order->add_item( $fee );
		
		$order->apply_coupon( $this->coupons[0]->get_code() );

		$order->calculate_totals();

		$order->set_created_via( 'admin' );
		$order->set_customer_id( 0 );
		$order->set_billing_address( $this->get_shopper_address() );
		$order->set_shipping_address( $this->get_shopper_address() );

		return wc_get_order( $order->save() );
	}

	private function populate_db_with_coupons(): void {
		$this->coupons = array();
		
		$coupon = new WC_Coupon();
		$coupon->set_code( 'discount' );
		$coupon->set_amount( 10 );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->save();
		
		$this->coupons[] = $coupon;
	}

	private function populate_db_with_orders(): void {
		$orders = array();
		
		/* 
		Create:
			1 - Order paid with seQura in the last week.
			2 - Order paid with seQura in the past week.
			3 - Order paid with other payment method in the last week.
		*/
		$orders[]     = $this->add_order( 'sequra', '-1 day' );
		$orders[]     = $this->add_order( 'sequra', '-8 day' );
		$orders[]     = $this->add_order( 'paypal', '-1 day' );
		$this->orders = $orders;
	}

	private function remove_products_from_db(): void {
		foreach ( $this->products as $product ) {
			$product->delete( true );
		}
	}

	private function remove_orders_from_db(): void {
		foreach ( $this->orders as $order ) {
			$order->delete( true );
		}
	}

	private function remove_coupons_from_db(): void {
		foreach ( $this->coupons as $coupon ) {
			$coupon->delete( true );
		}
	}
}
