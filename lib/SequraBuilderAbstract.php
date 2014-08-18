<?php

abstract class SequraBuilderAbstract
{

    protected $merchant_id;

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
            'state' => $state
        );
        if ('confirmed' == $state)
            $order['merchant_reference'] = array(
                'order_ref_1' => self::notNull($this->getOrderRef(1)),
                'order_ref_2' => self::notNull($this->getOrderRef(2))
            );
        return $order;
    }

    public function merchant()
    {
        return array(
            'id' => $this->merchant_id,
        );
    }

    public abstract function cartWithItems();

    public abstract function deliveryMethod();

    public abstract function items();
    public abstract function handlingItems();

    public abstract function customer();

    protected static function dateOrBlank($date)
    {
        return $date ? date_format(date_create($date), 'Y-m-d') : '';
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

    protected static function notNull($value1)
    {
        return is_null($value1) ? '' : $value1;
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