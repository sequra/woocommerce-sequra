<?php
/**
 * Post install migration for version 4.2.0 of the plugin.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Repositories
 */

namespace SeQura\WC\Repositories\Migrations;

use SeQura\Core\BusinessLogic\Domain\AdvancedSettings\Models\AdvancedSettings;
use SeQura\Core\BusinessLogic\Domain\AdvancedSettings\Services\AdvancedSettingsService;
use SeQura\Core\BusinessLogic\Domain\Order\OrderStates;
use SeQura\Core\BusinessLogic\Domain\OrderStatusSettings\Models\OrderStatusMapping;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\OrderStatusSettings\Services\Order_Status_Settings_Service;
use SeQura\WC\Repositories\Interface_Cache_Repository;
use Throwable;

/**
 * Post install migration for version 4.2.0 of the plugin.
 */
class Migration_Install_420 extends Migration {

	/**
	 * Entity table.
	 *
	 * @var string
	 */
	private $entity_table;

	/**
	 * Advanced settings service.
	 *
	 * @var AdvancedSettingsService
	 */
	private $advanced_settings_service;

	/**
	 * Order status settings service.
	 *
	 * @var Order_Status_Settings_Service
	 */
	private $order_status_settings_service;

	/**
	 * Get the plugin version when the changes were made.
	 */
	public function get_version(): string {
		return '4.2.0';
	}

	/**
	 * Constructor
	 *
	 * @param \wpdb                        $wpdb                          Database instance.
	 * @param AdvancedSettingsService       $advanced_settings_service     Advanced settings service.
	 * @param Order_Status_Settings_Service $order_status_settings_service Order status settings service.
	 */
	public function __construct( \wpdb $wpdb, Interface_Cache_Repository $cache, AdvancedSettingsService $advanced_settings_service, Order_Status_Settings_Service $order_status_settings_service ) {
		parent::__construct( $wpdb, $cache );
		$this->entity_table                  = $this->db->prefix . 'sequra_entity';
		$this->advanced_settings_service     = $advanced_settings_service;
		$this->order_status_settings_service = $order_status_settings_service;
	}

	/**
	 * Migrate Advanced settings to the new format
	 *
	 * @throws Throwable
	 */
	public function migrate_advanced_settings(): void {
		// Skip migration if the new Advanced settings are already set.
		if ( $this->advanced_settings_service->getAdvancedSettings() ) {
			return;
		}
	
		// @phpstan-ignore-next-line
		$query              = $this->db->prepare( 'SELECT `data` FROM ' . $this->entity_table . ' WHERE `type` = %s AND `index_1` = %s LIMIT 1', 'Configuration', 'defaultLoggerEnabled' );
		$logger_enabled_row = $this->db->get_row( $query, ARRAY_A );

		// @phpstan-ignore-next-line
		$query         = $this->db->prepare( 'SELECT `data` FROM ' . $this->entity_table . ' WHERE `type` = %s AND `index_1` = %s LIMIT 1', 'Configuration', 'minLogLevel' );
		$log_level_row = $this->db->get_row( $query, ARRAY_A );

		if ( null === $logger_enabled_row && null === $log_level_row ) {
			return;
		}

		$is_enabled = false;
		if ( null !== $logger_enabled_row && isset( $logger_enabled_row['data'] ) && is_string( $logger_enabled_row['data'] ) ) {
			$data       = json_decode( $logger_enabled_row['data'], true );
			$is_enabled = is_array( $data ) && ! empty( $data['value'] );

			$this->db->delete(
				$this->entity_table,
				array(
					'type'    => 'Configuration',
					'index_1' => 'defaultLoggerEnabled',
				)
			);
		}

		$level = 3;
		if ( null !== $log_level_row && isset( $log_level_row['data'] ) && is_string( $log_level_row['data'] ) ) {
			$data  = json_decode( $log_level_row['data'], true );
			$level = is_array( $data ) && isset( $data['value'] ) ? (int) $data['value'] : 3;

			$this->db->delete(
				$this->entity_table,
				array(
					'type'    => 'Configuration',
					'index_1' => 'minLogLevel',
				)
			);
		}

		$settings = new AdvancedSettings( $is_enabled, $level );
		$this->advanced_settings_service->setAdvancedSettings( $settings );
	}

	/**
	 * Migrate the shipped status from the removed sequra_shop_status_completed filter
	 * to the order status settings configuration.
	 *
	 * The filter returned an array but the mapping expects a string, so we keep
	 * only the first value from the array.
	 */
	public function migrate_shipped_status_from_filter(): void {
		$default = array( 'wc-completed' );

		$filtered = (array) \apply_filters_deprecated( 'woocommerce_sequracheckout_sent_statuses', array( $default ), '3.0.0', 'sequra_shop_status_completed' );

		/**
		 * Apply the deprecated filter to retrieve any custom value set by merchants.
		 *
		 * @since 3.0.0
		 * @deprecated 4.2.0 Use the order status mapping configuration instead.
		 */
		$filtered = \apply_filters( 'sequra_shop_status_completed', $filtered );
		$filtered = \is_array( $filtered ) ? $filtered : $default;

		if ( $filtered === $default ) {
			return;
		}

		$custom_status = reset( $filtered );
		if ( ! \is_string( $custom_status ) || '' === trim( $custom_status ) ) {
			return;
		}

		$mappings = $this->order_status_settings_service->getOrderStatusSettings();
		$updated  = array();
		$found    = false;
		foreach ( $mappings as $mapping ) {
			if ( $mapping->getSequraStatus() === OrderStates::STATE_SHIPPED ) {
				$updated[] = new OrderStatusMapping( OrderStates::STATE_SHIPPED, $custom_status );
				$found     = true;
			} else {
				$updated[] = $mapping;
			}
		}

		if ( ! $found ) {
			$updated[] = new OrderStatusMapping( OrderStates::STATE_SHIPPED, $custom_status );
		}

		$this->order_status_settings_service->saveOrderStatusSettings( $updated );
	}

	/**
	 * Execute the migration logic.
	 *
	 * @throws Throwable
	 */
	protected function execute(): void {
		$this->migrate_advanced_settings();
		$this->migrate_shipped_status_from_filter();
	}
}
