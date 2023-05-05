<?php

class SequraNABuilder extends SequraBuilderWC {
	/**
	 * Undocumented function
	 *
	 * @return array
	 */
	public function merchant() {
		$ret                                        = parent::merchant();
		$ret['options']['addresses_may_be_missing'] = true;

		return $ret;
	}
	/**
	 * Undocumented function
	 *
	 * @return null
	 */
	public function deliveryAddress() {
		return null;
	}
	/**
	 * Undocumented function
	 *
	 * @return null
	 */
	public function invoiceAddress() {
		return null;
	}
}
