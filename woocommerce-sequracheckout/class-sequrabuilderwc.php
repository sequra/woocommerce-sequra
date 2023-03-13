<?php
/**
 * Builder class.
 *
 * @package woocommerce-sequra
 */

/**
 * SequraBuilderWC class
 */
class SequraBuilderWC extends \Sequra\PhpClient\BuilderAbstract {
	const HASH_ALGO = 'sha256';

	/**
	 * Cart with info to build the order for Sequra
	 *
	 * @var null|WC_Cart
	 */
	protected $_cart = null;

	/**
	 * List of shipped order's id tos inform to SeQura with
	 *
	 * @var array
	 */
	protected $_shipped_ids = array();

	/**
	 * List of shipped order's id tos inform to SeQura with
	 *
	 * @var array
	 */
	protected $_building_report = false;

	/**
	 * SequraBuilderWC constructor.
	 *
	 * @param string        $merchant_id Merchant ID as provided in credential.
	 * @param null|WC_Order $order Order with the info to send to SeQura.
	 */
	public function __construct( $merchant_id, WC_Order $order = null ) {
		global $wp;
		$this->merchant_id = $merchant_id;
		// phpcs:disable
		// @todo add nonce
		if ( ! is_null( $order ) ) {
			$this->_current_order = $order;
		} elseif ( isset( $_POST['post_data'] ) ) {
			$this->_current_order = new SequraTempOrder( $_POST['post_data'] );
		} elseif ( isset( $wp->query_vars['order-pay'] ) ) { //if paying an order
			$this->_current_order = wc_get_order( $wp->query_vars['order-pay'] );
		} else {
			$this->_current_order = new SequraTempOrder('');
		}
		// phpcs:enable
		$this->_cart    = WC()->cart;
		$this->_pm      = SequraPaymentGateway::get_instance();
	}

	/**
	 * Build the order for Sequra
	 * @param mixed $state
	 * @return mixed
	 */
	public function build( $state = null ) {
		return apply_filters(
			'woocommerce_sequra_builderwc_build',
			parent::build( $state ),
			$this
		);
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function buildDeliveryReport() {
		$this->_building_report = true;
		add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', array( $this, 'setOrdersMetaQuery' ), 10, 3 );
		parent::buildDeliveryReport();
		remove_filter( 'woocommerce_order_data_store_cpt_get_orders_query', array( $this, 'setOrdersMetaQuery' ) );
	}

	/**
	 * Set payment method
	 *
	 * @param WC_Payment_Gateway $pm SequraInvoiceGateway or SequraPartPaymentGateway.
	 * @return void
	 */
	public function setPaymentMethod( $pm ) {
		$this->_pm           = $pm;
	}

	/**
	 * Get order_ref_1 or order_ref_2.
	 *
	 * @param int $num 1 or 2.
	 * @return string
	 */
	public function getOrderRef( $num ) {
		if ( 1 === $num ) {
			return apply_filters(
				'woocommerce_sequra_get_order_ref_1',
				$this->get_order_id(),
				$this->_current_order
			);
		}
	}

	/**
	 * Build merchant section
	 *
	 * @return array
	 */
	public function merchant() {
		$ret = parent::merchant();
		if ( ! is_null( $this->_pm ) ) {
			$ret['options']                 = $this->options();
			$ret['notify_url']              = add_query_arg(
				array(
					'order'  => '' . $this->get_order_id(),
					'wc-api' => 'woocommerce_' . $this->_pm->id,
				),
				home_url( '/' )
			);
			$ret['notification_parameters'] = array(
				'order' => "".$this->get_order_id(),
				'signature' => self::sign( $this->get_order_id() ),
				'result'    => '0',
			);
			$ret['return_url']              = add_query_arg(
				['sq_product'=>'SQ_PRODUCT_CODE'],
				$ret['notify_url']
			);
			$ret['events_webhook'] = array(
				'url' => $ret['notify_url'],
				'parameters' => array(
					'signature' => self::sign( 'webhook' . $this->get_order_id() ),
					'order'  => '' . $this->get_order_id(),
				)
			);
		}

		return $ret;
	}

	/**
	 * Sign the string using HASH_ALGO and merchant's password
	 *
	 * @param string $message String to sign.
	 *
	 * @return string
	 */
	public function sign( $message ) {
		return hash_hmac( self::HASH_ALGO, $message, $this->_pm->settings['password'] );
	}
	/**
	 * Get options for order
	 *
	 * @return null|array
	 */
	protected function options() {
		$data = null;
		if ( $this->_pm->settings['allow_payment_delay'] ) {
			$cart_contents   = $this->getCartContents();
			$first_charge_on = false;
			foreach ( $cart_contents as $cart_item_key => $cart_item ) {
				$_product = $this->getProductFromItem( $cart_item, $cart_item_key );
				if ( ! $_product ) {
					continue;
				}
				$raw_date = get_post_meta( $_product->get_id(), 'sequra_desired_first_charge_date', true );
				if ( ! $raw_date ) {
					continue;
				}
				if ( substr( $raw_date, 0, 1 ) == 'P' ) {
					$date = ( new DateTime() )->add( new DateInterval( $raw_date ) );
				} else {
					$date = new DateTime( $raw_date );
				}
				if ( ! $first_charge_on ) {
					$first_charge_on = $date->format( DateTime::ATOM );
				} else {
					$first_charge_on = min(
						$first_charge_on,
						$date->format( DateTime::ATOM )
					);
				}
			}
			if ( $first_charge_on ) {
				$data['desired_first_charge_on'] = $first_charge_on;
			}
		}
		return $data;
	}

	/**
	 * Undocumented function
	 *
	 * @return array
	 */
	public function cartWithItems() {
		$data                    = array();
		$sequra_cart_info        = SequraHelper::get_cart_info_from_session();
		$data['currency']        = get_woocommerce_currency();
		$data['cart_ref']        = $sequra_cart_info['ref'];
		$data['created_at']      = $sequra_cart_info['created_at'];
		$data['updated_at']      = date( 'c' );
		$data['gift']            = false;
		$data['delivery_method'] = $this->deliveryMethod();
		$data['items']           = $this->items();
		$total                   = \Sequra\PhpClient\Helper::totals( $data );

		$data['order_total_with_tax']    = $total['with_tax'];
		$data['order_total_without_tax'] = $total['without_tax'];
		$data['order_total_tax']         = 0;
		if ( $this->_pm->settings['allow_registration_items'] ) {
			$this->registrationItems( $data );
		}
		return $data;
	}

	/**
	 * Undocumented function
	 *
	 * @param array $data reference to built cartwithitem.
	 * @return void
	 */
	protected function registrationItems( &$data ) {
		$items = $data['items'];
		foreach ( $items as $key => $item ) {
			if ( ! isset( $item['product_id'] ) || ! $item['product_id'] ) {
				continue;
			}
			$registration_amount = self::integerPrice(
				(float) get_post_meta( $item['product_id'], 'sequra_registration_amount', true )
			);
			if ( $registration_amount > 0 ) {
				$data['items'][] = array(
					'type'           => 'registration',
					'reference'      => $item['reference'] . '-reg',
					'name'           => 'Reg. ' . $item['name'],
					'total_with_tax' => $item['quantity'] * $registration_amount,
				);
				// Fix orginal item
				$data['items'][ $key ]['total_with_tax'] = max(
					0,
					$data['items'][ $key ]['total_with_tax'] - $item['quantity'] * $registration_amount
				);
				$data['items'][ $key ]['price_with_tax'] = max(
					0,
					$data['items'][ $key ]['price_with_tax'] - $registration_amount
				);
			}
		}
	}

	/**
	 * Undocumented function
	 *
	 * @return array
	 */
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
	/**
	 * Get shipping method from session.
	 *
	 * @return string
	 */
	private function getShippingMethodFromSession() {
		$shipping_methods = WC()->session->chosen_shipping_methods;
		if ( ! $shipping_methods ) {
			$shipping_methods = array();
		}
		$packages = WC()->shipping->get_packages();
		$package  = current( $packages );
		if($package && isset($package['rates']) && isset($package['rates'][ current( $shipping_methods ) ])){
			return $package['rates'][ current( $shipping_methods ) ];
		}
	}
	/**
	 * Undocumented function
	 *
	 * @param array      $item    the array with item info.
	 * @param WC_Product $product the product we are building item info for.
	 * @return void
	 */
	protected function add_service_end_date( &$item, $product, $cart_item ) {
		$post_id          = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		$service_end_date = apply_filters(
			'woocommerce_sequra_add_service_end_date',
			get_post_meta( $post_id, 'sequra_service_end_date', true ),
			$product,
			$cart_item
		);
		if ( ! SequraHelper::validate_service_date( $service_end_date ) ) {
			$service_end_date = $this->_pm->settings['default_service_end_date'];
		}
		if ( 0 === strpos( $service_end_date, 'P' ) ) {
			$item['ends_in'] = $service_end_date;
		} else {
			$item['ends_on'] = $service_end_date;
		}
	}
	/**
	 * Undocumented function
	 *
	 * @return array
	 */
	public function productItems() {
		global $woocommerce;
		$items         = array();
		$cart_contents = $this->getCartContents();
		foreach ( $cart_contents as $cart_item_key => $cart_item ) {
			$_product = $this->getProductFromItem( $cart_item, $cart_item_key );
			if ( ! $_product ) {
				continue;
			}
			$item = array();
			if (
				'yes' === $this->_pm->settings['enable_for_virtual'] &&
				get_post_meta( $_product->get_id(), 'is_sequra_service', true ) !== 'no'
			) {
				$item['type'] = 'service';
				$this->add_service_end_date( $item, $_product, $cart_item );
			} else {
				$item['type'] = 'product';
			}
			$item['reference'] = $_product->get_sku() ? $_product->get_sku() : $_product->get_id();

			// @todo: Research conflict with this and WPML.
			// $name = apply_filters( 'woocommerce_cart_item_name', $_product->get_title(), $cart_item, $cart_item_key);
			$name = $_product->get_title();

			$item['name'] = wp_strip_all_tags( $name );
			if ( isset( $cart_item['quantity'] ) ) {
				$item['quantity'] = (int) $cart_item['quantity'];
			}
			if ( isset( $cart_item['qty'] ) ) {
				$item['quantity'] = (int) $cart_item['qty'];
			}
			$item['price_with_tax'] = self::integerPrice( self::notNull( ( $cart_item['line_subtotal'] + $cart_item['line_subtotal_tax'] ) ) / $item['quantity'] );
			$item['total_with_tax'] = self::integerPrice( $cart_item['line_subtotal'] + $cart_item['line_subtotal_tax'] );
			$item['downloadable']   = $_product->is_downloadable();

			// OPTIONAL.
			$item['description'] = (string) strip_tags( self::notNull( get_post( $_product->get_id() )->post_content ) );
			$item['product_id']  = self::notNull( $_product->get_id() );
			$item['url']         = (string) self::notNull( get_permalink( $_product->get_id() ) );
			if ( version_compare( $woocommerce->version, '3.0.0', '<' ) ) {
				$item['category'] = (string) self::notNull( strip_tags( $_product->get_categories() ) );
			} else {
				$item['category'] = (string) self::notNull( strip_tags( wc_get_product_category_list( $_product->get_id() ) ) );
			}
			/*@todo: $item["manufacturer"] but it is not wooCommerce stantdard attribute*/
			$items[] = $item;
		}
		return $items;
	}

	/**
	 * Undocumented function
	 *
	 * @return array
	 */
	public function discountItems() {
		global $woocommerce;
		$items = array();
		// order discounts.
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
		return $items;
	}

	/**
	 * Undocumented function
	 *
	 * @return array
	 */
	public function extraItems() {
		global $woocommerce;
		$items = array();
		// add Customer fee (without tax).
		$item = array();
		if ( $this->_current_order instanceof SequraTempOrder ) {
			$fees = $this->_cart->get_fees();
		} else {
			$fees = $this->_current_order->get_fees();
		}
		foreach ( $fees as $fee_key => $fee ) {
			if ( $this->_current_order instanceof SequraTempOrder ) {
				$name   = $fee->name;
				$amount = $fee->amount;
				if ( $fee->tax ) {
					$amount += $fee->tax;
				}
			} else {
				$name   = $fee['name'];
				$amount = $fee['line_total'];
				if ( isset($fee['total_tax']) && $fee['total_tax'] ) {
					$amount += $fee['total_tax'];
				}
			}
			$items[] = $this->feeHandlingOrDiscount( $name, $amount );
		}

		return $items;
	}
	/**
	 * Undocumented function
	 *
	 * @return array
	 */
	protected function getCartcontents() {
		if ( 'completed' === $this->_current_order->get_status() ) {
			return $this->_current_order->get_items(
				apply_filters(
					'woocommerce_admin_order_item_types',
					array( 'line_item' )
				)
			);
		}
		if(
			is_array( $this->_current_order->get_items() ) &&
			count( $this->_current_order->get_items() )
		) {
			return $this->_current_order->get_items();
		}
		return ! is_null( WC()->cart ) ? WC()->cart->get_cart_contents() : [];
	}

	/**
	 * Undocumented function
	 *
	 * @param mixed  $cart_item     cart item.
	 * @param string $cart_item_key cart item key.
	 * @return array
	 */
	protected function getProductFromItem( $cart_item, $cart_item_key ) {
		if ( is_callable( array( $cart_item, 'get_product' ) ) ) {
			return $cart_item->get_product();
		}
		if ( isset( $cart_item['product_id'] ) && (int)$cart_item['product_id'] > 0 ) {
			return new WC_Product( $cart_item['product_id'] );
		}
		return $this->_current_order->get_product_from_item( $cart_item );
	}

	/**
	 * Build discount item
	 *
	 * @param string $ref Discount reference.
	 * @param int    $amount Discount amount.
	 *
	 * @return array
	 */
	protected function discount( $ref, $amount ) {
		$discount                  = -1 * $amount;
		$item                      = array();
		$item['type']              = 'discount';
		$item['reference']         = self::notNull( $ref );
		$item['name']              = 'Descuento';
		$item['total_without_tax'] = self::integerPrice( $discount );
		$item['total_with_tax']    = self::integerPrice( $discount );

		return $item;
	}

	/**
	 * Undocumented function
	 *
	 * @param string $name Name.
	 * @param float  $amount Amount.
	 * @return array
	 */
	protected function feeHandlingOrDiscount( $name, $amount ) {
		$item = array();
		$item['type']      = $amount>0?'handling':'discount';
		$item['reference'] = $name;
		$item['name']      = $name;
		$item['tax_rate']          = 0;
		$item['total_with_tax']    = self::integerPrice( $amount );
		$item['total_without_tax'] = $item['total_with_tax'];

		return $item;
	}

	/**
	 * Undocumented function
	 *
	 * @return array
	 */
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

		if ( 0 === $shipping_total ) {
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

	/**
	 * Undocumented function
	 *
	 * @param array $order built order array.
	 * @return array
	 */
	public function fixRoundingProblems( $order ) {
		$totals           = \Sequra\PhpClient\Helper::totals( $order['cart'] );
		$diff_with_tax    = $order['cart']['order_total_with_tax'] - $totals['with_tax'];
		$diff_without_tax = $order['cart']['order_total_without_tax'] - $totals['without_tax'];
		/*Don't correct error bigger than 1 cent per line*/
		if ( ( 0 === $diff_with_tax && 0 === $diff_without_tax ) || count( $order['cart']['items'] ) < abs( $diff_with_tax ) ) {
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

	/**
	 * Undocumented function
	 *
	 * @return array
	 */
	public function getShippedOrderIds() {
		if ( is_null( $this->_shipped_ids ) ) {
			$this->getShippedOrderList();
		}

		return $this->_shipped_ids;
	}

	/**
	 * Undocumented function
	 *
	 * @return array
	 */
	public function getShippedOrderList() {
		global $woocommerce;
		if ( version_compare( $woocommerce->version, '3.0.0', '<' ) ) {
			return $this->getShippedOrderList_legacy();
		}
		$args = array(
			'limit'      => -1,
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => '_sent_to_sequra',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_payment_method',
					'compare' => 'LIKE',
					'value'   => 'sequra',
				),

			),
			'type'       => 'shop_order',
			'status'     => apply_filters( 'woocommerce_sequracheckout_sent_statuses', array( 'wc-completed' ) ),
			'return'     => 'ids',
		);
		$this->_shipped_ids = wc_get_orders( $args );
		return $this->_shipped_ids;
	}

	/**
	 * Undocumented function
	 *
	 * @return array
	 */
	public function getShippedOrderList_legacy() {
		$args               = array(
			'numberposts' => -1,
			'meta_query'  => array(
				'relation' => 'AND',
				array(
					'key'     => '_sent_to_sequra',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_payment_method',
					'compare' => 'LIKE',
					'value'   => 'sequra',
				),
			),
			'post_type'   => 'shop_order',
			'post_status' => 'publish',
			'tax_query'   => array(
				array(
					'taxonomy' => 'shop_order_status',
					'field'    => 'slug',
					'terms'    => 'completed',
				),
			),
			'return'      => 'ids',
		);
		$this->_shipped_ids = get_posts( $args );
		return $this->_shipped_ids;
	}

	/**
	 * Undocumented function
	 *
	 * @param array $wp_query_args    query args.
	 * @param array $args             args.
	 * @param array $order_data_store order data store.
	 * @return array
	 */
	public function setOrdersMetaQuery( $wp_query_args, $args, $order_data_store ) {
		if ( isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ) {
			$wp_query_args['meta_query'] = array_merge( $wp_query_args['meta_query'], $args['meta_query'] );
		}

		return $wp_query_args;
	}

	/**
	 * Build shipped orders and store them in orders.
	 *
	 * @return void
	 */
	public function buildShippedOrders() {
		$order_ids     = $this->getShippedOrderList();
		$this->_orders = array();
		foreach ( $order_ids as $order_id ) {
			$data                       = array();
			$this->_current_order       = new WC_Order( $order_id );
			$data['sent_at']            = self::dateOrBlank( $this->order_sent_at() );
			$data['state']              = 'delivered';
			$data['delivery_address']   = $this->deliveryAddress();
			$data['invoice_address']    = $this->invoiceAddress();
			$data['customer']           = $this->customer();
			$data['cart']               = $this->shipmentCart();
			$data['remaining_cart']     = $this->remainingCart();
			$data['merchant_reference'] = $this->orderMerchantReference();
			$this->_orders[]            = $data;
		}
	}

	protected function order_sent_at() {
		global $woocommerce;
		if (
			version_compare( $woocommerce->version, '3.0.0', '<' ) ||
			is_null( $this->_current_order->get_date_completed() )
		) {
			return date( 'c', strtotime( $this->_current_order->completed_date ) );
		}
		return $this->_current_order->get_date_completed()->date( 'c' );
	}

	/**
	 * Undocumented function
	 *
	 * @return array
	 *
	 * @throws Exception When no city found.
	 */
	public function deliveryAddress() {
		$data                   = array();
		$data['given_names']    = self::notNull( $this->getDeliveryField( 'shipping_first_name' ) );
		$data['surnames']       = self::notNull( $this->getDeliveryField( 'shipping_last_name' ) );
		$data['company']        = self::notNull( $this->getDeliveryField( 'shipping_company' ) );
		$data['address_line_1'] = self::notNull( $this->getDeliveryField( 'shipping_address_1' ) );
		$data['address_line_2'] = self::notNull( $this->getDeliveryField( 'shipping_address_2' ) );
		$data['postal_code']    = self::notNull( $this->getDeliveryField( 'shipping_postcode' ) );
		$data['city']           = self::notNull( $this->getDeliveryField( 'shipping_city' ) );
		if ( '' === $data['city'] ) {
			$data['city'] = self::notNull( $this->getDeliveryField( 'city' ) );
		}
		$data['country_code'] = self::notNull( $this->getDeliveryField( 'shipping_country' ), 'ES' );
		// OPTIONAL.
		$states     = WC()->countries->get_states( $data['country_code'] );
		$state_code = self::notNull( $this->getDeliveryField( 'shipping_state' ) );
		if ( $state_code ) {
			$data['state'] = self::notNull( $states[ $state_code ] );
		}
		$data['mobile_phone'] = self::notNull( $this->getDeliveryField( 'shipping_phone' ) );
		// @todo: Search vat/nif common plugins.
		$data['vat_number'] = self::notNull( $this->getDeliveryField( 'shipping_nif' ) );
		if ( ! $data['vat_number'] ) {
			$data['vat_number'] = self::notNull( $this->getDeliveryField( 'shipping_vat' ) );
		}
		if ( ! $data['vat_number'] ) {
			$data['vat_number'] = self::notNull( $this->getDeliveryField( 'nif' ) );
		}
		return $data;
	}
	/**
	 * Undocumented function
	 *
	 * @param string $field_name Field name.
	 * @return string
	 */
	public function getDeliveryField( $field_name ) {
		$ret = $this->getField( $field_name );
		if ( ! is_null( $ret ) && '' != $ret ) {
			return $ret;
		}

		return $this->getField( str_replace( 'shipping', 'billing', $field_name ) );
	}

	/**
	 * Undocumented function
	 *
	 * @param string $field_name field name.
	 * @return string
	 */
	public function getField( $field_name ) {
		global $woocommerce;
		if ( $this->_current_order instanceof SequraTempOrder ) {
			$func = 'get_' . $field_name;

			return $this->_current_order->$func();
		}

		$func = 'get_' . $field_name;

		return method_exists( get_class( $this->_current_order ), $func ) ?
			$this->_current_order->$func() : self::notNull( $this->_current_order->$field_name );
	}
	/**
	 * Undocumented function
	 *
	 * @return array
	 */
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
		// OPTIONAL.
		$states     = WC()->countries->get_states( $data['country_code'] );
		$state_code = self::notNull( $this->getDeliveryField( 'billing_state' ) );
		if ( $state_code ) {
			$data['state'] = self::notNull( $states[ $state_code ] );
		}
		$data['mobile_phone'] = self::notNull( $this->getField( 'billing_phone' ) );
		$data['vat_number']   = self::notNull( $this->getField( 'billing_nif' ) );
		if ( '' === $data['vat_number'] ) {
			$data['vat_number'] = self::notNull( $this->getField( 'billing_vat' ) );
		}
		if ( ! $data['vat_number'] ) {
			$data['vat_number'] = self::notNull( $this->getField( 'nif' ) );
		}
		return $data;
	}
	/**
	 * Undocumented function
	 *
	 * @return array
	 */
	public function customer() {
		$data = array();
		$id   = -1;
		if ( ! $this->_building_report ) {
			$data['language_code'] = self::notNull( self::getCustomerLanguage() );
			if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
				$data['ip_number'] = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
			} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				$data['ip_number'] = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
			} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
				$data['ip_number'] = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
			}
			if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
				$data['user_agent'] = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
			}

			$data['logged_in'] = is_user_logged_in();
			$id                = $data['logged_in'] ? get_current_user_id() : -1;
		}

		$data['given_names'] = $this->getCustomerField( $id, 'first_name' );
		$data['surnames']    = $this->getCustomerField( $id, 'last_name' );
		$data['email']       = $this->getCustomerField( $id, 'billing_email' );
		$data['nin']         = self::notNull( $this->getField( 'nif' ) );
		if ( '' == $data['nin'] ) {
			$data['nin'] = self::notNull( $this->getField( 'billing_nif' ) );
		}
		if ( '' == $data['nin'] ) {
			$data['nin'] = self::notNull( $this->getField( 'billing_vat' ) );
		}

		// OPTIONAL.
		if ( is_user_logged_in() ) { // Avoid if user is not logged in.
			$data['date_of_birth'] = get_user_meta( $id, 'sequra_dob', true );
			if ( '' == $data['date_of_birth'] ) {
				$data['date_of_birth'] = self::dateOrBlank( $this->getCustomerField( $id, 'dob' ) );
			}
			$data['previous_orders'] = self::getPreviousOrders( $id );
		}
		$data['company'] = $this->getCustomerField( $id, 'billing_company' );
		if ( $id > 0 ) {
			$data['ref'] = $id;
		}

		return $data;
	}

	/**
	 * Get SeQura language code
	 *
	 * @return string
	 * */
	public static function getCustomerLanguage() {
		$lng = substr( get_bloginfo( 'language' ), 0, 2 );
		if ( function_exists( 'qtrans_getLanguage' ) ) {
			$lng = qtrans_getLanguage();
		}
		if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
			$lng = ICL_LANGUAGE_CODE;
		}

		return $lng;
	}
	/**
	 * Undocumented function
	 *
	 * @param int    $id         Customer id.
	 * @param string $field_name Field name.
	 * @return array
	 */
	public function getCustomerField( $id, $field_name ) {
		if ( 0 < $id && get_user_meta( $id, $field_name, true ) ) {
			return get_user_meta( $id, $field_name, true );
		}

		$var = 'billing_' . str_replace( 'billing_', '', $field_name );

		return self::notNull( $this->getField( $var ) );
	}
	/**
	 * Undocumented function
	 *
	 * @param int $customer_id customer id.
	 * @return array
	 */
	public static function getPreviousOrders( $customer_id ) {
		$orders    = array();
		$args      = array(
			'limit'       => -1,
			'type'        => 'shop_order',
			'customer'    => $customer_id,
			'post_status' => array( 'wc-processing', 'wc-completed' ),
			'return'      => 'ids',
		);
		$order_ids = wc_get_orders( $args );
		foreach ( $order_ids as $id ) {
			$prev_order      = new WC_Order( $id );
			$post            = get_post( $id );
			$order['amount'] = self::integerPrice( $prev_order->get_total() );
			if ( $order['amount'] <= 0 ) {
				continue;
			}
			$order['currency']   = $prev_order->get_currency();
			$date                = strtotime( $post->post_date );
			$order['created_at'] = date( 'c', $date );
			$orders[]            = $order;
		}

		return $orders;
	}

	protected function get_order_currency() {
		global $woocommerce;
		if ( version_compare( $woocommerce->version, '3.0.0', '<' ) ) {
			return $this->_current_order->get_order_currency();
		}
		return $this->_current_order->get_currency();
	}

	/**
	 * Undocumented function
	 *
	 * @return array
	 */
	public function shipmentCart() {
		$data                    = array(
			'order_total_without_tax' => 0,
			'order_total_with_tax'    => 0,
		);
		$data['currency']        = $this->get_order_currency();
		$data['delivery_method'] = $this->deliveryMethod();
		$data['gift']            = false;
		$data['items']           = $this->items();

		if ( count( $data['items'] ) > 0 ) {
			$totals                          = \Sequra\PhpClient\Helper::totals( $data );
			$data['order_total_without_tax'] = $totals['without_tax'];
			$data['order_total_with_tax']    = $totals['with_tax'];
		}

		return $data;
	}
	/**
	 * Undocumented function
	 *
	 * @return array
	 */
	public function remainingCart() {
		$empty_cart = array(
			'order_total_without_tax' => 0,
			'order_total_with_tax'    => 0,
			'items'                   => array(),
		);
		return $empty_cart;
	}
	/**
	 * Undocumented function
	 *
	 * @return array
	 */
	public function getOrderStats() {
		global $woocommerce;
		if ( version_compare( $woocommerce->version, '3.0.0', '<' ) ) {
			return $this->getOrderStats_legacy();
		}
		$stats = array();
		if ( false && get_option( 'sequra_allowstats' ) ) {
			return $stats;
		}

		$args   = array(
			'limit'      => -1,
			'type'       => 'shop_order',
			'date_after' => '1 week ago',
		);
		$orders = wc_get_orders( $args );
		foreach ( $orders as $order ) {
			$this->_current_order = $order;
			$stat                 = array(
				'completed_at'       => self::dateOrBlank( '' . $order->get_date_created() ),
				'merchant_reference' => $this->orderMerchantReference(),
				'currency'           => $this->get_order_currency(),
			);

			if ( true || get_option( 'sequra_allowstats_amount' ) ) {
				$stat['amount'] = self::integerPrice( $this->_current_order->get_total() );
			}
			if ( true || get_option( 'sequra_allowstats_country' ) ) {
				$stat['country'] = self::notNull( $this->_current_order->get_billing_country(), 'ES' );
			}
			if ( true || get_option( 'sequra_allowstats_payment' ) ) { // todo: Stats config.
				$stat['payment_method_raw'] = $this->_current_order->get_payment_method();
				$stat['payment_method']     = self::mapPaymentMethod( $stat['payment_method_raw'] );
			}
			if ( true || get_option( 'sequra_allowstats_status' ) ) { // todo: Stats config.
				$stat['raw_status'] = $this->_current_order->get_status();
				$stat['status']     = self::mapStatus( $stat['raw_status'] );
			}

			$stats[] = $stat;
		}

		return $stats;
	}
	/**
	 * Undocumented function
	 *
	 * @return array
	 */
	public function getOrderStats_legacy() {
		$stats = array();
		if ( false && get_option( 'sequra_allowstats' ) ) {
			return $stats;
		}
		$args  = array(
			'numberposts' => -1,
			'post_type'   => 'shop_order',
			'date_query'  => array(
				array(
					'column' => 'post_date_gmt',
					'after'  => '1 week ago',
				),
			),
		);
		$posts = get_posts( $args );
		foreach ( $posts as $post ) {
			$this->_current_order = new WC_Order( $post->ID );
			$date                 = strtotime( $post->post_date );
			$stat                 = array(
				'completed_at'       => self::dateOrBlank( date( 'c', $date ) ),
				'merchant_reference' => $this->orderMerchantReference(),
				'currency'           => $this->get_order_currency(),
			);
			if ( true || get_option( 'sequra_allowstats_amount' ) ) {
				$stat['amount'] = self::integerPrice( $this->_current_order->get_total() );
			}
			if ( true || get_option( 'sequra_allowstats_country' ) ) {
				$stat['country'] = self::notNull( $this->_current_order->billing_country, 'ES' );
			}
			if ( true || get_option( 'sequra_allowstats_payment' ) ) { // @todo: Stats config.
				$stat['payment_method_raw'] = $this->_current_order->payment_method;
				$stat['payment_method']     = self::mapPaymentMethod( $stat['payment_method_raw'] );
			}
			if ( true || get_option( 'sequra_allowstats_status' ) ) { // @todo: Stats config.
				$stat['raw_status'] = $this->_current_order->get_status();
				$stat['status']     = self::mapStatus( $stat['raw_status'] );
			}
			$stats[] = $stat;
		}

		return $stats;
	}

	/**
	 * Undocumented function
	 *
	 * @param string $payment_method_raw method name.
	 * @return string
	 */
	public static function mapPaymentMethod( $payment_method_raw ) {
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

	/**
	 * Undocumented function
	 *
	 * @param string $raw_status status name.
	 * @return string
	 */
	public static function mapStatus( $raw_status ) {
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

	/**
	 * Undocumented function
	 *
	 * @return array
	 */
	public static function platform() {
		$sql = "show variables like 'version';";
		global $wpdb;
		// phpcs:disable
		$db_version = $wpdb->get_var( $sql );
		// phpcs:enable
		$data = array(
			'name'           => 'WooCommerce',
			'version'        => self::notNull( WOOCOMMERCE_VERSION ),
			'plugin_version' => 'fc'.get_option( 'sequracheckout_version' ),
			'php_version'    => phpversion(),
			'php_os'         => PHP_OS,
			'uname'          => php_uname(),
			'db_name'        => 'mysql',
			'db_version'     => $db_version,
		);

		return $data;
	}

	/**
	 * Undocumented function
	 *
	 * @return int
	 */
	protected function get_order_id() {
		global $woocommerce;
		if ( version_compare( $woocommerce->version, '3.0.0', '<' ) ) {
			return $this->_current_order->id;
		}
		return $this->_current_order->get_id();
	}
}
