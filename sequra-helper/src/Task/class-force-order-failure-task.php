<?php
/**
 * Task class
 * 
 * @package SeQura/Helper
 */

namespace SeQura\Helper\Task;

// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.Security.EscapeOutput.ExceptionNotEscaped

/**
 * Task class
 */
class Force_Order_Failure_Task extends Task {

	/**
	 * Execute the task
	 * 
	 * @throws \Exception If the task fails
	 */
	public function execute( array $args = array() ): void {

		if ( ! isset( $args['order_id'] ) ) {
			throw new \Exception( 'Invalid order ID', 400 );
		} 
		$order_id = absint( $args['order_id'] );
		
		if ( ! $this->force_order_failure( $order_id ) ) {
			throw new \Exception( 'Failed to update order ' . $order_id . ' payload', 500 );
		}
	}

	/**
	 * Update the order payload to force failure
	 */
	private function force_order_failure( int $order_id ): bool {
		global $wpdb;
		$table_name = $this->get_sequra_order_table_name();
		$row        = $wpdb->get_row( "SELECT * FROM $table_name WHERE `type` = 'SeQuraOrder' AND `index_3` = '$order_id'" );

		if ( ! $row ) {
			return false;
		}

		$data = json_decode( $row->data, true );
		if ( ! $data ) {
			return false;
		}

		// Sum 5000 cents to the totals to exceed the approved amount.
		$plus                 = 5000;
		$order_total_with_tax = 0;
		if ( isset( $data['unshipped_cart']['items'] ) ) {
			foreach ( $data['unshipped_cart']['items'] as $key => &$item ) {
				$item['total_with_tax'] += $plus;
				$order_total_with_tax   += $plus;
			}
		}
		if ( isset( $data['unshipped_cart']['order_total_with_tax'] ) ) {
			$data['unshipped_cart']['order_total_with_tax'] += $order_total_with_tax;
		}

		$wpdb->update(
			$table_name,
			array(
				'data' => wp_json_encode( $data ),
			),
			array( 'id' => $row->id )
		);

		return true;
	}
}
