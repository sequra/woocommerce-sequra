<?php

class SequraNABuilder extends SequraBuilderWC {

	public function merchant() {
		$ret                                       = parent::merchant();
		$ret['options']['addresses_may_be_missing'] = true;

		return $ret;
	}

	public function deliveryAddress() {
		return null;
	}

	public function invoiceAddress() {
		return null;
	}
}
