<?php

class SequraReporter
{

	public static function sendDailyDeliveryReport()
	{
		$helper = new SequraHelper(new SequraInvoicePaymentGateway());
		$builder = $helper->getBuilder();
		$builder->buildDeliveryReport();
		$client = $helper->getClient();
		$client->sendDeliveryReport($builder->getDeliveryReport());
		$status= $client->getStatus();
		if ( $status == 204) {
			$shipped_ids = $builder->getShippedOrderIds();
			self::setOrdersAsSent($shipped_ids);
			return count($shipped_ids);
		} elseif ($status >= 200 && $status <= 299 || $status == 409) {
			$x = json_decode($client->result, true); // return array, not object

		}
		return false;
	}

	static function setOrdersAsSent($ids){
		foreach ($ids as $id)
			update_post_meta((int)$id, '_sent_to_sequra', date('c'));
	}
}
