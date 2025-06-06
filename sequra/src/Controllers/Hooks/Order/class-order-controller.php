<?php
/**
 * Order controller
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers
 */

namespace SeQura\WC\Controllers\Hooks\Order;

use SeQura\Core\Infrastructure\Logger\LogContextData;
use SeQura\WC\Controllers\Controller;
use SeQura\WC\Services\Interface_Logger_Service;
use SeQura\WC\Services\Order\Interface_Order_Service;
use Throwable;
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

		\add_action( 'admin_notices', array( $this, 'display_notices' ) );
	}

	/**
	 * Add support to custom meta query vars for the order query
	 *
	 * @param array $wp_query_args Args for WP_Query.
	 * @param array $query_vars Query vars from WC_Order_Query.
	 * @param WC_Order_Data_Store_CPT $order_data_store WC_Order_Data_Store instance.
	 * @return array modified $query
	 */
	public function handle_custom_query_vars( $wp_query_args, $query_vars, $order_data_store ) {
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
	 * 
	 * @param int $order_id Order ID.
	 * @param string $old_status Old status.
	 * @param string $new_status New status.
	 * @param WC_Order $order Order object.
	 * 
	 * @return void
	 */
	public function handle_order_status_changed( $order_id, $old_status, $new_status, $order ) {
		$this->logger->log_debug( 'Hook executed', __FUNCTION__, __CLASS__ );
		try {
			$this->order_service->update_sequra_order_status( $order, $old_status, $new_status );
		} catch ( Throwable $e ) {
			$this->logger->log_throwable(
				$e,
				__FUNCTION__,
				__CLASS__,
				array(
					new LogContextData( 'order_id', $order_id ),
					new LogContextData( 'old_status', $old_status ),
					new LogContextData( 'new_status', $new_status ),
				) 
			);
			
			\set_transient(
				$this->get_notices_transient( $order_id ),
				array(
					array(
						'notice'      => __( 'An error occurred while updating the order data in seQura.', 'sequra' ), // TODO: improve message with link to simba.
						'type'        => 'error',
						'dismissible' => true,
					),
				),
				0 
			);
		}
	}

	/**
	 * Get the transient key for notices related to an order
	 * 
	 * @param int $order_id Order ID.
	 */
	private function get_notices_transient( $order_id ): string {
		return 'sequra_notices_order_' . $order_id;
	}

	/**
	 * Display notices related to an order
	 * 
	 * @return void
	 */
	public function display_notices() {
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! \is_admin() || ! isset( $_GET['post'], $_GET['action'] ) || 'edit' !== $_GET['action'] ) {
			return;
		}

		$order = wc_get_order( \absint( $_GET['post'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$transient_key = $this->get_notices_transient( $order->get_id() );
		$notices       = (array) \get_transient( $transient_key );
		if ( ! empty( $notices ) ) {
			\delete_transient( $transient_key );
		}
		foreach ( $notices as $notice ) {
			ob_start();
			\wc_get_template( 'admin/notice.php', $notice, '', $this->templates_path );
			echo ob_get_clean(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Show a link to the seQura back office in the order details page
	 * 
	 * @param WC_Order $order Order object.
	 * @return void
	 */
	public function show_link_to_sequra_back_office( $order ): void {
		$args = array(
			'sequra_link' => $this->order_service->get_link_to_sequra_back_office( $order ),
		);
		\wc_get_template( 'admin/order_details.php', $args, '', $this->templates_path );
	}

	/**
	 * Cleanup orders
	 * 
	 * @return void
	 */
	public function cleanup_orders() {
		$this->logger->log_debug( 'Hook executed', __FUNCTION__, __CLASS__ );
		$this->order_service->cleanup_orders();
	}

	/**
	 * Respond to the sequra_order_migration_add_indexes hook
	 * 
	 * @param string $hook Hook name that triggered the action.
	 */
	public function migrate_orders_to_use_indexes( $hook ) {
		$this->logger->log_debug( 'Hook executed', __FUNCTION__, __CLASS__ );

		if ( $this->order_service->is_migration_complete() ) {
			$this->logger->log_debug( 'Indexing process is done. Job will be unscheduled', __FUNCTION__, __CLASS__ );
			\wp_clear_scheduled_hook( $hook, array( $hook ) );
			return;
		}

		$this->order_service->migrate_data();
	}
}
