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
				if ( ! $this->is_dummy_service_config_in_use() ) {
					$this->recreate_tables_in_database();
					$this->set_dummy_services_config();
				}
				wp_send_json_success( array( 'message' => 'Merchant "dummy_services" configuration applied' ) );
				break;
			case 'dummy_config':
				if ( ! $this->is_dummy_config_in_use() ) {
					$this->recreate_tables_in_database();
					$this->set_dummy_config();
				}
				wp_send_json_success( array( 'message' => 'Merchant "dummy" configuration applied' ) );
				break;
			case 'force_order_failure':
				if ( isset( $_GET['order_id'] ) ) {
					$order_id = absint( $_GET['order_id'] );
					if ( $this->force_order_failure( $order_id ) ) {
						wp_send_json_success( array( 'message' => 'Updated order ' . $order_id . ' payload to force failure' ) );
					} else {
						wp_send_json_error( array( 'message' => 'Failed to update order ' . $order_id . ' payload' ), 500 );
					}
				} else {
					wp_send_json_error( array( 'message' => 'Invalid order ID' ), 400 );
				}
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
	 * Get the table name for the seQura order table
	 */
	private function get_sequra_order_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'sequra_order';
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
	 * Check if dummy merchant configuration is in use
	 */
	private function is_dummy_config_in_use(): bool {
		global $wpdb;
		$table_name = $this->get_sequra_entity_table_name();
		$query      = "SELECT * FROM $table_name WHERE type = 'ConnectionData' AND `data` LIKE '%\"username\":\"dummy\"%'";
		$result     = $wpdb->get_results( $query );
		return is_array( $result ) && ! empty( $result );
	}

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

	/**
	 * Update the order payload to force failure
	 */
	private function force_order_failure( int $order_id ): bool {
		global $wpdb;
		$table_name = $this->get_sequra_order_table_name();
		$row        = $wpdb->get_row( "SELECT * FROM $table_name WHERE `type` = 'SeQuraOrder' AND `index_3` = '$order_id'" );

		if ( ! $row ) {
			return false;
		}

		$data = json_decode( $row->data, true );
		if ( ! $data ) {
			return false;
		}

		// Sum 5000 cents to the totals to exceed the approved amount.
		$plus                 = 5000;
		$order_total_with_tax = 0;
		if ( isset( $data['unshipped_cart']['items'] ) ) {
			foreach ( $data['unshipped_cart']['items'] as $key => &$item ) {
				$item['total_with_tax'] += $plus;
				$order_total_with_tax   += $plus;
			}
		}
		if ( isset( $data['unshipped_cart']['order_total_with_tax'] ) ) {
			$data['unshipped_cart']['order_total_with_tax'] += $order_total_with_tax;
		}

		$wpdb->update(
			$table_name,
			array(
				'data' => wp_json_encode( $data ),
			),
			array( 'id' => $row->id )
		);

		return true;

		// {"class_name":"SeQura\\Core\\BusinessLogic\\Domain\\Order\\Models\\SeQuraOrder","id":null,"reference":"8d2f14ca-7a60-4951-863b-309bbd0e86d9","cartId":"66991f94cd371","orderRef1":"","merchant":{"id":"dummy"},"merchantReference":null,"shippedCart":{},"unshippedCart":{},"state":"","trackings":null,"deliveryMethod":{},"deliveryAddress":{},"invoiceAddress":{},"customer":{"given_names":"Review Test Cancel","surnames":"Review Test Cancel","email":"test@sequra.es","logged_in":false,"language_code":"en","ip_number":"79.116.3.205","user_agent":"Mozilla\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/127.0.6533.17 Safari\/537.36","company":"","vat_number":"","previous_orders":[{"created_at":"2024-07-18T13:43:47+00:00","amount":5000,"currency":"EUR","raw_status":"processing","status":"Processing","payment_method_raw":"sequra","payment_method":"Flexible payment with seQura","postal_code":"08010","country_code":"ES"},{"created_at":"2024-07-18T13:42:18+00:00","amount":10000,"currency":"EUR","raw_status":"processing","status":"Processing","payment_method_raw":"sequra","payment_method":"Flexible payment with seQura","postal_code":"08010","country_code":"ES"},{"created_at":"2024-07-18T13:40:57+00:00","amount":10000,"currency":"EUR","raw_status":"processing","status":"Processing","payment_method_raw":"sequra","payment_method":"Flexible payment with seQura","postal_code":"08010","country_code":"ES"},{"created_at":"2024-07-18T13:33:56+00:00","amount":10000,"currency":"EUR","raw_status":"processing","status":"Processing","payment_method_raw":"sequra","payment_method":"Flexible payment with seQura","postal_code":"08010","country_code":"ES"}]},"platform":{"name":"WooCommerce","version":"9.1.2","plugin_version":"3.0.0","uname":"Linux 4385436d2249 6.6.16-linuxkit #1 SMP Fri Feb 16 11:54:02 UTC 2024 aarch64","db_name":"mariadb","db_version":"11.3.2","php_version":"8.2.21"},"gui":{"layout":"desktop"},"paymentMethod":null,"cart_id":"66991f94cd371","order_ref_1":"","merchant_reference":[],"shipped_cart":{"currency":"EUR","gift":false,"order_total_with_tax":0,"items":[]},"unshipped_cart":{"currency":"EUR","gift":false,"order_total_with_tax":10000,"cart_ref":"66991f94cd371","created_at":"2024-07-18T13:58:44+00:00","updated_at":"2024-07-18T13:58:44+00:00","items":[{"type":"product","total_with_tax":9000,"reference":"woo-sunglasses","name":"Sunglasses","price_with_tax":9000,"quantity":1,"downloadable":false,"category":"<a href=\"https:\/\/sq.wp.michel.ngrok.dev\/?product_cat=accessories\" rel=\"tag\">Accessories<\/a>","description":"Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.","product_id":13,"url":"https:\/\/sq.wp.michel.ngrok.dev\/?product=sunglasses"},{"type":"handling","total_with_tax":1000,"reference":"handling","name":"Shipping cost"}]},"delivery_method":{"name":"Flat rate","provider":"flat_rate:1"},"delivery_address":{"given_names":"Review Test Cancel","surnames":"Review Test Cancel","company":"","address_line_1":"Carrer d'Al\u00ed Bei, 7","address_line_2":"","postal_code":"08010","city":"Barcelona","country_code":"ES","mobile_phone":"666666666","state":"Barcelona","vat_number":""},"invoice_address":{"given_names":"Review Test Cancel","surnames":"Review Test Cancel","company":"","address_line_1":"Carrer d'Al\u00ed Bei, 7","address_line_2":"","postal_code":"08010","city":"Barcelona","country_code":"ES","mobile_phone":"666666666","state":"Barcelona","vat_number":""},"payment_method":[]}

		// {"class_name":"SeQura\\Core\\BusinessLogic\\Domain\\Order\\Models\\SeQuraOrder","id":"251","reference":"0eebcb1e-514b-48ef-bac4-890d31923607","cartId":"6699200472c63","orderRef1":"113","merchant":{"id":"dummy_services","notify_url":"https:\/\/sq.wp.michel.ngrok.dev\/?order=113&wc-api=woocommerce_sequra_ipn&store_id=1","notification_parameters":{"order":"113","signature":"298fed21a29bf336ef911e4029f5b54809a9eb542850bca94bd17250dc512023","result":"0","storeId":"1"},"return_url":"https:\/\/sq.wp.michel.ngrok.dev\/?order=113&sq_product=SQ_PRODUCT_CODE&wc-api=woocommerce_sequra_return","events_webhook":{"url":"https:\/\/sq.wp.michel.ngrok.dev\/?order=113&wc-api=woocommerce_sequra&store_id=1","parameters":{"order":"113","signature":"298fed21a29bf336ef911e4029f5b54809a9eb542850bca94bd17250dc512023","storeId":"1"}}},"merchantReference":{},"shippedCart":{},"unshippedCart":{},"state":"confirmed","trackings":null,"deliveryMethod":{},"deliveryAddress":{},"invoiceAddress":{},"customer":{"given_names":"Fulano","surnames":"De Tal","email":"test@sequra.es","logged_in":false,"language_code":"en","ip_number":"79.116.3.205","user_agent":"Mozilla\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/127.0.6533.17 Safari\/537.36","company":"","vat_number":"","previous_orders":[{"created_at":"2024-07-18T13:59:10+00:00","amount":10000,"currency":"EUR","raw_status":"processing","status":"Processing","payment_method_raw":"sequra","payment_method":"Flexible payment with seQura","postal_code":"08010","country_code":"ES"},{"created_at":"2024-07-18T13:43:47+00:00","amount":5000,"currency":"EUR","raw_status":"processing","status":"Processing","payment_method_raw":"sequra","payment_method":"Flexible payment with seQura","postal_code":"08010","country_code":"ES"},{"created_at":"2024-07-18T13:42:18+00:00","amount":10000,"currency":"EUR","raw_status":"processing","status":"Processing","payment_method_raw":"sequra","payment_method":"Flexible payment with seQura","postal_code":"08010","country_code":"ES"},{"created_at":"2024-07-18T13:40:57+00:00","amount":10000,"currency":"EUR","raw_status":"processing","status":"Processing","payment_method_raw":"sequra","payment_method":"Flexible payment with seQura","postal_code":"08010","country_code":"ES"},{"created_at":"2024-07-18T13:33:56+00:00","amount":10000,"currency":"EUR","raw_status":"processing","status":"Processing","payment_method_raw":"sequra","payment_method":"Flexible payment with seQura","postal_code":"08010","country_code":"ES"}]},"platform":{"name":"WooCommerce","version":"9.1.2","plugin_version":"3.0.0","uname":"Linux 4385436d2249 6.6.16-linuxkit #1 SMP Fri Feb 16 11:54:02 UTC 2024 aarch64","db_name":"mariadb","db_version":"11.3.2","php_version":"8.2.21"},"gui":{"layout":"desktop"},"paymentMethod":null,"cart_id":"6699200472c63","order_ref_1":"113","merchant_reference":{"order_ref_1":113,"order_ref_2":""},"shipped_cart":{"currency":"EUR","gift":false,"order_total_with_tax":0,"items":[]},"unshipped_cart":{"currency":"EUR","gift":false,"order_total_with_tax":5000,"cart_ref":"6699200472c63","created_at":"2024-07-18T14:00:36+00:00","updated_at":"2024-07-18T14:00:42+00:00","items":[{"type":"registration","total_with_tax":1590,"reference":"woo-album-reg","name":"Reg. Album"},{"type":"service","total_with_tax":3410,"reference":"woo-album","name":"Album","ends_in":"P1Y","price_with_tax":5000,"quantity":1,"downloadable":false,"supplier":"","rendered":false}]},"delivery_method":{"name":"default","provider":"default"},"delivery_address":{"given_names":"Fulano","surnames":"De Tal","company":"","address_line_1":"Carrer d'Al\u00ed Bei, 7","address_line_2":"","postal_code":"08010","city":"Barcelona","country_code":"ES","mobile_phone":"666666666","state":"Barcelona","extra":"","vat_number":""},"invoice_address":{"given_names":"Fulano","surnames":"De Tal","company":"","address_line_1":"Carrer d'Al\u00ed Bei, 7","address_line_2":"","postal_code":"08010","city":"Barcelona","country_code":"ES","mobile_phone":"666666666","state":"Barcelona","extra":"","vat_number":""},"payment_method":[]}
		// {"class_name":"SeQura\\Core\\BusinessLogic\\Domain\\Order\\Models\\SeQuraOrder","id":"251","reference":"0eebcb1e-514b-48ef-bac4-890d31923607","cartId":"6699200472c63","orderRef1":"113","merchant":{"id":"dummy_services","notify_url":"https:\/\/sq.wp.michel.ngrok.dev\/?order=113&wc-api=woocommerce_sequra_ipn&store_id=1","notification_parameters":{"order":"113","signature":"298fed21a29bf336ef911e4029f5b54809a9eb542850bca94bd17250dc512023","result":"0","storeId":"1"},"return_url":"https:\/\/sq.wp.michel.ngrok.dev\/?order=113&sq_product=SQ_PRODUCT_CODE&wc-api=woocommerce_sequra_return","events_webhook":{"url":"https:\/\/sq.wp.michel.ngrok.dev\/?order=113&wc-api=woocommerce_sequra&store_id=1","parameters":{"order":"113","signature":"298fed21a29bf336ef911e4029f5b54809a9eb542850bca94bd17250dc512023","storeId":"1"}}},"merchantReference":{},"shippedCart":{},"unshippedCart":{},"state":"confirmed","trackings":null,"deliveryMethod":{},"deliveryAddress":{},"invoiceAddress":{},"customer":{"given_names":"Fulano","surnames":"De Tal","email":"test@sequra.es","logged_in":false,"language_code":"en","ip_number":"79.116.3.205","user_agent":"Mozilla\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/127.0.6533.17 Safari\/537.36","company":"","vat_number":"","previous_orders":[{"created_at":"2024-07-18T13:59:10+00:00","amount":10000,"currency":"EUR","raw_status":"processing","status":"Processing","payment_method_raw":"sequra","payment_method":"Flexible payment with seQura","postal_code":"08010","country_code":"ES"},{"created_at":"2024-07-18T13:43:47+00:00","amount":5000,"currency":"EUR","raw_status":"processing","status":"Processing","payment_method_raw":"sequra","payment_method":"Flexible payment with seQura","postal_code":"08010","country_code":"ES"},{"created_at":"2024-07-18T13:42:18+00:00","amount":10000,"currency":"EUR","raw_status":"processing","status":"Processing","payment_method_raw":"sequra","payment_method":"Flexible payment with seQura","postal_code":"08010","country_code":"ES"},{"created_at":"2024-07-18T13:40:57+00:00","amount":10000,"currency":"EUR","raw_status":"processing","status":"Processing","payment_method_raw":"sequra","payment_method":"Flexible payment with seQura","postal_code":"08010","country_code":"ES"},{"created_at":"2024-07-18T13:33:56+00:00","amount":10000,"currency":"EUR","raw_status":"processing","status":"Processing","payment_method_raw":"sequra","payment_method":"Flexible payment with seQura","postal_code":"08010","country_code":"ES"}]},"platform":{"name":"WooCommerce","version":"9.1.2","plugin_version":"3.0.0","uname":"Linux 4385436d2249 6.6.16-linuxkit #1 SMP Fri Feb 16 11:54:02 UTC 2024 aarch64","db_name":"mariadb","db_version":"11.3.2","php_version":"8.2.21"},"gui":{"layout":"desktop"},"paymentMethod":null,"cart_id":"6699200472c63","order_ref_1":"113","merchant_reference":{"order_ref_1":113,"order_ref_2":""},"shipped_cart":{"currency":"EUR","gift":false,"order_total_with_tax":0,"items":[]},"unshipped_cart":{"currency":"EUR","gift":false,"order_total_with_tax":5000,"cart_ref":"6699200472c63","created_at":"2024-07-18T14:00:36+00:00","updated_at":"2024-07-18T14:00:42+00:00","items":[{"type":"registration","total_with_tax":1590,"reference":"woo-album-reg","name":"Reg. Album"},{"type":"service","total_with_tax":3410,"reference":"woo-album","name":"Album","ends_in":"P1Y","price_with_tax":5000,"quantity":1,"downloadable":false,"supplier":"","rendered":false}]},"delivery_method":{"name":"default","provider":"default"},"delivery_address":{"given_names":"Fulano","surnames":"De Tal","company":"","address_line_1":"Carrer d'Al\u00ed Bei, 7","address_line_2":"","postal_code":"08010","city":"Barcelona","country_code":"ES","mobile_phone":"666666666","state":"Barcelona","extra":"","vat_number":""},"invoice_address":{"given_names":"Fulano","surnames":"De Tal","company":"","address_line_1":"Carrer d'Al\u00ed Bei, 7","address_line_2":"","postal_code":"08010","city":"Barcelona","country_code":"ES","mobile_phone":"666666666","state":"Barcelona","extra":"","vat_number":""},"payment_method":[]}
		// {"class_name":"SeQura\\Core\\BusinessLogic\\Domain\\Order\\Models\\SeQuraOrder","id":"251","reference":"0eebcb1e-514b-48ef-bac4-890d31923607","cartId":"6699200472c63","orderRef1":"113","merchant":{"id":"dummy_services","notify_url":"https:\/\/sq.wp.michel.ngrok.dev\/?order=113&wc-api=woocommerce_sequra_ipn&store_id=1","notification_parameters":{"order":"113","signature":"298fed21a29bf336ef911e4029f5b54809a9eb542850bca94bd17250dc512023","result":"0","storeId":"1"},"return_url":"https:\/\/sq.wp.michel.ngrok.dev\/?order=113&sq_product=SQ_PRODUCT_CODE&wc-api=woocommerce_sequra_return","events_webhook":{"url":"https:\/\/sq.wp.michel.ngrok.dev\/?order=113&wc-api=woocommerce_sequra&store_id=1","parameters":{"order":"113","signature":"298fed21a29bf336ef911e4029f5b54809a9eb542850bca94bd17250dc512023","storeId":"1"}}},"merchantReference":[],"shippedCart":[],"unshippedCart":[],"state":"confirmed","trackings":null,"deliveryMethod":[],"deliveryAddress":[],"invoiceAddress":[],"customer":{"given_names":"Fulano","surnames":"De Tal","email":"test@sequra.es","logged_in":false,"language_code":"en","ip_number":"79.116.3.205","user_agent":"Mozilla\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/127.0.6533.17 Safari\/537.36","company":"","vat_number":"","previous_orders":[{"created_at":"2024-07-18T13:59:10+00:00","amount":10000,"currency":"EUR","raw_status":"processing","status":"Processing","payment_method_raw":"sequra","payment_method":"Flexible payment with seQura","postal_code":"08010","country_code":"ES"},{"created_at":"2024-07-18T13:43:47+00:00","amount":5000,"currency":"EUR","raw_status":"processing","status":"Processing","payment_method_raw":"sequra","payment_method":"Flexible payment with seQura","postal_code":"08010","country_code":"ES"},{"created_at":"2024-07-18T13:42:18+00:00","amount":10000,"currency":"EUR","raw_status":"processing","status":"Processing","payment_method_raw":"sequra","payment_method":"Flexible payment with seQura","postal_code":"08010","country_code":"ES"},{"created_at":"2024-07-18T13:40:57+00:00","amount":10000,"currency":"EUR","raw_status":"processing","status":"Processing","payment_method_raw":"sequra","payment_method":"Flexible payment with seQura","postal_code":"08010","country_code":"ES"},{"created_at":"2024-07-18T13:33:56+00:00","amount":10000,"currency":"EUR","raw_status":"processing","status":"Processing","payment_method_raw":"sequra","payment_method":"Flexible payment with seQura","postal_code":"08010","country_code":"ES"}]},"platform":{"name":"WooCommerce","version":"9.1.2","plugin_version":"3.0.0","uname":"Linux 4385436d2249 6.6.16-linuxkit #1 SMP Fri Feb 16 11:54:02 UTC 2024 aarch64","db_name":"mariadb","db_version":"11.3.2","php_version":"8.2.21"},"gui":{"layout":"desktop"},"paymentMethod":null,"cart_id":"6699200472c63","order_ref_1":"113","merchant_reference":{"order_ref_1":113,"order_ref_2":""},"shipped_cart":{"currency":"EUR","gift":false,"order_total_with_tax":0,"items":[]},"unshipped_cart":{"currency":"EUR","gift":false,"order_total_with_tax":25000,"cart_ref":"6699200472c63","created_at":"2024-07-18T14:00:36+00:00","updated_at":"2024-07-18T14:00:42+00:00","items":[{"type":"registration","total_with_tax":11590,"reference":"woo-album-reg","name":"Reg. Album"},{"type":"service","total_with_tax":13410,"reference":"woo-album","name":"Album","ends_in":"P1Y","price_with_tax":5000,"quantity":1,"downloadable":false,"supplier":"","rendered":false}]},"delivery_method":{"name":"default","provider":"default"},"delivery_address":{"given_names":"Fulano","surnames":"De Tal","company":"","address_line_1":"Carrer d'Al\u00ed Bei, 7","address_line_2":"","postal_code":"08010","city":"Barcelona","country_code":"ES","mobile_phone":"666666666","state":"Barcelona","extra":"","vat_number":""},"invoice_address":{"given_names":"Fulano","surnames":"De Tal","company":"","address_line_1":"Carrer d'Al\u00ed Bei, 7","address_line_2":"","postal_code":"08010","city":"Barcelona","country_code":"ES","mobile_phone":"666666666","state":"Barcelona","extra":"","vat_number":""},"payment_method":[]}
	}
}

new SeQura_Helper_Plugin();
