<?php
class SequraTempOrder
{
	public $data = array();
    public function __construct($post_data)
    {
        parse_str($post_data,$this->data);
		$this->shipping_first_name = $this->data['shipping_first_name'];
		$this->shipping_last_name = $this->data['shipping_last_name'];
		$this->shipping_company = $this->data['shipping_company'];
		$this->shipping_address_1 = $this->data['shipping_address_1'];
		$this->shipping_address_2 = $this->data['shipping_address_2'];
		$this->shipping_city = $this->data['shipping_city'];
		$this->shipping_state = $this->data['shipping_state'];
		$this->shipping_postcode = $this->data['shipping_postcode'];
		$this->shipping_country = $this->data['shipping_country'];
    }

	public function __call($name, $arguments)
	{
		return $this->data[substr($name,4)];
	}
}
