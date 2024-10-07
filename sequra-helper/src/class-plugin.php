<?php
/**
 * SeQura Helper Plugin
 * 
 * @package SeQura_Helper
 */

namespace SeQura\Helper;

use SeQura\Helper\Task\Cart_Version_Task;
use SeQura\Helper\Task\Checkout_Version_Task;
use SeQura\Helper\Task\Clear_Configuration_Task;
use SeQura\Helper\Task\Configure_Dummy_Service_Task;
use SeQura\Helper\Task\Configure_Dummy_Task;
use SeQura\Helper\Task\Force_Order_Failure_Task;
use SeQura\Helper\Task\Get_Plugin_Zip_Task;
use SeQura\Helper\Task\Print_Logs_Task;
use SeQura\Helper\Task\Remove_Db_Tables_Task;
use SeQura\Helper\Task\Remove_Log_Task;
use SeQura\Helper\Task\Set_Theme_Task;
use SeQura\Helper\Task\Task;

// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.NonceVerification.Recommended

/**
 * SeQura Helper Plugin
 */
class Plugin {
	
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'handle_webhook' ) );
	}

	/**
	 * Get task for webhook
	 */
	private function get_task_for_webhook( $webhook ): Task {

		$map = array(
			'dummy_services_config' => Configure_Dummy_Service_Task::class,
			'dummy_config'          => Configure_Dummy_Task::class,
			'clear_config'          => Clear_Configuration_Task::class,
			'force_order_failure'   => Force_Order_Failure_Task::class,
			'remove_log'            => Remove_Log_Task::class,
			'print_logs'            => Print_Logs_Task::class,
			'set_theme'             => Set_Theme_Task::class,
			'cart_version'          => Cart_Version_Task::class,
			'checkout_version'      => Checkout_Version_Task::class,
			'plugin_zip'            => Get_Plugin_Zip_Task::class,
			'remove_db_tables'      => Remove_Db_Tables_Task::class,

		);

		return ! isset( $map[ $webhook ] ) ? new Task() : new $map[ $webhook ]();
	}

	/**
	 * Handle webhook
	 */
	public function handle_webhook(): void {
		if ( ! isset( $_GET['sq-webhook'] ) ) {
			return;
		}

		$task = $this->get_task_for_webhook( sanitize_text_field( $_GET['sq-webhook'] ) );
		
		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			$task->http_error_response( 'Invalid request method', 405 );
		}

		try {
			$task->execute( $_REQUEST );
			$task->http_success_response();
		} catch ( \Exception $e ) {
			$task->http_error_response( $e->getMessage(), (int) $e->getCode() );
		}
	}
}
