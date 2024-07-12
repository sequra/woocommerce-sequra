<?php
/**
 * SequraBuilderLP
 *
 * @package seura-learnpress
 */

/**
 * Class
 *
 */

class SequraBuilderLP extends SequraBuilderWC {
	/**
	 * Undocumented function
	 *
	 * @param [type] $cart_item
	 * @param [type] $cart_item_key
	 * @return void
	 */
	protected function getProductFromItem( $cart_item ) {
        try {
            return parent::getProductFromItem( $cart_item );
        } catch (Exception $e) {
            $course_id = learn_press_get_order_item_meta( $cart_item['product_id'], '_course_id' );
            $product = new LP_Course($course_id);
        }
		return $product;
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function items() {
		$items         = array();
		$cart_contents = $this->getCartContents();
		foreach ( $cart_contents as $cart_item_key => $cart_item ) {
			$item     = array();
			$_product = $this->getProductFromItem( $cart_item );
			if ( ! $_product ) {
				continue;
			}
			if (
				$this->pm->settings['enable_for_virtual'] == 'yes' &&
				(get_post_meta( $cart_item['product_id'], 'is_sequra_service', true ) != 'no' ||
				get_class($_product) == 'LP_Course'
				)
			) {
				$service_end_date = get_post_meta( $cart_item['product_id'], 'sequra_service_end_date', true );
				if(!SequraHelper::validate_service_date($service_end_date)){
					$service_end_date = $this->pm->settings['default_service_end_date'];
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
			$item["reference"] = $cart_item['product_id'];
			$name              = $cart_item['product_id'];
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
			$item["description"] = strip_tags( self::notNull( get_post( $cart_item['product_id'] )->post_content ) );
			$item["product_id"]  = self::notNull( $cart_item['product_id'] );
			$item["url"]         = (string) self::notNull( get_permalink( $cart_item['product_id'] ) );
			//$item["category"]    = self::notNull( strip_tags( $_product->get_categories() ) );
			$items[] = $item;
		}

		return $items;
	}
	/**
	 * Undocumented function
	 *
	 * @return array
	 */
	public function merchant() {
		$ret                                        = parent::merchant();
		$ret['options']['addresses_may_be_missing'] = true;

		return $ret;
	}
	/**
	 * Undocumented function
	 *
	 * @return null
	 */
	public function deliveryAddress() {
		return null;
	}
	/**
	 * Undocumented function
	 *
	 * @return null
	 */
	public function invoiceAddress() {
		return null;
	}
}
