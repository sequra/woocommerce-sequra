<?php

class SequraClient
{

    public static $endpoint = '';
    public static $user = '';
    public static $password = '';
    public static $user_agent = null;

    protected $_endpoint;
    protected $_user;
    protected $_password;
    protected $_user_agent;

    protected $success;
    protected $cart_has_changed;
    protected $status;
    protected $curl_result = null;
    protected $json = null;

    public function __construct($user = null, $password = null, $endpoint = null)
    {
        $this->_user = self::notNull($user, self::$user);
        $this->_password = self::notNull($password, self::$password);
        $this->_endpoint = self::notNull($endpoint, self::$endpoint);
        $this->_user_agent = self::notNull(self::$user_agent, 'cURL php ' . phpversion());
    }

    public function startSolicitation($order)
    {
        $this->initCurl($this->_endpoint);
        $this->verbThePayload('POST', array('order' => $order));
        if ($this->status == 204) {
            $this->success = true;
            $this->logToFile("Start " . $this->status . ": Ok!");
        } elseif ($this->status >= 200 && $this->status <= 299 || $this->status == 409) {
            $this->json = json_decode($this->curl_result, true); // return array, not object
            $this->logToFile("Start " . $this->status . ": " . $this->curl_result);
        }

        curl_close($this->ch);
    }

    public function getIdentificationForm($uri)
    {
        $this->initCurl($uri . '/id_form');
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('Accept: text/html'));
        $this->sendRequest();

        if ($this->status >= 200 && $this->status <= 299) {
            return $this->curl_result;
        } else {
            die(curl_error($this->ch));
        }
        curl_close($this->ch);
    }

    public function updateOrder($uri, $order)
    {
        $this->initCurl($uri);
        $this->verbThePayload('PUT', array('order' => $order));

        if ($this->status >= 200 && $this->status <= 299) {
            $this->success = true;
        } elseif ($this->status == 409) {
            $this->cart_has_changed = true;
            $this->json = json_decode($this->curl_result, true);
        }
        curl_close($this->ch);
    }

    public function sendDeliveryReport($delivery_report)
    {
        $this->initCurl($this->_endpoint . '/delivery_reports');
        $this->verbThePayload('POST', array('delivery_report' => $delivery_report));

        if ($this->status == 204) {
            $this->success = true;
            $this->logToFile("Delivery " . $this->status . ": Ok!");
        } elseif ($this->status >= 200 && $this->status <= 299 || $this->status == 409) {
            $this->json = json_decode($this->curl_result, true); // return array, not object
            $this->logToFile("Delivery " . $this->status . ": " . $this->json);
        }
        curl_close($this->ch);
    }

    public function succeeded()
    {
        return $this->success;
    }

    public function getJson()
    {
        return $this->json;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function cartHasChanged()
    {
        return $this->cart_has_changed;
    }

    public function getOrderUri()
    {
        if (preg_match('/^Location:\s+([^\n\r]+)/mi', $this->headers, $m)) {
            return $m[1];
        }
    }

    // protected methods below

    protected function initCurl($url)
    {
        $this->success = $this->json = null;
        $this->ch = curl_init($url);
        curl_setopt($this->ch, CURLOPT_USERPWD, $this->_user . ':' . $this->_password);
        curl_setopt($this->ch, CURLOPT_USERAGENT, $this->_user_agent);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_FAILONERROR, false);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
        // Some versions of openssl seem to need this
        // http://www.supermind.org/blog/763/solved-curl-56-received-problem-2-in-the-chunky-parser
        curl_setopt($this->ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        // From http://it.toolbox.com/wiki/index.php/Use_curl_from_PHP_-_processing_response_headers
        curl_setopt($this->ch, CURLOPT_HEADERFUNCTION, array(&$this, 'storeHeaders'));
        $this->headers = '';
    }

    protected function storeHeaders($ch, $header)
    {
        $this->headers .= $header;
        return strlen($header);
    }

    protected function verbThePayload($verb, $payload)
    {
        $data_string = json_encode($payload);
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $verb);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json',
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );
        $this->sendRequest();
    }

    protected function sendRequest()
    {
        $this->curl_result = curl_exec($this->ch);
        $this->status = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
    }

    protected static function notNull($value1, $value2)
    {
        return is_null($value1) ? $value2 : $value1;
    }

    const LOG_FILE = null;

    // const LOG_FILE = 'ROOT/log/sequra.log';

    function logToFile($msg)
    {
        if (!self::LOG_FILE) return;
        $path = str_replace('ROOT', $_SERVER['DOCUMENT_ROOT'], self::LOG_FILE);
        $fd = fopen($path, "a");
        $str = "[" . date("Y-m-d H:i:s", time()) . "] " . $msg;
        fwrite($fd, $str . "\n");
        fclose($fd);
    }
}
