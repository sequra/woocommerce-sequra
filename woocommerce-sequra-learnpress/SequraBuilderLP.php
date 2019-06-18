<?php

class SequraBuilderLP extends SequraBuilderWC {
	
	protected function getProductFromItem( $cart_item, $cart_item_key ) {
		$product = parent::getProductFromItem( $cart_item, $cart_item_key);
		if (!$product && $order = learn_press_get_order_by_item_id( $cart_item->get_id() ) ) {
			$course_id = learn_press_get_order_item_meta( $cart_item->get_id(), '_course_id' );
			$product = new LP_Course($course_id);
		}
		return $product;
	}


	public function items() {
		$items         = array();
		$cart_contents = $this->getCartContents();
		foreach ( $cart_contents as $cart_item_key => $cart_item ) {
			$item     = array();
			if (
				$this->_pm->core_settings['enable_for_virtual'] == 'yes' &&
				(get_post_meta( $cart_item->get_id(), 'is_sequra_service', true ) != 'no' ||
				get_class($_product) == 'LP_Course'
				)
			) {
				$service_end_date = get_post_meta( $cart_item->get_id(), 'sequra_service_end_date', true );
				if(!SequraHelper::validate_service_end_date($service_end_date)){
					$service_end_date = $this->_pm->core_settings['default_service_end_date'];
				}
				$item["type"] = 'service';
				if ( strpos( $service_end_date, "P" )===0 ) {
					$item["ends_in"] = $service_end_date;
				} else {
					$item["ends_on"] = $service_end_date;
				}
			} else {
				$item["type"] = 'product';
			}
			$item["reference"] = $cart_item->get_id();
			$name              = $cart_item->get_name();
			$item["name"]      = strip_tags( $name );
			if ( isset( $cart_item['quantity'] ) ) {
				$item["quantity"] = (int) $cart_item['quantity'];
			}
			if ( isset( $cart_item['qty'] ) ) {
				$item["quantity"] = (int) $cart_item['qty'];
			}
			$item["price_with_tax"] = self::integerPrice( self::notNull( ( $cart_item['line_subtotal'] + $cart_item['line_subtotal_tax'] ) ) / $item['quantity'] );
			$item["total_with_tax"] = self::integerPrice( $cart_item['line_subtotal'] + $cart_item['line_subtotal_tax'] );
			$item["downloadable"]   = false;//$_product->is_downloadable();

			// OPTIONAL
			$item["description"] = strip_tags( self::notNull( get_post( $cart_item->get_id() )->post_content ) );
			$item["product_id"]  = self::notNull( $cart_item->get_id() );
			$item["url"]         = (string) self::notNull( get_permalink( $cart_item->get_id() ) );
			//$item["category"]    = self::notNull( strip_tags( $_product->get_categories() ) );
			/*@TODO: $item["manufacturer"] but it is not wooCommerce stantdard attribute*/
			$items[] = $item;
		}

		//order discounts
		if ( $this->_current_order instanceof SequraTempOrder ) {
			foreach ( $this->_cart->coupon_discount_amounts as $key => $val ) {
				$amount  = $val + $this->_cart->coupon_discount_tax_amounts[ $key ];
				$items[] = $this->discount( $key, $amount );
			}
		} else {
			foreach ( $this->_current_order->get_items( 'coupon' ) as $key => $val ) {
				$amount  = $val['discount_amount'] + $val['discount_amount_tax'];
				$items[] = $this->discount( $val['name'], $amount );
			}
		}

		//add Customer fee (without tax)
		$item = array();
		if ( $this->_current_order instanceof SequraTempOrder ) {
			$fees = $this->_cart->fees;
		} else {
			$fees = $this->_current_order->get_fees();
		}
		foreach ( $fees as $fee_key => $fee ) {
			$item["type"] = 'invoice_fee';
			if ( $this->_current_order instanceof SequraTempOrder ) {
				$item["total_with_tax"] = self::integerPrice( $fee->amount );
				$item["tax_rate"]       = 0;
				if ( $fee->tax ) {
					$item["total_with_tax"] += self::integerPrice( $fee->tax );
				}
				$item["total_without_tax"] = $item["total_with_tax"];
			} else {
				$item["total_without_tax"] = $item["total_with_tax"] = self::integerPrice( $fee['line_total'] );
				$item["tax_rate"]          = 0;
			}
			$items[] = $item;
		}

		return $items;
	}

	public function merchant() {
		$ret                                       = parent::merchant();
		$ret['options']['addresses_may_be_missing'] = true;

		return $ret;
	}

	public function deliveryAddress() {
		return null;
	}

	public function invoiceAddress() {
		return null;
	}
}
