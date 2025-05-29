<?php
/**
 * Task class
 * 
 * @package SeQura/Helper
 */

namespace SeQura\Helper\Task;

// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/**
 * Task class
 */
class Configure_Dummy_Task extends Task {

	/**
	 * Check if dummy merchant configuration is in use
	 */
	private function is_dummy_config_in_use( bool $widgets ): bool {
		$expected_rows = $widgets ? 2 : 1;
		global $wpdb;
		$table_name = $this->get_sequra_entity_table_name();
		$query      = "SELECT * FROM $table_name WHERE (`type` = 'ConnectionData' AND `data` LIKE '%\"username\":\"dummy_automated_tests\"%') OR (`type` = 'WidgetSettings' AND `data` LIKE '%\"displayOnProductPage\":true%')";
		$result     = $wpdb->get_results( $query );
		return is_array( $result ) && count( $result ) === $expected_rows;
	}

	/**
	 * Set configuration for dummy merchant
	 */
	private function set_dummy_config( bool $widgets ): void {
		global $wpdb;
		$table_name = $this->get_sequra_entity_table_name();
		$wpdb->insert(
			$table_name,
			array(
				'id'      => 1,
				'type'    => 'ConnectionData',
				'index_1' => '1',
				'index_2' => null,
				'index_3' => null,
				'index_4' => null,
				'index_5' => null,
				'index_6' => null,
				'index_7' => null,
				'data'    => '{"class_name":"SeQura\\\\Core\\\\BusinessLogic\\\\DataAccess\\\\ConnectionData\\\\Entities\\\\ConnectionData","id":null,"storeId":"1","connectionData":{"environment":"sandbox","merchantId":null,"authorizationCredentials":{"username":"dummy_automated_tests","password":"PM72GTvQSlinHIhReicunfYv4+fjLIXz\/TiLUnXg7bMGZ1tFI2WIMkhtaTHpSZrD1bNrAaM9GLiAsltlsIiHqaqxmLbDjA=="}}}',
			)
		);
		$wpdb->insert(
			$table_name,
			array(
				'id'      => 2,
				'type'    => 'StatisticalData',
				'index_1' => '1',
				'index_2' => null,
				'index_3' => null,
				'index_4' => null,
				'index_5' => null,
				'index_6' => null,
				'index_7' => null,
				'data'    => '{"class_name":"SeQura\\\\Core\\\\BusinessLogic\\\\DataAccess\\\\StatisticalData\\\\Entities\\\\StatisticalData","id":null,"storeId":"1","statisticalData":{"sendStatisticalData":true}}',
			)
		);
		$wpdb->insert(
			$table_name,
			array(
				'id'      => 3,
				'type'    => 'CountryConfiguration',
				'index_1' => '1',
				'index_2' => null,
				'index_3' => null,
				'index_4' => null,
				'index_5' => null,
				'index_6' => null,
				'index_7' => null,
				'data'    => '{"class_name":"SeQura\\\\Core\\\\BusinessLogic\\\\DataAccess\\\\CountryConfiguration\\\\Entities\\\\CountryConfiguration","id":null,"storeId":"1","countryConfigurations":[{"countryCode":"ES","merchantId":"dummy_automated_tests"},{"countryCode":"IT","merchantId":"dummy_automated_tests_it"},{"countryCode":"PT","merchantId":"dummy_automated_tests_pt"}]}',
			)
		);

		$wpdb->insert(
			$table_name,
			array(
				'id'      => 4,
				'type'    => 'WidgetSettings',
				'index_1' => '1',
				'index_2' => null,
				'index_3' => null,
				'index_4' => null,
				'index_5' => null,
				'index_6' => null,
				'index_7' => null,
				'data'    => '{"class_name":"SeQura\\\\WC\\\\Core\\\\Extension\\\\BusinessLogic\\\\DataAccess\\\\PromotionalWidgets\\\\Entities\\\\Widget_Settings","id":null,"storeId":"1","widgetSettings":{"enabled":true,"assetsKey":"' . getenv( 'DUMMY_ASSETS_KEY' ) . '","displayOnProductPage":' . ( $widgets ? 'true' : 'false' ) . ',"showInstallmentsInProductListing":false,"showInstallmentsInCartPage":false,"miniWidgetSelector":"","widgetConfiguration":"{\"alignment\":\"center\",\"amount-font-bold\":\"true\",\"amount-font-color\":\"#1C1C1C\",\"amount-font-size\":\"15\",\"background-color\":\"white\",\"border-color\":\"#B1AEBA\",\"border-radius\":\"\",\"class\":\"\",\"font-color\":\"#1C1C1C\",\"link-font-color\":\"#1C1C1C\",\"link-underline\":\"true\",\"no-costs-claim\":\"\",\"size\":\"M\",\"starting-text\":\"only\",\"type\":\"banner\"}","widgetLabels":{"messages":[],"messagesBelowLimit":[]},"widgetLocationConfiguration":{"selForPrice":".summary .price>.amount,.summary .price ins .amount","selForAltPrice":".woocommerce-variation-price .price>.amount,.woocommerce-variation-price .price ins .amount","selForAltPriceTrigger":".variations","selForDefaultLocation":".summary>.price","customLocations":[]}}}',
			),
		);
	}

	/**
	 * Execute the task
	 * 
	 * @throws \Exception If the task fails
	 */
	public function execute( array $args = array() ): void {
		$widgets = isset( $args['widgets'] ) ? (bool) $args['widgets'] : true;
		if ( ! $this->is_dummy_config_in_use( $widgets ) ) {
			$this->recreate_entity_table_in_database();
			$this->set_dummy_config( $widgets );
		}
	}
}
