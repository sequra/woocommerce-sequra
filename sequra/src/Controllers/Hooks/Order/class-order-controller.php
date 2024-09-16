<?php
/**
 * Order controller
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers
 */

namespace SeQura\WC\Controllers\Hooks\Order;

use SeQura\WC\Controllers\Controller;
use SeQura\WC\Services\Interface_Logger_Service;
use SeQura\WC\Services\Order\Interface_Order_Service;
use WC_Order;

/**
 * Handle hooks related to order management
 */
class Order_Controller extends Controller implements Interface_Order_Controller {

	/**
	 * Order service
	 *
	 * @var Interface_Order_Service
	 */
	private $order_service;

	/**
	 * Constructor
	 */
	public function __construct( 
		Interface_Logger_Service $logger, 
		string $templates_path,
		Interface_Order_Service $order_service 
	) {
		parent::__construct( $logger, $templates_path );
		$this->order_service = $order_service;
	}

	/**
	 * Add support to custom meta query vars for the order query
	 *
	 * @param array $wp_query_args Args for WP_Query.
	 * @param array $query_vars Query vars from WC_Order_Query.
	 * @param WC_Order_Data_Store_CPT $order_data_store WC_Order_Data_Store instance.
	 * @return array modified $query
	 */
	public function handle_custom_query_vars( array $wp_query_args, array $query_vars, $order_data_store ): array {
		$this->logger->log_debug( 'Hook executed', __FUNCTION__, __CLASS__ );
		$custom_query_vars = array( 
			$this->order_service->get_sent_to_sequra_meta_key(),
		);
		foreach ( $query_vars as $key => $value ) {
			if ( isset( $value['compare'] ) && in_array( $key, $custom_query_vars, true ) ) {
				$arg = array(
					'key'     => $key,
					'compare' => $value['compare'],
				);
				if ( isset( $value['value'] ) ) {
					$arg['value'] = $value['value'];
				}
				$wp_query_args['meta_query'][] = $arg;
			}
		}
		return $wp_query_args;
	}

	/**
	 * Trigger the sync of the order status with SeQura
	 */
	public function handle_order_status_changed( int $order_id, string $old_status, string $new_status, WC_Order $order ): void {
		$this->logger->log_debug( 'Hook executed', __FUNCTION__, __CLASS__ );
		$this->order_service->update_sequra_order_status( $order, $old_status, $new_status );
	}
}
