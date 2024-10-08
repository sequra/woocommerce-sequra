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
class Configure_Dummy_Service_Task extends Task {

	/**
	 * Check if dummy_services merchant configuration is in use
	 */
	private function is_dummy_service_config_in_use(): bool {
		global $wpdb;
		$table_name = $this->get_sequra_entity_table_name();
		$query      = "SELECT * FROM $table_name WHERE type = 'ConnectionData' AND `data` LIKE '%\"username\":\"dummy_services\"%'";
		$result     = $wpdb->get_results( $query );
		return is_array( $result ) && ! empty( $result );
	}

	/**
	 * Set configuration for dummy_services merchant
	 */
	private function set_dummy_services_config(): void {
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
				'data'    => '{"class_name":"SeQura\\\\Core\\\\BusinessLogic\\\\DataAccess\\\\ConnectionData\\\\Entities\\\\ConnectionData","id":null,"storeId":"1","connectionData":{"environment":"sandbox","merchantId":null,"authorizationCredentials":{"username":"dummy_services","password":"nkT\/LVmRilA\/0ZSPv6hlfNE80glXw6mp0BwYBQ4KNlip9xUHfxsgrZwuvWz8PuCHQYDKtNcRYb+u3UUExhcm2VgiEte5Lw=="}}}',
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
				'data'    => '{"class_name":"SeQura\\\\Core\\\\BusinessLogic\\\\DataAccess\\\\CountryConfiguration\\\\Entities\\\\CountryConfiguration","id":null,"storeId":"1","countryConfigurations":[{"countryCode":"ES","merchantId":"dummy_services"}]}',
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
				'data'    => '{"class_name":"SeQura\\\\WC\\\\Core\\\\Extension\\\\BusinessLogic\\\\DataAccess\\\\PromotionalWidgets\\\\Entities\\\\Widget_Settings","id":null,"storeId":"1","widgetSettings":{"enabled":false,"assetsKey":"","displayOnProductPage":false,"showInstallmentsInProductListing":false,"showInstallmentsInCartPage":false,"miniWidgetSelector":"","widgetConfiguration":"{\"alignment\":\"center\",\"amount-font-bold\":\"true\",\"amount-font-color\":\"#1C1C1C\",\"amount-font-size\":\"15\",\"background-color\":\"white\",\"border-color\":\"#B1AEBA\",\"border-radius\":\"\",\"class\":\"\",\"font-color\":\"#1C1C1C\",\"link-font-color\":\"#1C1C1C\",\"link-underline\":\"true\",\"no-costs-claim\":\"\",\"size\":\"M\",\"starting-text\":\"only\",\"type\":\"banner\"}","widgetLabels":{"messages":[],"messagesBelowLimit":[]},"widgetLocationConfiguration":{"selForPrice":".summary .price>.amount,.summary .price ins .amount","selForAltPrice":".woocommerce-variation-price .price>.amount,.woocommerce-variation-price .price ins .amount","selForAltPriceTrigger":".variations","selForDefaultLocation":".summary>.price","customLocations":[]}}}',
			)
		);
		$wpdb->insert(
			$table_name,
			array(
				'id'      => 5,
				'type'    => 'GeneralSettings',
				'index_1' => '1',
				'index_2' => null,
				'index_3' => null,
				'index_4' => null,
				'index_5' => null,
				'index_6' => null,
				'index_7' => null,
				'data'    => '{"class_name":"SeQura\\\\WC\\\\Core\\\\Extension\\\\BusinessLogic\\\\DataAccess\\\\GeneralSettings\\\\Entities\\\\General_Settings","id":null,"storeId":"1","generalSettings":{"sendOrderReportsPeriodicallyToSeQura":false,"showSeQuraCheckoutAsHostedPage":false,"allowedIPAddresses":[],"excludedProducts":[],"excludedCategories":[],"enabledForServices":true,"allowFirstServicePaymentDelay":false,"allowServiceRegItems":true,"defaultServicesEndDate":"P1Y"}}',
			)
		);
	}

	/**
	 * Execute the task
	 * 
	 * @throws \Exception If the task fails
	 */
	public function execute( array $args = array() ): void {
		if ( ! $this->is_dummy_service_config_in_use() ) {
			$this->recreate_entity_table_in_database();
			$this->set_dummy_services_config();
		}
	}
}
