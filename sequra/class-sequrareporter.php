<?php
/**
 * SeQura Reporter class.
 *
 * @package woocommerce-sequra
 */

/**
 * SeQura Reporter Class
 * */
class SequraReporter {

	/**
	 * Send delivery report, return number of orders added in the report or false.
	 *
	 * @return bool|int
	 */
	public static function send_daily_delivery_report() {
		$helper  = new SequraHelper();
		$builder = $helper->get_builder();
		$builder->buildDeliveryReport();
		$client = $helper->get_client();
		$client->sendDeliveryReport( $builder->getDeliveryReport() );
		$status = $client->getStatus();
		if ( 204 === (int) $status ) {
			$shipped_ids = $builder->getShippedOrderIds();
			self::set_orders_as_sent( $shipped_ids );
			return count( $shipped_ids );
		} elseif ( 200 <= $status && 299 >= $status || 409 === (int) $status ) {
			$x = json_decode( $client->result, true ); // return array, not object.
			return $x;
		}
		return false;
	}
	/**
	 * Set orders as sent so tha they are not added in future reports
	 *
	 * @param array $ids Orders ids.
	 * @return void
	 */
	public static function set_orders_as_sent( $ids ) {
		foreach ( $ids as $id ) {
			update_post_meta( (int) $id, '_sent_to_sequra', gmdate( 'c' ) );
		}
	}
}
