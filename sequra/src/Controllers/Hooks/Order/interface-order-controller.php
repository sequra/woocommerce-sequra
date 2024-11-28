<?php
/**
 * Order interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers
 */

namespace SeQura\WC\Controllers\Hooks\Order;

use WC_Order;
use WC_Order_Data_Store_CPT;

/**
 * Handle hooks related to order management
 */
interface Interface_Order_Controller {

	/**
	 * Add support to custom meta query vars for the order query
	 *
	 * @param array $wp_query_args Args for WP_Query.
	 * @param array $query_vars Query vars from WC_Order_Query.
	 * @param WC_Order_Data_Store_CPT $order_data_store WC_Order_Data_Store instance.
	 * @return array modified $query
	 */
	public function handle_custom_query_vars( $wp_query_args, $query_vars, $order_data_store );

	/**
	 * Trigger the sync of the order status with SeQura
	 * 
	 * @param int $order_id Order ID.
	 * @param string $old_status Old status.
	 * @param string $new_status New status.
	 * @param WC_Order $order Order object.
	 * 
	 * @return void
	 */
	public function handle_order_status_changed( $order_id, $old_status, $new_status, $order );

	/**
	 * Display notices related to an order
	 * 
	 * @return void
	 */
	public function display_notices();

	/**
	 * Show a link to the seQura back office in the order details page
	 * 
	 * @param WC_Order $order Order object.
	 * @return void
	 */
	public function show_link_to_sequra_back_office( $order );
}
