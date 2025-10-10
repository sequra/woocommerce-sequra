<?php
/**
 * Task class
 *
 * @package SeQura/Helper
 */

namespace SeQura\Helper\Task;

/**
 * Task class
 */
class Verify_Order_Has_Merchant_Id_Task extends Task {

	/**
	 * Execute the task
	 *
	 * @param array<string, string> $args Arguments for the task.
	 *
	 * @throws \Exception If the task fails.
	 */
	public function execute( array $args = array() ): void {
		if ( ! isset( $args['order_id'], $args['merchant_id'] ) ) {
			throw new \Exception( 'Missing required arguments: order_id OR merchant_id', 400 );
		}

		$order_id    = $args['order_id'];
		$merchant_id = $args['merchant_id'];
		global $wpdb;

		$table_name  = $this->get_sequra_order_table_name();
		$query       = "SELECT `data` FROM $table_name WHERE `index_3` = '$order_id' LIMIT 1";
		$string_data = $wpdb->get_var( $query );
		if ( ! $string_data ) {
			throw new \Exception( 'Order not found', 404 );
		}
		/**
		 * Order data
		 *
		 * @var string $data
		 * @phpstan-var array<string, array<string, string>>
		 */
		$data = json_decode( $string_data, true );
		if ( ! is_array( $data ) || ! is_array( $data['merchant'] ) || ! isset( $data['merchant']['id'] ) ) {
			throw new \Exception( 'Merchant ID not found in order data', 404 );
		}
		/**
		 * Current merchant ID
		 * 
		 * @var string $current_merchant_id
		 */
		$current_merchant_id = $data['merchant']['id'];
		if ( $current_merchant_id !== $merchant_id ) {
			throw new \Exception( "Merchant ID '$current_merchant_id' does not match '$merchant_id'", 400 );
		}
	}
}
