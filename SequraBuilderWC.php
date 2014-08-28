<?php

class SequraBuilderWC extends SequraBuilderAbstract
{
	protected $_order;
	protected $_cart;

	public function __construct($merchant_id, $order = null)
	{
		$this->merchant_id = $merchant_id;
		if (!is_null($order))
			$this->_order = $order;
		else
			$this->_order = new SequraTempOrder($_POST['post_data']);
		$this->_cart = WC()->cart;
	}

	public function setPaymentMethod($pm)
	{
		$this->_pm = $pm;
	}
	public function getOrderRef($num){
		if(1==$num)
			return $this->_order->id;
	}

	public function merchant()
	{
		$ret = array();
		$ret['id'] = $this->merchant_id;
		$ret['approved_callback'] = 'shop_callback_sequra_approved';
		return $ret;
	}

	public function cartWithItems()
	{
		$data = array();
		$sequra_cart_info = WC()->session->get('sequra_cart_info');
		$data['currency'] = get_woocommerce_currency();
		$data['cart_ref'] = $sequra_cart_info['ref'];
		$data['created_at'] = $sequra_cart_info['created_at'];
		$data['updated_at'] = date('c');
		$data['gift'] = false;

		$data['delivery_method'] = $this->getDeliveryMethod();
		$data['order_total_with_tax'] = self::integerPrice($this->_cart->total);
		$data['order_total_without_tax'] = self::integerPrice($this->_cart->total - $this->_cart->tax_total);
		$data['items'] = array_merge(
			$this->items(),
			$this->handlingItems()
		);
		return $data;
	}

	public function getSequraCartInfo()
	{

	}

	public function getDeliveryMethod()
	{
		$chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');
		return array(
			'name' => self::notNull($chosen_shipping_methods[0]),
			'days' => wc_cart_totals_shipping_method_label($chosen_shipping_methods[0]),
//            'provider' => self::notNull($carrierInfos[0]),
		);
	}

	private function getCartcontents()
	{
//		if ($this->_order->id)
//			return $this->_order->get_items();
		return $this->_cart->cart_contents;
	}

	public function items()
	{
		$items = array();
		foreach ($this->getCartContents() as $cart_item_key => $cart_item) {
			$_product_id = $cart_item['product_id'];
			$_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
			$item = array();
			$item["reference"] = self::notNull($_product->get_sku());
			$name = apply_filters('woocommerce_cart_item_name', $_product->get_title(), $cart_item, $cart_item_key);
			$item["name"] = $name;
			$item["price_without_tax"] = self::integerPrice(self::notNull($_product->get_price_excluding_tax()));
			$item["price_with_tax"] = self::integerPrice(self::notNull($_product->get_price_including_tax()));
			$item["quantity"] = (int)$cart_item['quantity'];
			$item["tax_rate"] = self::integerPrice(self::notNull($cart_item['line_tax'] / $cart_item['line_total']));
			//self::integerPrice(self::notNull($cart_item['line_total']));
			$item["total_without_tax"] = $item["quantity"] * $item["price_without_tax"];
			//self::integerPrice(self::notNull($cart_item['line_total']+$cart_item['line_total_tax']));
			$item["total_with_tax"] = $item["quantity"] * $item["price_with_tax"];
			$item["downloadable"] = $_product->is_downloadable();

			// OPTIONAL
			$item["description"] = self::notNull(get_post($_product_id)->post_content);
			$item["product_id"] = self::notNull($_product_id);
			$item["url"] = self::notNull(get_permalink($_product_id));
			$item["category"] = self::notNull(strip_tags($_product->get_categories()));
			/*@TODO: $item["manufacturer"] but it is not wooCommerce stantdard attribute*/
			$items[] = $item;
		}

		//order discounts
		if ($this->_cart->discount_total != 0) {
			$discount = $this->_cart->discount_total;
			if ($this->_cart->discount_total > 0) {
				$discount = -1 * $this->_cart->discount_total;
			}
			//$discountExclTax=$discount*1.21; //What kind of tax?
			$item = array();
			$item["type"] = "discount";
			$item["reference"] = self::notNull($this->_cart->applied_coupons[0]);
			$item["name"] = 'Descuento';
			$item["total_without_tax"] = self::integerPrice($discount);
			$item["total_with_tax"] = self::integerPrice($discount);
			$items[] = $item;
		}
		//add Customer fee (without tax)

		foreach ($this->_cart->fees as $fee_key => $fee) {
			$item["type"] = $fee_key;
			$item["total_with_tax"] = $item["total_without_tax"] = self::integerPrice($fee->amount);
			$item["tax_rate"] = 0;
			if ($fee->tax) {
				$item["total_with_tax"] += self::integerPrice($fee->tax);
				$item["tax_rate"] = self::integerPrice($fee->tax / $fee->amount);
			}
			$items[] = $item;
		}

		return $items;
	}

	public function handlingItems()
	{
		$delivery = $this->getDeliveryMethod();
		if (!$delivery['name']) {
			return array();
		}

		$handling = array(
			'type' => 'handling',
			'reference' => $delivery['name'],
			'name' => $delivery['name'],
			'total_without_tax' => self::integerPrice($this->_cart->shipping_total),
			'total_with_tax' => self::integerPrice($this->_cart->shipping_total + $this->_cart->shipping_tax_total),
			'tax_rate' => 0,
		);
		if (0 < $this->_cart->shipping_total)
			$handling['tax_rate'] = self::integerPrice($this->_cart->shipping_tax_total / $this->_cart->shipping_total);
		if ($delivery['days'])
			$handling['days'] = $delivery['days'];

		return array($handling);
	}

	public function deliveryAddress()
	{
		$data = array();
		$data['given_names'] = self::notNull($this->_order->shipping_first_name);
		$data['surnames'] = self::notNull($this->_order->shipping_last_name);
		$data['company'] = self::notNull($this->_order->shipping_company);
		$data['address_line_1'] = self::notNull($this->_order->shipping_address_1);
		$data['address_line_2'] = self::notNull($this->_order->shipping_address_2);
		$data['postal_code'] = self::notNull($this->_order->shipping_postcode);
		$data['city'] = self::notNull($this->_order->shipping_city);
		if ($data['city'] == '')
			throw new Exception('City is required');
		$data['country_code'] = self::notNull($this->_order->shipping_country);
		// OPTIONAL
		$data['state'] = self::notNull($this->_order->shipping_state);
		$data['mobile_phone'] = self::notNull($this->_order->shipping_phone);
		/*TODO: Search vat/nif common plugins*/
		$data['vat_number'] = self::notNull($this->_order->shipping_nif);
		if ('' == $data['vat_number'])
			$data['vat_number'] = self::notNull($this->_order->shipping_vat);
		return $data;
	}

	public function deliveryMethod()
	{
		return;
	}

	public function invoiceAddress()
	{
		$data = array();
		$data['given_names'] = self::notNull($this->_order->billing_first_name);
		$data['surnames'] = self::notNull($this->_order->billing_last_name);
		$data['company'] = self::notNull($this->_order->billing_company);
		$data['address_line_1'] = self::notNull($this->_order->billing_address_1);
		$data['address_line_2'] = self::notNull($this->_order->billing_address_2);
		$data['postal_code'] = self::notNull($this->_order->billing_postcode);
		$data['city'] = self::notNull($this->_order->billing_city);
		$data['country_code'] = self::notNull($this->_order->billing_country);
		// OPTIONAL
		$data['state'] = self::notNull($this->_order->billing_state);
		$data['mobile_phone'] = self::notNull($this->_order->billing_phone);
		$data['vat_number'] = self::notNull($this->_order->billing_nif);
		if ('' == $data['vat_number'])
			$data['vat_number'] = self::notNull($this->_order->billing_vat);
		return $data;
	}

	/**
	 * Get SeQura language code
	 * */
	static function _getLanguange()
	{
		$lng = substr(get_bloginfo('language'), 0, 2);
		if (function_exists('qtrans_getLanguage'))
			$lng = qtrans_getLanguage();
		if (defined('ICL_LANGUAGE_CODE'))
			$lng = ICL_LANGUAGE_CODE;
		return $lng;
	}

	public function customer()
	{
		$data = array();
		$data['language_code'] = self::notNull(self::_getLanguange());
		$data['ip_number'] = $_SERVER["REMOTE_ADDR"];
		$data['user_agent'] = $_SERVER["HTTP_USER_AGENT"];
		$data['logged_in'] = is_user_logged_in();
		$id = $data['logged_in'] ? get_current_user_id() : -1;

		$data['given_names'] = $this->getCustomerField($id, 'first_name');
		$data['surnames'] = $this->getCustomerField($id, 'last_name');
		$data['email'] = $this->getCustomerField($id, 'billing_email');
		// OPTIONAL
		$data['date_of_birth'] = self::dateOrBlank($this->getCustomerField($id, 'dob'));
		$data['company'] = $this->getCustomerField($id, 'billing_company');
		if ($id > 0)
			$data['ref'] = $id;
		$data['previous_orders'] = self::getPreviousOrders($id);
		return $data;
	}

	public function getCustomerField($id, $field_name)
	{
		if (0 < $id && $ret = get_user_meta($id, $field_name, true))
			return $ret;

		$var = 'billing_' . str_replace('billing_', '', $field_name);
		return self::notNull($this->_order->$var);
	}

	public function getPreviousOrders($customer_id)
	{
		$args = array(
			'numberposts' => -1,
			'meta_key' => '_customer_user',
			'meta_value' => $customer_id,
			'post_type' => 'shop_order',
			'post_status' => 'publish',
		);
		$posts = get_posts($args);
		$order_ids = wp_list_pluck($posts, 'ID');
		foreach ($order_ids as $id) {
			$prev_order = new WC_Order($id);
			$post = get_post($id);
			$order['amount'] = self::integerPrice($prev_order->get_total());
			$order['currency'] = $prev_order->get_order_currency();
			$date = strtotime($post->post_date);
			$order['created_at'] = date('c', $date);
			$orders[] = $order;
		}
		return $orders;
	}

	public static function getSentOrderIds()
	{
		$args = array(
			'numberposts' => -1,
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key' => '_sent_to_sequra',
					'compare' => 'NOT EXISTS'
				),
				array(
					'key' => '_payment_method',
					'compare' => '=',
					'value' => 'sequra',
				)

			),
			'post_type' => 'shop_order',
			'post_status' => 'publish',
			/*'tax_query'=>array(
				array(
					'taxonomy' =>'shop_order_status',
					'field' => 'slug',
					'terms' => 'completed',
					'operator' => '='
				)
			)*/
		);
		$results = new WP_Query( $args);
		$kk = $results->request;
		$posts = get_posts($args);
		return wp_list_pluck($posts, 'ID');
	}

	public function getStats()
	{
		$args = array(
			'numberposts' => -1,
			'meta_key' => '_customer_user',
			'meta_value' => $customer_id,
			'post_type' => 'shop_order',
			'post_status' => 'publish',
			/*'tax_query'=>array(
				array(
					'taxonomy' =>'shop_order_status',
					'field' => 'slug',
					'terms' =>$status
				)
			)*/
		);
		$posts = get_posts($args);
		$order_ids = wp_list_pluck($posts, 'ID');
		foreach ($order_ids as $id) {
			$prev_order = new WC_Order($id);
			$post = get_post($id);
			$order['amount'] = self::integerPrice($prev_order->get_total());
			$order['currency'] = $prev_order->get_order_currency();
			$date = strtotime($post->post_date);
			$order['created_at'] = date('c', $date);
			$orders[] = $order;
		}
		return $orders;
	}


	public function platform()
	{
		$sql = "show variables like 'version';";
		global $wpdb;
		$db_version = $wpdb->get_var($sql);
		$plugin_data = get_plugin_data(dirname(__FILE__) . '/gateway-sequra.php');

		$data = array(
			'name' => 'WooCommerce',
			'version' => self::notNull(WOOCOMMERCE_VERSION),
			'plugin_version' => (string)$plugin_data['Version'],
			'php_version' => phpversion(),
			'php_os' => PHP_OS,
			'uname' => php_uname(),
			'db_name' => 'mysql',
			'db_version' => $db_version
		);
		return $data;
	}
}
