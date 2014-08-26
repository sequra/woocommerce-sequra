<?php
class SequraHelper
{
	/* Payment Method */
	private $_pm;
	/* Sequra Client */
	private $_client;
	/* Json builder */
	private $_builder;

	public function __construct($pm)
	{
		$this->_pm = $pm;
		$this->dir = WP_PLUGIN_DIR . "/" . plugin_basename(dirname(__FILE__)) . '/';
	}

	function get_identity_form()
	{
		$client = $this->getClient();
		$builder = $this->getBuilder();
		try {
			$order = $builder->build();
		} catch (Exception $e) {
			if ($this->_pm->debug == 'yes')
				$this->_pm->log->add('sequra', $e->getMessage());
			return '';
		}

		$client->startSolicitation($order);
		if ($client->succeeded()) {
			$uri = $client->getOrderUri();
			WC()->session->set('sequraURI',$uri);
			return $client->getIdentificationForm($uri, true);
		}
	}

	function get_approval($order)
	{
		$client = $this->getClient();
		$data = $this->getBuilder($order)->build('confirmed');
		$uri = WC()->session->get('sequraURI');
		$client->updateOrder($uri, $data);
		update_post_meta((int)$order->id, 'Transaction ID', WC()->session->get('sequraURI'));
		update_post_meta((int)$order->id, 'Transaction Status', $client->getStatus());
		/*TODO: Store more information for later use in stats, like browser*/
		return $client->succeeded();
	}

	public function getClient()
	{
		if ($this->_client instanceof SequraClient)
			return $this->_client;
		if (!class_exists('SequraClient')) require_once($this->dir . 'lib/SequraClient.php');
		SequraClient::$endpoint = $this->_pm->endpoint;
		SequraClient::$user = $this->_pm->user;
		SequraClient::$password = $this->_pm->password;
		SequraClient::$user_agent = 'cURL WooCommerce ' . WOOCOMMERCE_VERSION . ' php ' . phpversion();
		$this->_client = new SequraClient();

		return $this->_client;
	}

	public function getBuilder($order=null)
	{
		if ($this->_builder instanceof SequraClient)
			return $this->_builder;

		if (!class_exists('SequraBuilderAbstract')) require_once($this->dir . 'lib/SequraBuilderAbstract.php');
		if (!class_exists('SequraTempOrder')) require_once($this->dir . 'SequraTempOrder.php');
		if (!class_exists('SequraBuilderWC')) require_once($this->dir . 'SequraBuilderWC.php');
		$this->_builder = new SequraBuilderWC($this->_pm->merchantref,$order);

		return $this->_builder;
	}
}
