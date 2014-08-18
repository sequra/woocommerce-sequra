<?php
class SequraTempOrder
{
	public $data = array();
    public function __construct($post_data)
    {
        parse_str($post_data,$this->data);
    }
	public function __call($name, $arguments)
	{
		return $this->data[substr($name,4)];
	}
}
