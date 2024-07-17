<?php
/**
 * SeQura Helper Plugin
 * 
 * @package SeQura_Helper
 */

// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.NonceVerification.Recommended

/**
 * SeQura Helper Plugin
 */
class SeQura_Helper_Plugin {
	
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'handle_webhook' ) );
	}

	/**
	 * Handle webhook
	 */
	public function handle_webhook(): void {
		if ( ! isset( $_GET['sq-webhook'] ) ) {
			return;
		}

		header( 'Content-Type: application/json' );

		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			wp_send_json_error( array( 'message' => 'Invalid request method' ), 405 );
		}

		switch ( sanitize_text_field( $_GET['sq-webhook'] ) ) {
			case 'dummy_services_config':
				$this->recreate_tables_in_database();
				$this->set_dummy_services_config();
				wp_send_json_success( array( 'message' => 'Merchant "dummy_services" configuration applied' ) );
				break;
			case 'dummy_config':
				$this->recreate_tables_in_database();
				$this->set_dummy_config();
				wp_send_json_success( array( 'message' => 'Merchant "dummy" configuration applied' ) );
				break;
			default:
				wp_send_json_error( array( 'message' => 'Invalid webhook' ), 400 );
		}
	}

	/**
	 * Get the table name for the seQura entity table
	 */
	private function get_sequra_entity_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'sequra_entity';
	}

	/**
	 * Recreate tables in the database
	 */
	private function recreate_tables_in_database(): void {
		global $wpdb;
		$table_name      = $this->get_sequra_entity_table_name();
		$charset_collate = $wpdb->collate;
		
		$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
		
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta(
			"CREATE TABLE IF NOT EXISTS $table_name (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `type` VARCHAR(255),
            `index_1` VARCHAR(127),
            `index_2` VARCHAR(127),
            `index_3` VARCHAR(127),
            `index_4` VARCHAR(127),
            `index_5` VARCHAR(127),
            `index_6` VARCHAR(127),
            `index_7` VARCHAR(127),
            `data` LONGTEXT,
            PRIMARY KEY  (id)
            ) $charset_collate" 
		);
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
				'data'    => '{"class_name":"SeQura\\\\Core\\\\BusinessLogic\\\\DataAccess\\\\StatisticalData\\\\Entities\\\\StatisticalData","id":null,"storeId":"1","statisticalData":{"sendStatisticalData":false}}',
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
				'data'    => '{"class_name":"SeQura\\\\WC\\\\Core\\\\Extension\\\\BusinessLogic\\\\DataAccess\\\\PromotionalWidgets\\\\Entities\\\\Widget_Settings","id":null,"storeId":"1","widgetSettings":{"enabled":false,"assetsKey":"","displayOnProductPage":false,"showInstallmentsInProductListing":false,"showInstallmentsInCartPage":false,"miniWidgetSelector":"","widgetConfiguration":"{\"alignment\":\"center\",\"amount-font-bold\":\"true\",\"amount-font-color\":\"#1C1C1C\",\"amount-font-size\":\"15\",\"background-color\":\"white\",\"border-color\":\"#B1AEBA\",\"border-radius\":\"\",\"class\":\"\",\"font-color\":\"#1C1C1C\",\"link-font-color\":\"#1C1C1C\",\"link-underline\":\"true\",\"no-costs-claim\":\"\",\"size\":\"M\",\"starting-text\":\"only\",\"type\":\"banner\"}","widgetLabels":{"messages":[],"messagesBelowLimit":[]},"widgetLocationConfiguration":{"sel_for_price":".summary .price>.amount,.summary .price ins .amount","sel_for_alt_price":".woocommerce-variation-price .price>.amount,.woocommerce-variation-price .price ins .amount,.woocommerce-variation-price .price .amount","sel_for_alt_price_trigger":".variations","sel_for_default_location":".summary .price","custom_locations":[]}}}',
			)
		);
	}
	/**
	 * Set configuration for dummy merchant
	 */
	private function set_dummy_config(): void {
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
				'data'    => '{"class_name":"SeQura\\\\Core\\\\BusinessLogic\\\\DataAccess\\\\ConnectionData\\\\Entities\\\\ConnectionData","id":null,"storeId":"1","connectionData":{"environment":"sandbox","merchantId":null,"authorizationCredentials":{"username":"dummy","password":"xEkiq2JYwXLEwyjYNHHpVWBPIrz5AUkiKxmShNvZVAOQpaI9P+MRKgX7V9rqS07RDAPUV\/KMs8Muza6PFMZ2H0tterUfNw=="}}}',
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
				'data'    => '{"class_name":"SeQura\\\\Core\\\\BusinessLogic\\\\DataAccess\\\\StatisticalData\\\\Entities\\\\StatisticalData","id":null,"storeId":"1","statisticalData":{"sendStatisticalData":false}}',
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
				'data'    => '{"class_name":"SeQura\\\\Core\\\\BusinessLogic\\\\DataAccess\\\\CountryConfiguration\\\\Entities\\\\CountryConfiguration","id":null,"storeId":"1","countryConfigurations":[{"countryCode":"ES","merchantId":"dummy"},{"countryCode":"FR","merchantId":"dummy_fr"},{"countryCode":"IT","merchantId":"dummy_it"},{"countryCode":"PT","merchantId":"dummy_pt"}]}',
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
				'data'    => '{"class_name":"SeQura\\\\WC\\\\Core\\\\Extension\\\\BusinessLogic\\\\DataAccess\\\\PromotionalWidgets\\\\Entities\\\\Widget_Settings","id":null,"storeId":"1","widgetSettings":{"enabled":true,"assetsKey":"ADc3ZdOLh4","displayOnProductPage":true,"showInstallmentsInProductListing":false,"showInstallmentsInCartPage":false,"miniWidgetSelector":"","widgetConfiguration":"{\"alignment\":\"center\",\"amount-font-bold\":\"true\",\"amount-font-color\":\"#1C1C1C\",\"amount-font-size\":\"15\",\"background-color\":\"white\",\"border-color\":\"#B1AEBA\",\"border-radius\":\"\",\"class\":\"\",\"font-color\":\"#1C1C1C\",\"link-font-color\":\"#1C1C1C\",\"link-underline\":\"true\",\"no-costs-claim\":\"\",\"size\":\"M\",\"starting-text\":\"only\",\"type\":\"banner\"}","widgetLabels":{"messages":[],"messagesBelowLimit":[]},"widgetLocationConfiguration":{"sel_for_price":".summary .price>.amount,.summary .price ins .amount","sel_for_alt_price":".woocommerce-variation-price .price>.amount,.woocommerce-variation-price .price ins .amount,.woocommerce-variation-price .price .amount","sel_for_alt_price_trigger":".variations","sel_for_default_location":".summary .price","custom_locations":[]}}}',
			)
		);
	}
}

new SeQura_Helper_Plugin();
