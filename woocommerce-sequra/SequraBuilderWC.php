<?php

class SequraBuilderWC extends SequraBuilderAbstract {
	const HASH_ALGO = 'sha256';

	protected $_cart = null;
	protected $_shipped_ids = array();

	public function __construct( $merchant_id, $order = null ) {
		$this->merchant_id = $merchant_id;
		if ( ! is_null( $order ) ) {
			$this->_current_order = $order;
		} else if ( isset( $_POST['post_data'] ) ) {
			$this->_current_order = new SequraTempOrder( $_POST['post_data'] );
		}
		$this->_cart = WC()->cart;
	}

	public function setPaymentMethod( $pm ) {
		$this->_pm = $pm;
	}

	public function getOrderRef( $num ) {
		if ( 1 == $num ) {
			return $this->_current_order->id;
		}
	}

	public function merchant() {
		$ret                                       = parent::merchant();
		$ret['partpayment_details_getter']         = 'SequraFractionInstance.partpayment_details_getter';
		$ret['options']['accept_terms_explicitly'] = true;
		$ret['notify_url']                         = add_query_arg( array(
			'order'  => "" . $this->_current_order->id,
			'wc-api' => 'woocommerce_' . $this->_pm->id
		), home_url( '/' ) );
		$ret['notification_parameters']            = array(
			'signature' => self::sign( $this->_current_order->id ),
			'result'    => "0"
		);
		$ret['return_url']                         = $ret['notify_url'];

		return $ret;
	}

	public function cartWithItems() {
		$data                            = array();
		$sequra_cart_info                = SequraHelper::get_cart_info_from_session();
		$data['currency']                = get_woocommerce_currency();
		$data['cart_ref']                = $sequra_cart_info['ref'];
		$data['created_at']              = $sequra_cart_info['created_at'];
		$data['updated_at']              = date( 'c' );
		$data['gift']                    = false;
		$data['delivery_method']         = $this->deliveryMethod();
		$data['items']                   = array_merge(
			$this->items(),
			$this->handlingItems()
		);
		$total                           = self::totals( $data );
		$data['order_total_with_tax']    = $total['with_tax'];
		$data['order_total_without_tax'] = $total['without_tax'];
		$data['order_total_tax']         = 0;

		return $data;
	}

	public function fixRoundingProblems( $order ) {
		$totals           = self::totals( $order['cart'] );
		$diff_with_tax    = $order['cart']['order_total_with_tax'] - $totals['with_tax'];
		$diff_without_tax = $order['cart']['order_total_without_tax'] - $totals['without_tax'];
		/*Don't correct error bigger than 1 cent per line*/
		if ( ( $diff_with_tax == 0 && $diff_without_tax == 0 ) || count( $order['cart']['items'] ) < abs( $diff_with_tax ) ) {
			return $order;
		}

		$item['type']              = 'discount';
		$item['reference']         = 'Ajuste';
		$item['name']              = 'Ajuste';
		$item['total_without_tax'] = $diff_without_tax;
		$item['total_with_tax']    = $diff_with_tax;
		if ( $diff_with_tax > 0 ) {
			$item['type']     = 'handling';
			$item['tax_rate'] = $diff_without_tax ? round( abs( ( $diff_with_tax * $diff_without_tax ) ) - 1 ) * 100 : 0;
		}
		$order['cart']['items'][] = $item;

		return $order;
	}

	public function deliveryMethod() {
		$method = null;
		if ( $this->_current_order instanceof SequraTempOrder ) {
			$method   = $this->getShippingMethodFromSession();
			$name     = 'default';
			$provider = 'default';
			if ( $method ) {
				$name     = self::notNull( $method->label );
				$provider = self::notNull( $method->id );
			}

			return array(
				'name'     => $name,
				'days'     => '',
				'provider' => $provider,
			);
		}
		$shipping_methods = $this->_current_order->get_shipping_methods();
		$shipping_method  = current( $shipping_methods );

		return array(
			'name'     => self::notNull( $shipping_method['name'] ),
			'provider' => self::notNull( $shipping_method['method_id'] ),
		);
	}

	private function getShippingMethodFromSession() {
		$shipping_methods = WC()->session->chosen_shipping_methods;
		if ( ! $shipping_methods ) {
			$shipping_methods = array();
		}
		$packages = WC()->shipping->get_packages();
		$package  = current( $packages );

		return $package['rates'][ current( $shipping_methods ) ];
	}

	protected function getCartcontents() {
		if ( $this->_current_order->status == 'completed' ) {
			return $this->_current_order->get_items( apply_filters( 'woocommerce_admin_order_item_types', array( 'line_item' ) ) );
		}

		return $this->_current_order->get_items();
	}

	private function getProductFromItem( $cart_item, $cart_item_key ) {
		return $this->_current_order->get_product_from_item( $cart_item );
	}

	protected function discount( $ref, $amount ) {
		$discount                  = - 1 * $amount;
		$item                      = array();
		$item["type"]              = "discount";
		$item["reference"]         = self::notNull( $ref );
		$item["name"]              = 'Descuento';
		$item["total_without_tax"] = self::integerPrice( $discount );
		$item["total_with_tax"]    = self::integerPrice( $discount );

		return $item;
	}

	public function items() {
		$items         = array();
		$cart_contents = $this->getCartContents();
		foreach ( $cart_contents as $cart_item_key => $cart_item ) {
			$_product = $this->getProductFromItem( $cart_item, $cart_item_key );
			$item     = array();
			if ( $_product->is_virtual() ) {
				$item["type"]     = 'service';
				$service_end_date = get_post_meta( $_product->id, 'service_end_date', true );
				if ( is_numeric( $service_end_date ) ) {
					$item["ends_in"] = 'P' . $service_end_date . 'D';
				} else {
					$item["ends_on"] = $service_end_date;
				}
			} else {
				$item["type"] = 'product';
			}
			$item["reference"] = self::notNull( $_product->get_sku() );
			$name              = apply_filters( 'woocommerce_cart_item_name', $_product->get_title(), $cart_item, $cart_item_key );
			$item["name"]      = strip_tags( $name );
			if ( isset( $cart_item['quantity'] ) ) {
				$item["quantity"] = (int) $cart_item['quantity'];
			}
			if ( isset( $cart_item['qty'] ) ) {
				$item["quantity"] = (int) $cart_item['qty'];
			}
			$item["price_with_tax"] = self::integerPrice( self::notNull( ( $cart_item['line_subtotal'] + $cart_item['line_subtotal_tax'] ) ) / $item['quantity'] );
			$item["total_with_tax"] = self::integerPrice( $cart_item['line_subtotal'] + $cart_item['line_subtotal_tax'] );
			$item["downloadable"]   = $_product->is_downloadable();

			// OPTIONAL
			$item["description"] = strip_tags( self::notNull( get_post( $_product->id )->post_content ) );
			$item["product_id"]  = self::notNull( $_product->id );
			$item["url"]         = self::notNull( get_permalink( $_product->id ) );
			$item["category"]    = self::notNull( strip_tags( $_product->get_categories() ) );
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

	public function handlingItems() {
		$delivery = $this->deliveryMethod();
		if ( ! isset( $delivery['name'] ) && ! isset( $delivery['days'] ) ) {
			return array();
		}
		if ( $this->_current_order instanceof SequraTempOrder ) {
			$shipping_total = $this->_cart->shipping_total + $this->_cart->shipping_tax_total;
		} else {
			$shipping_total = $this->_current_order->get_total_shipping() + $this->_current_order->get_shipping_tax();
		}

		if ( $shipping_total == 0 ) {
			return array();
		}

		$handling = array(
			'type'           => 'handling',
			'reference'      => 'Envío y manipulación',
			'name'           => $delivery['name'] ? $delivery['name'] : 'Gastos de envío',
			'total_with_tax' => self::integerPrice( $shipping_total ),
			'tax_rate'       => 0,
		);
		if ( isset( $delivery['days'] ) && $delivery['days'] ) {
			$handling['days'] = $delivery['days'];
		}

		return array( $handling );
	}

	public function deliveryAddress() {
		$data                   = array();
		$data['given_names']    = self::notNull( $this->getDeliveryField( 'shipping_first_name' ) );
		$data['surnames']       = self::notNull( $this->getDeliveryField( 'shipping_last_name' ) );
		$data['company']        = self::notNull( $this->getDeliveryField( 'shipping_company' ) );
		$data['address_line_1'] = self::notNull( $this->getDeliveryField( 'shipping_address_1' ) );
		$data['address_line_2'] = self::notNull( $this->getDeliveryField( 'shipping_address_2' ) );
		$data['postal_code']    = self::notNull( $this->getDeliveryField( 'shipping_postcode' ) );
		$data['city']           = self::notNull( $this->getDeliveryField( 'shipping_city' ) );
		if ( $data['city'] == '' ) {
			throw new Exception( 'City is required' );
		}
		$data['country_code'] = self::notNull( $this->getDeliveryField( 'shipping_country' ), 'ES' );
		// OPTIONAL
		$states = WC()->countries->get_states( $data['country_code'] );
		if ( $state_code = self::notNull( $this->getDeliveryField( 'shipping_state' ) ) ) {
			$data['state'] = self::notNull( $states[ $state_code ] );
		}
		$data['mobile_phone'] = self::notNull( $this->getDeliveryField( 'shipping_phone' ) );
		/*TODO: Search vat/nif common plugins*/
		$data['vat_number'] = self::notNull( $this->getDeliveryField( 'shipping_nif' ) );
		if ( '' == $data['vat_number'] ) {
			$data['vat_number'] = self::notNull( $this->getDeliveryField( 'shipping_vat' ) );
		}

		return $data;
	}

	public function invoiceAddress() {
		$data                   = array();
		$data['given_names']    = self::notNull( $this->getField( 'billing_first_name' ) );
		$data['surnames']       = self::notNull( $this->getField( 'billing_last_name' ) );
		$data['company']        = self::notNull( $this->getField( 'billing_company' ) );
		$data['address_line_1'] = self::notNull( $this->getField( 'billing_address_1' ) );
		$data['address_line_2'] = self::notNull( $this->getField( 'billing_address_2' ) );
		$data['postal_code']    = self::notNull( $this->getField( 'billing_postcode' ) );
		$data['city']           = self::notNull( $this->getField( 'billing_city' ) );
		$data['country_code']   = self::notNull( $this->getField( 'billing_country' ), 'ES' );
		// OPTIONAL
		$states = WC()->countries->get_states( $data['country_code'] );
		if ( $state_code = self::notNull( $this->getDeliveryField( 'billing_state' ) ) ) {
			$data['state'] = self::notNull( $states[ $state_code ] );
		}
		$data['mobile_phone'] = self::notNull( $this->getField( 'billing_phone' ) );
		$data['vat_number']   = self::notNull( $this->getField( 'billing_nif' ) );
		if ( '' == $data['vat_number'] ) {
			$data['vat_number'] = self::notNull( $this->getField( 'billing_vat' ) );
		}

		return $data;
	}

	/**
	 * Get SeQura language code
	 * */
	static function getCustomerLanguange() {
		$lng = substr( get_bloginfo( 'language' ), 0, 2 );
		if ( function_exists( 'qtrans_getLanguage' ) ) {
			$lng = qtrans_getLanguage();
		}
		if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
			$lng = ICL_LANGUAGE_CODE;
		}

		return $lng;
	}

	public function customer() {
		$data                  = array();
		$data['language_code'] = self::notNull( self::getCustomerLanguange() );
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$data['ip_number'] = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$data['ip_number'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$data['ip_number'] = $_SERVER['REMOTE_ADDR'];
		}
		$data['user_agent'] = $_SERVER["HTTP_USER_AGENT"];

		$data['logged_in'] = is_user_logged_in();
		$id                = $data['logged_in'] ? get_current_user_id() : - 1;

		$data['given_names'] = $this->getCustomerField( $id, 'first_name' );
		$data['surnames']    = $this->getCustomerField( $id, 'last_name' );
		$data['email']       = $this->getCustomerField( $id, 'billing_email' );
		$data['vat_number']  = self::notNull( $this->getField( 'billing_nif' ) );
		if ( '' == $data['vat_number'] ) {
			$data['vat_number'] = self::notNull( $this->getField( 'billing_vat' ) );
		}
		if ( '' != $data['vat_number'] ) {
			$data['nin'] = $data['vat_number'];
		}
		// OPTIONAL
		if ( is_user_logged_in() ) { //Avoid if user is not logged in
			$data['date_of_birth'] = get_user_meta( $id, 'sequra_dob', true );
			if ( '' == $data['date_of_birth'] ) {
				$data['date_of_birth'] = self::dateOrBlank( $this->getCustomerField( $id, 'dob' ) );
			}
		}
		$data['company'] = $this->getCustomerField( $id, 'billing_company' );
		if ( $id > 0 ) {
			$data['ref'] = $id;
		}
		if ( $data['logged_in'] ) {
			$data['previous_orders'] = self::getPreviousOrders( $id );
		}

		return $data;
	}

	public function getCustomerField( $id, $field_name ) {
		if ( 0 < $id && $ret = get_user_meta( $id, $field_name, true ) ) {
			return $ret;
		}

		$var = 'billing_' . str_replace( 'billing_', '', $field_name );

		return self::notNull( $this->getField( $var ) );
	}

	public function getDeliveryField( $field_name ) {
		$ret = $this->getField( $field_name );
		if ( ! is_null( $ret ) && '' != $ret ) {
			return $ret;
		}

		return $this->getField( str_replace( 'shipping', 'billing', $field_name ) );
	}

	public function getField( $field_name ) {
		if ( $this->_current_order instanceof SequraTempOrder ) {
			$func = 'get_' . $field_name;

			return $this->_current_order->$func();
		}

		return self::notNull( $this->_current_order->$field_name );
	}

	public function getPreviousOrders( $customer_id ) {
		$args      = array(
			'numberposts' => - 1,
			'meta_key'    => '_customer_user',
			'meta_value'  => $customer_id,
			'post_type'   => 'shop_order',
			'post_status' => array( 'wc-processing', 'wc-completed' ),
		);
		$posts     = get_posts( $args );
		$orders    = array();
		$order_ids = wp_list_pluck( $posts, 'ID' );
		foreach ( $order_ids as $id ) {
			$prev_order          = new WC_Order( $id );
			$post                = get_post( $id );
			$order['amount']     = self::integerPrice( $prev_order->get_total() );
			$order['currency']   = $prev_order->get_order_currency();
			$date                = strtotime( $post->post_date );
			$order['created_at'] = date( 'c', $date );
			$orders[]            = $order;
		}

		return $orders;
	}

	public function getShippedOrderIds() {
		if ( is_null( $this->_shipped_ids ) ) {
			$this->getShippedOrderList();
		}

		return $this->_shipped_ids;
	}

	public function getShippedOrderList() {
		$args               = array(
			'numberposts' => - 1,
			'meta_query'  => array(
				'relation' => 'AND',
				array(
					'key'     => '_sent_to_sequra',
					'compare' => 'NOT EXISTS'
				),
				array(
					'key'     => '_payment_method',
					'compare' => 'LIKE',
					'value'   => 'sequra',
				)

			),
			'post_type'   => 'shop_order',
			'post_status' => 'publish',
			'tax_query'   => array(
				array(
					'taxonomy' => 'shop_order_status',
					'field'    => 'slug',
					'terms'    => 'completed',
				)
			)
		);
		$posts              = get_posts( $args );
		$this->_shipped_ids = wp_list_pluck( $posts, 'ID' );

		return $posts;
	}

	public function buildShippedOrders() {
		$posts         = $this->getShippedOrderList();
		$this->_orders = array();
		foreach ( $posts as $post ) {
			$data                       = array();
			$this->_current_order       = new WC_Order( $post->ID );
			$date                       = strtotime( $this->_current_order->completed_date );
			$data['sent_at']            = self::dateOrBlank( date( 'c', $date ) );
			$data['state']              = 'delivered';
			$data['delivery_address']   = $this->deliveryAddress();
			$data['invoice_address']    = $this->invoiceAddress();
			$data['customer']           = $this->customer();
			$data['cart']               = $this->shipmentCart();
			$data['merchant_reference'] = $this->orderMerchantReference();
			$this->_orders[]            = $data;

		}
	}

	public function shipmentCart() {
		$data             = array();
		$data['currency'] = $this->_current_order->get_order_currency();;
		$data['delivery_method'] = $this->deliveryMethod();
		$data['gift']            = false;
		$data['items']           = array_merge(
			$this->items(),
			$this->handlingItems()
		);

		if ( count( $data['items'] ) > 0 ) {
			$totals                          = self::totals( $data );
			$data['order_total_without_tax'] = $totals['without_tax'];
			$data['order_total_with_tax']    = $totals['with_tax'];
		}

		return $data;
	}

	public function getOrderStats() {
		$stats = array();
		if ( false && get_option( 'sequra_allowstats' ) ) {
			return $stats;
		}

		$args  = array(
			'numberposts' => - 1,
			'post_type'   => 'shop_order',
			'date_query'  => array(
				array(
					'column' => 'post_date_gmt',
					'after'  => '1 week ago',
				)
			)
		);
		$posts = get_posts( $args );
		foreach ( $posts as $post ) {
			$this->_current_order = new WC_Order( $post->ID );
			$date                 = strtotime( $post->post_date );
			$stat                 = array(
				'completed_at'       => self::dateOrBlank( date( 'c', $date ) ),
				'merchant_reference' => $this->orderMerchantReference(),
				'currency'           => $this->_current_order->get_order_currency()
			);

			if ( true || get_option( 'sequra_allowstats_amount' ) ) // TODO: Stats config
			{
				$stat['amount'] = self::integerPrice( $this->_current_order->get_total() );
			}
			if ( true || get_option( 'sequra_allowstats_country' ) ) // TODO: Stats config
			{
				$stat['country'] = self::notNull( $this->_current_order->billing_country, 'ES' );
			}
			if ( true || get_option( 'sequra_allowstats_payment' ) ) { // TODO: Stats config
				$stat['payment_method_raw'] = $this->_current_order->payment_method;
				$stat['payment_method']     = self::mapPaymentMethod( $stat['payment_method_raw'] );
			}
			if ( true || get_option( 'sequra_allowstats_status' ) ) { // TODO: Stats config
				$stat['raw_status'] = $this->_current_order->status;
				$stat['status']     = self::mapStatus( $stat['raw_status'] );
			}

			$stats[] = $stat;
		}

		return $stats;
	}

	static function mapPaymentMethod( $payment_method_raw ) {
		switch ( $payment_method_raw ) {
			case 'ceca':
			case 'servired':
			case 'redsys':
			case 'iupay':
			case 'univia':
			case 'banesto':
			case 'ruralvia':
			case 'cuatrob':
			case 'paytpvcom':
			case 'cc':
				return 'CC';
			case 'paypal':
				return 'PP';
			case 'cheque':
			case 'banktransfer':
			case 'trustly':
				return 'TR';
			case 'cashondelivery':
			case 'cod':
				return 'COD';
				break;
			case 'sequra':
				return 'SQ';
			default:
				return 'O/' . $payment_method_raw;
		}
	}

	static function mapStatus( $raw_status ) {
		switch ( $raw_status ) {
			case 'completed':
				return 'shipped';
			case 'cancelled':
			case 'refunded':
				return 'cancelled';
			default:
				return 'processing';
		}
	}

	public function platform() {
		$sql = "show variables like 'version';";
		global $wpdb;
		$db_version = $wpdb->get_var( $sql );

		$data = array(
			'name'           => 'WooCommerce',
			'version'        => self::notNull( WOOCOMMERCE_VERSION ),
			'plugin_version' => get_option( 'sequra_version' ),
			'php_version'    => phpversion(),
			'php_os'         => PHP_OS,
			'uname'          => php_uname(),
			'db_name'        => 'mysql',
			'db_version'     => $db_version
		);

		return $data;
	}

	/**
	 * Sign the string using HASH_ALGO and merchant's password
	 *
	 * @param $message String to sign
	 *
	 * @return string
	 */
	public function sign( $message ) {
		return hash_hmac( self::HASH_ALGO, $message, $this->_pm->password );
	}
}
