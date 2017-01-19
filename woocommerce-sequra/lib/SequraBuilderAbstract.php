<?php

/**
 * Class SequraBuilderAbstract
 */
abstract class SequraBuilderAbstract
{

	protected $merchant_id;
	protected $_current_order = null;
	protected $_orders = array();
	protected $_broken_orders = array();
	protected $_delivery_report = null;

	public function __construct($merchant_id)
	{
		$this->merchant_id = $merchant_id;
	}

	public function build($state = null)
	{
		$order = array(
			'merchant' => $this->merchant(),
			'cart' => $this->cartWithItems(),
			'delivery_address' => $this->deliveryAddress(),
			'invoice_address' => $this->invoiceAddress(),
			'customer' => $this->customer(),
			'gui' => $this->gui(),
			'platform' => $this->platform(),
			'state' => self::notNull($state)
		);
		$order = $this->fixTotals($order);
		if ('confirmed' == $state)
			$order['merchant_reference'] = $this->orderMerchantReference();

		return $order;
	}

	public function fixTotals($order)
	{
		$totals = self::totals($order['cart']);
		$diff_without_tax = $order['cart']['order_total_without_tax'] - $totals['without_tax'];
		$diff_with_tax = $order['cart']['order_total_with_tax'] - $totals['with_tax'];
		$diff_max = abs(max($diff_with_tax, $diff_without_tax));
		if ($diff_max == 0 || $diff_max > count($order['cart']['items']))
			return $order;
		$items = array();
		foreach ($order['cart']['items'] as $item) {
			if ('discount' == $item['type']) {
				$item['total_without_tax'] += $diff_without_tax;
				$item['total_with_tax'] += $diff_with_tax;
				$item['name'] .= (''==$item['name']?'':' + ') . 'ajuste';
			}
			$items[] = $item;
		}
		$order['cart']['items'] = $items;
		return $order;
	}

	public function buildDeliveryReport()
	{
		$this->buildShippedOrders();
		$this->buildBrokenOrders();
		$this->_delivery_report = array(
			'merchant' => $this->merchant(),
			'orders' => $this->_orders,
			'broken_orders' => $this->_broken_orders,
			'statistics' => array('orders' => $this->getOrderStats()),
			'platform' => $this->platform()
		);
	}

	public function getDeliveryReport()
	{
		if (is_null($this->_delivery_report))
			$this->buildDeliveryReport();
		return $this->_delivery_report;
	}

	public function merchant()
	{
		return array(
			'id' => $this->merchant_id,
		);
	}

	public abstract function buildShippedOrders();

	function buildBrokenOrders()
	{
		foreach ($this->_orders as $key => $order) {
			if (!self::isConsistentCart($order['cart'])) {
				$this->_broken_orders[] = $order;
				unset($this->_orders[$key]);
			}
		}
	}

	public function orderMerchantReference()
	{
		$ret = array();
		$ref = self::notNull($this->getOrderRef(1));
		if (''!=$ref)
			$ret['order_ref_1'] = $ref;
		$ref = self::notNull($this->getOrderRef(2));
		if (''!=$ref)
			$ret['order_ref_2'] = $ref;
		return $ret;
	}

	public abstract function getOrderStats();

	public abstract function cartWithItems();

	public abstract function deliveryMethod();

	public function items()
	{
		return array_merge(
			$this->productItem(),
			$this->extraItems(),
			$this->handlingItems()
		);
	}

	public abstract function handlingItems();

	public abstract function customer();

	protected static function dateOrBlank($date)
	{
		return $date ? date_format(date_create($date), 'Y-m-d') : '';
	}

	public static function isConsistentCart($cart)
	{
		$totals = self::totals($cart);
		return $cart['order_total_without_tax'] == $totals['without_tax'] && $cart['order_total_with_tax'] == $totals['with_tax'];
	}

	public static function totals($cart)
	{
		$total_without_tax = $total_with_tax = 0;
		foreach ($cart['items'] as $item) {
			$total_without_tax += $item['total_without_tax'];
			$total_with_tax += $item['total_with_tax'];
		}
		return array('without_tax' => $total_without_tax, 'with_tax' => $total_with_tax);
	}

	public abstract function getPreviousOrders($customerID);

	public function gui()
	{
		$data = array(
			'layout' => $this->isMobile() ? 'mobile' : 'desktop',
		);
		return $data;
	}

	public abstract function platform();

	static $centsPerWhole = 100;

	public static function integerPrice($price)
	{
		return intval(round(self::$centsPerWhole * $price));
	}

	protected static function notNull($value1, $default = '')
	{
		return is_null($value1) ? $default : $value1;
	}

	// TODO: find out were this method was copied from so that we can see when it is updated.
	protected static function isMobile()
	{
		$regex_match = "/(nokia|iphone|android|motorola|^mot\-|softbank|foma|docomo|kddi|up\.browser|up\.link|"
			. "htc|dopod|blazer|netfront|helio|hosin|huawei|novarra|CoolPad|webos|techfaith|palmsource|"
			. "blackberry|alcatel|amoi|ktouch|nexian|samsung|^sam\-|s[cg]h|^lge|ericsson|philips|sagem|wellcom|bunjalloo|maui|"
			. "symbian|smartphone|mmp|midp|wap|phone|windows ce|iemobile|^spice|^bird|^zte\-|longcos|pantech|gionee|^sie\-|portalmmm|"
			. "jig\s browser|hiptop|^ucweb|^benq|haier|^lct|opera\s*mobi|opera\*mini|320x320|240x320|176x220"
			. ")/i";

		if (preg_match($regex_match, strtolower($_SERVER['HTTP_USER_AGENT']))) {
			return true;
		}

		if ((strpos(strtolower($_SERVER['HTTP_ACCEPT']), 'application/vnd.wap.xhtml+xml') > 0) or ((isset($_SERVER['HTTP_X_WAP_PROFILE']) or isset($_SERVER['HTTP_PROFILE'])))) {
			return true;
		}

		$mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
		$mobile_agents = array(
			'w3c ', 'acs-', 'alav', 'alca', 'amoi', 'audi', 'avan', 'benq', 'bird', 'blac',
			'blaz', 'brew', 'cell', 'cldc', 'cmd-', 'dang', 'doco', 'eric', 'hipt', 'inno',
			'ipaq', 'java', 'jigs', 'kddi', 'keji', 'leno', 'lg-c', 'lg-d', 'lg-g', 'lge-',
			'maui', 'maxo', 'midp', 'mits', 'mmef', 'mobi', 'mot-', 'moto', 'mwbp', 'nec-',
			'newt', 'noki', 'oper', 'palm', 'pana', 'pant', 'phil', 'play', 'port', 'prox',
			'qwap', 'sage', 'sams', 'sany', 'sch-', 'sec-', 'send', 'seri', 'sgh-', 'shar',
			'sie-', 'siem', 'smal', 'smar', 'sony', 'sph-', 'symb', 't-mo', 'teli', 'tim-',
			'tosh', 'tsm-', 'upg1', 'upsi', 'vk-v', 'voda', 'wap-', 'wapa', 'wapi', 'wapp',
			'wapr', 'webc', 'winw', 'winw', 'xda ', 'xda-');

		if (in_array($mobile_ua, $mobile_agents)) {
			return true;
		}

		if (isset($_SERVER['ALL_HTTP']) && strpos(strtolower($_SERVER['ALL_HTTP']), 'OperaMini') > 0) {
			return true;
		}

		return false;
	}
}