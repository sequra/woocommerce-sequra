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
use SeQura\Helper\Task\Remove_Address_Fields_Task;
use SeQura\Helper\Task\Print_Logs_Task;
use SeQura\Helper\Task\Remove_Db_Tables_Task;
use SeQura\Helper\Task\Remove_Log_Task;
use SeQura\Helper\Task\Set_Theme_Task;
use SeQura\Helper\Task\Task;
use SeQura\Helper\Task\Verify_Order_Has_Merchant_Id_Task;

/**
 * SeQura Helper Plugin
 */
class Plugin {

	/**
	 * File path
	 *
	 * @var string
	 */
	private $file_path;
	
	/**
	 * Constructor
	 * 
	 * @param string $file_path The plugin file path.
	 */
	public function __construct( string $file_path ) {
		$this->file_path = $file_path;
		add_action( 'init', array( $this, 'handle_webhook' ) );
		// WooCommerce Compat.
		add_action( 'before_woocommerce_init', array( $this, 'declare_woocommerce_compatibility' ) );
		if ( defined( 'SQ_STOP_HEARTBEAT' ) && SQ_STOP_HEARTBEAT ) {
			add_action( 'init', array( $this, 'stop_heartbeat' ), 1 );
		}
		// This disable entirely the coming soon page functionality from WooCommerce that affects the E2E tests.
		add_filter( 'woocommerce_coming_soon_exclude', '__return_true', 10 );

		if ( Remove_Address_Fields_Task::is_option_enabled() ) {
			add_filter( 'woocommerce_checkout_fields', array( $this, 'remove_checkout_fields_classic' ) );
			add_filter( 'woocommerce_get_country_locale', array( $this, 'remove_checkout_fields_blocks' ) );
		}
	}

	/**
	 * Remove address fields from classic checkout form
	 *
	 * @param array<string, array<string, mixed>> $fields Fields.
	 * @return array<string, array<string, mixed>>
	 */
	public function remove_checkout_fields_classic( $fields ) {
		unset( $fields['billing']['billing_company'] );
		unset( $fields['billing']['billing_address_1'] );
		unset( $fields['billing']['billing_address_2'] );
		unset( $fields['billing']['billing_city'] );
		unset( $fields['billing']['billing_postcode'] );
		unset( $fields['billing']['billing_country'] );
		unset( $fields['billing']['billing_state'] );

		unset( $fields['shipping']['shipping_company'] );
		unset( $fields['shipping']['shipping_address_1'] );
		unset( $fields['shipping']['shipping_address_2'] );
		unset( $fields['shipping']['shipping_city'] );
		unset( $fields['shipping']['shipping_postcode'] );
		unset( $fields['shipping']['shipping_country'] );
		unset( $fields['shipping']['shipping_state'] );

		return $fields;
	}
	/**
	 * Remove address fields from blocks based checkout form
	 *
	 * @param array<string, array<string, mixed>> $locale Locale.
	 * @return array<string, array<string, mixed>>
	 */
	public function remove_checkout_fields_blocks( $locale ) {
		// Loop every country locale we sell to and hide specific fields.
		foreach ( $locale as $country_code => $fields ) {
			$locale[ $country_code ]['address_1'] = array(
				'required' => false,
				'hidden'   => true,
			);

			$locale[ $country_code ]['address_2'] = array(
				'required' => false,
				'hidden'   => true,
			);

			$locale[ $country_code ]['city'] = array(
				'required' => false,
				'hidden'   => true,
			);

			$locale[ $country_code ]['postcode'] = array(
				'required' => false,
				'hidden'   => true,
			);

			$locale[ $country_code ]['state'] = array(
				'required' => false,
				'hidden'   => true,
			);

			$locale[ $country_code ]['company'] = array(
				'required' => false,
				'hidden'   => true,
			);
		}

		return $locale;
	}

	/**
	 * Disable heartbeat to ease debugging
	 */
	public function stop_heartbeat(): void {
		wp_deregister_script( 'heartbeat' );
	}

	/**
	 * Get task for webhook
	 * 
	 * @param string $webhook The webhook name.
	 */
	private function get_task_for_webhook( $webhook ): Task {

		$map = array(
			'dummy_services_config'        => Configure_Dummy_Service_Task::class,
			'dummy_config'                 => Configure_Dummy_Task::class,
			'clear_config'                 => Clear_Configuration_Task::class,
			'remove_log'                   => Remove_Log_Task::class,
			'print_logs'                   => Print_Logs_Task::class,
			'set_theme'                    => Set_Theme_Task::class,
			'cart_version'                 => Cart_Version_Task::class,
			'checkout_version'             => Checkout_Version_Task::class,
			'remove_db_tables'             => Remove_Db_Tables_Task::class,
			'verify_order_has_merchant_id' => Verify_Order_Has_Merchant_Id_Task::class,
			'remove_address_fields'        => Remove_Address_Fields_Task::class,
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

	/**
	 * Declare WooCommerce compatibility.
	 */
	public function declare_woocommerce_compatibility(): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', $this->file_path, true );
		}
	}
}
