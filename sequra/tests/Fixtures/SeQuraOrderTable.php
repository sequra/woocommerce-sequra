<?php
/**
 * Helper to manage the content of sequra_order table in the database
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Tests\Fixtures;

/**
 * Helper to manage the content of sequra_order table in the database
 */
class SeQuraOrderTable {

	/**
	 * Database connection
	 *
	 * @var wpdb
	 */
	private $db;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->db = $wpdb;
	}

	/**
	 * Get the table name
	 * 
	 * @param bool $legacy If true, returns the legacy table name.
	 * @return string
	 */
	private function table_name( $legacy = false ) {
		$table_name = $this->db->prefix . 'sequra_order';
		if ( $legacy ) {
			$table_name .= '_legacy';
		}
		return $table_name;
	}

	/**
	 * Fill the table with sample data
	 */
	public function fill_with_sample_data() {
		$this->reset();
		$now       = gmdate( 'c' );
		$yesterday = gmdate( 'c', strtotime( 'yesterday' ) );
		$rows      = array(
			// Solicitation from current date.
			array(
				'id'      => 1,
				'type'    => 'SeQuraOrder',
				'index_1' => '4b096ff1-a660-4d40-9869-6050b69b2615',
				'index_2' => '67b3299f5b598',
				'index_3' => '',
				'index_4' => null,
				'index_5' => null,
				'index_6' => null,
				'index_7' => null,
				'data'    => '{"class_name":"SeQura\\\\Core\\\\BusinessLogic\\\\Domain\\\\Order\\\\Models\\\\SeQuraOrder","id":"1","reference":"4b096ff1-a660-4d40-9869-6050b69b2615","cartId":"67b3299f5b598","orderRef1":"","merchant":{"id":"dummy_automated_tests"},"merchantReference":{},"shippedCart":{},"unshippedCart":{},"state":"","trackings":null,"deliveryMethod":{},"deliveryAddress":{},"invoiceAddress":{},"customer":{"given_names":"Fulano","surnames":"De Tal","email":"admin@admin.com","logged_in":true,"language_code":"en","ip_number":"172.18.0.1","user_agent":"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/133.0.0.0 Safari\\/537.36","ref":1,"company":"","vat_number":"","previous_orders":[{"created_at":"2025-02-17T10:18:21+00:00","amount":8000,"currency":"EUR","raw_status":"processing","status":"Processing","payment_method_raw":"sequra","payment_method":"Flexible payment with seQura","postal_code":"15001","country_code":"ES"},{"created_at":"2025-02-17T09:20:41+00:00","amount":9500,"currency":"EUR","raw_status":"processing","status":"Processing","payment_method_raw":"sequra","payment_method":"Flexible payment with seQura","postal_code":"15001","country_code":"ES"},{"created_at":"2025-02-11T14:35:02+00:00","amount":9500,"currency":"EUR","raw_status":"processing","status":"Processing","payment_method_raw":"sequra","payment_method":"Flexible payment with seQura","postal_code":"15001","country_code":"ES"}]},"platform":{"name":"WooCommerce","version":"9.6.2 + WordPress 6.7.1","plugin_version":"3.1.1-rc.1","uname":"Linux 5c7d47826de3 6.10.14-linuxkit #1 SMP Fri Nov 29 17:22:03 UTC 2024 aarch64","db_name":"mariadb","db_version":"11.6.2","php_version":"8.2.27"},"gui":{"layout":"desktop"},"paymentMethod":null,"cart_id":"67b3299f5b598","order_ref_1":"","merchant_reference":{"order_ref_1":"","order_ref_2":""},"shipped_cart":{"currency":"EUR","gift":false,"order_total_with_tax":0,"items":[]},"unshipped_cart":{"currency":"EUR","gift":false,"order_total_with_tax":10000,"cart_ref":"67b3299f5b598","created_at":"' . $now . '","updated_at":"' . $now . '","items":[{"type":"product","total_with_tax":9000,"reference":"woo-sunglasses","name":"Sunglasses","price_with_tax":9000,"quantity":1,"downloadable":false,"category":"<a href=\\"http:\\/\\/localhost.sequrapi.com:8000\\/product-category\\/accessories\\/\\" rel=\\"tag\\">Accessories<\\/a>","description":"Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.","product_id":13,"url":"http:\\/\\/localhost.sequrapi.com:8000\\/product\\/sunglasses\\/"},{"type":"handling","total_with_tax":1000,"reference":"handling","name":"Shipping cost"}]},"delivery_method":{"name":"default","provider":"default"},"delivery_address":{"given_names":"Fulano","surnames":"De Tal","company":"","address_line_1":"calle 2","address_line_2":"","postal_code":"15001","city":"coru","country_code":"ES","mobile_phone":"666666666","state":"A Coru\\u00f1a","vat_number":""},"invoice_address":{"given_names":"Fulano","surnames":"De Tal","company":"","address_line_1":"calle 2","address_line_2":"","postal_code":"15001","city":"coru","country_code":"ES","mobile_phone":"666666666","state":"A Coru\\u00f1a","vat_number":""},"payment_method":[]}',
			),
			// Solicitation from one day ago.
			array(
				'id'      => 2,
				'type'    => 'SeQuraOrder',
				'index_1' => 'dcc86374-2f5a-46ba-b80e-487ded32fa4e',
				'index_2' => '67b3299f5e4a4',
				'index_3' => '',
				'index_4' => null,
				'index_5' => null,
				'index_6' => null,
				'index_7' => null,
				'data'    => '{"class_name":"SeQura\\\\Core\\\\BusinessLogic\\\\Domain\\\\Order\\\\Models\\\\SeQuraOrder","id":2,"reference":"dcc86374-2f5a-46ba-b80e-487ded32fa4e","cartId":"67b3299f5e4a4","orderRef1":"","merchant":{"id":"dummy_automated_tests"},"merchantReference":null,"shippedCart":{},"unshippedCart":{},"state":"","trackings":null,"deliveryMethod":{},"deliveryAddress":{},"invoiceAddress":{},"customer":{"given_names":"Fulano","surnames":"De Tal","email":"admin@admin.com","logged_in":true,"language_code":"en","ip_number":"172.18.0.1","user_agent":"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/133.0.0.0 Safari\\/537.36","ref":1,"company":"","vat_number":"","previous_orders":[{"created_at":"2025-02-17T10:18:21+00:00","amount":8000,"currency":"EUR","raw_status":"processing","status":"Processing","payment_method_raw":"sequra","payment_method":"Flexible payment with seQura","postal_code":"15001","country_code":"ES"},{"created_at":"2025-02-17T09:20:41+00:00","amount":9500,"currency":"EUR","raw_status":"processing","status":"Processing","payment_method_raw":"sequra","payment_method":"Flexible payment with seQura","postal_code":"15001","country_code":"ES"},{"created_at":"2025-02-11T14:35:02+00:00","amount":9500,"currency":"EUR","raw_status":"processing","status":"Processing","payment_method_raw":"sequra","payment_method":"Flexible payment with seQura","postal_code":"15001","country_code":"ES"}]},"platform":{"name":"WooCommerce","version":"9.6.2 + WordPress 6.7.1","plugin_version":"3.1.1-rc.1","uname":"Linux 5c7d47826de3 6.10.14-linuxkit #1 SMP Fri Nov 29 17:22:03 UTC 2024 aarch64","db_name":"mariadb","db_version":"11.6.2","php_version":"8.2.27"},"gui":{"layout":"desktop"},"paymentMethod":null,"cart_id":"67b3299f5e4a4","order_ref_1":"","merchant_reference":[],"shipped_cart":{"currency":"EUR","gift":false,"order_total_with_tax":0,"items":[]},"unshipped_cart":{"currency":"EUR","gift":false,"order_total_with_tax":10000,"cart_ref":"67b3299f5e4a4","created_at":"' . $yesterday . '","updated_at":"' . $yesterday . '","items":[{"type":"product","total_with_tax":9000,"reference":"woo-sunglasses","name":"Sunglasses","price_with_tax":9000,"quantity":1,"downloadable":false,"category":"<a href=\\"http:\\/\\/localhost.sequrapi.com:8000\\/product-category\\/accessories\\/\\" rel=\\"tag\\">Accessories<\\/a>","description":"Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.","product_id":13,"url":"http:\\/\\/localhost.sequrapi.com:8000\\/product\\/sunglasses\\/"},{"type":"handling","total_with_tax":1000,"reference":"handling","name":"Shipping cost"}]},"delivery_method":{"name":"default","provider":"default"},"delivery_address":{"given_names":"Fulano","surnames":"De Tal","company":"","address_line_1":"calle 2","address_line_2":"","postal_code":"15001","city":"coru","country_code":"ES","mobile_phone":"666666666","state":"A Coru\\u00f1a","vat_number":""},"invoice_address":{"given_names":"Fulano","surnames":"De Tal","company":"","address_line_1":"calle 2","address_line_2":"","postal_code":"15001","city":"coru","country_code":"ES","mobile_phone":"666666666","state":"A Coru\\u00f1a","vat_number":""},"payment_method":[]}',
			),
			// Order with orderRef1.
			array(
				'id'      => 3,
				'type'    => 'SeQuraOrder',
				'index_1' => '48686210-bf68-485f-bf13-43d15c9c727b',
				'index_2' => '67b3006a4bf7c',
				'index_3' => '1739284359',
				'index_4' => null,
				'index_5' => null,
				'index_6' => null,
				'index_7' => null,
				'data'    => '{"class_name":"SeQura\\\\Core\\\\BusinessLogic\\\\Domain\\\\Order\\\\Models\\\\SeQuraOrder","id":3,"reference":"48686210-bf68-485f-bf13-43d15c9c727b","cartId":"67b3006a4bf7c","orderRef1":"1739284359","merchant":{"id":"dummy_automated_tests","notify_url":"http:\\/\\/localhost.sequrapi.com:8000\\/?order=1739284359&wc-api=woocommerce_sequra_ipn&store_id=1","notification_parameters":{"order":"1739284359","signature":"7e8b9c327c933db6cbf0541d3b340d75974edd0bdb9bba3a77c7386cfce2c5e4","result":"0","storeId":"1"},"return_url":"http:\\/\\/localhost.sequrapi.com:8000\\/?order=1739284359&sq_product=SQ_PRODUCT_CODE&wc-api=woocommerce_sequra_return","events_webhook":{"url":"http:\\/\\/localhost.sequrapi.com:8000\\/?order=1739284359&wc-api=woocommerce_sequra&store_id=1","parameters":{"order":"1739284359","signature":"7e8b9c327c933db6cbf0541d3b340d75974edd0bdb9bba3a77c7386cfce2c5e4","storeId":"1"}}},"merchantReference":{},"shippedCart":{},"unshippedCart":{},"state":"","trackings":null,"deliveryMethod":{},"deliveryAddress":{},"invoiceAddress":{},"customer":{"given_names":"Fulano","surnames":"De Tal","email":"admin@admin.com","logged_in":true,"language_code":"en","ip_number":"172.18.0.1","user_agent":"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/133.0.0.0 Safari\\/537.36","ref":1,"company":"","vat_number":"","created_at":"2025-02-11T14:31:59+00:00","updated_at":"2025-02-11T14:31:59+00:00","previous_orders":[{"created_at":"2025-02-17T10:18:21+00:00","amount":8000,"currency":"EUR","raw_status":"processing","status":"Processing","payment_method_raw":"sequra","payment_method":"Flexible payment with seQura","postal_code":"15001","country_code":"ES"},{"created_at":"2025-02-17T09:20:41+00:00","amount":9500,"currency":"EUR","raw_status":"processing","status":"Processing","payment_method_raw":"sequra","payment_method":"Flexible payment with seQura","postal_code":"15001","country_code":"ES"},{"created_at":"2025-02-11T14:35:02+00:00","amount":9500,"currency":"EUR","raw_status":"processing","status":"Processing","payment_method_raw":"sequra","payment_method":"Flexible payment with seQura","postal_code":"15001","country_code":"ES"}]},"platform":{"name":"WooCommerce","version":"9.6.2 + WordPress 6.7.1","plugin_version":"3.1.1-rc.1","uname":"Linux 5c7d47826de3 6.10.14-linuxkit #1 SMP Fri Nov 29 17:22:03 UTC 2024 aarch64","db_name":"mariadb","db_version":"11.6.2","php_version":"8.2.27"},"gui":{"layout":"desktop"},"paymentMethod":null,"cart_id":"67b3006a4bf7c","order_ref_1":"1739284359","merchant_reference":{"order_ref_1":1739284359},"shipped_cart":{"currency":"EUR","gift":false,"order_total_with_tax":0,"items":[]},"unshipped_cart":{"currency":"EUR","gift":false,"order_total_with_tax":10000,"cart_ref":"67b3006a4bf7c","created_at":"2025-01-17T09:24:58+00:00","updated_at":"2025-01-17T12:21:19+00:00","items":[{"type":"product","total_with_tax":9000,"reference":"woo-sunglasses","name":"Sunglasses","price_with_tax":9000,"quantity":1,"downloadable":false,"category":"<a href=\\"http:\\/\\/localhost.sequrapi.com:8000\\/product-category\\/accessories\\/\\" rel=\\"tag\\">Accessories<\\/a>","description":"Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.","product_id":13,"url":"http:\\/\\/localhost.sequrapi.com:8000\\/product\\/sunglasses\\/"},{"type":"handling","total_with_tax":1000,"reference":"handling","name":"Shipping cost"}]},"delivery_method":{"name":"Flat rate","provider":"flat_rate"},"delivery_address":{"given_names":"Fulano","surnames":"De Tal","company":"","address_line_1":"calle 1","address_line_2":"","postal_code":"08010","city":"barcelona","country_code":"ES","mobile_phone":"666666666","state":"Barcelona","extra":"","vat_number":""},"invoice_address":{"given_names":"Fulano","surnames":"De Tal","company":"","address_line_1":"calle 1","address_line_2":"","postal_code":"08010","city":"barcelona","country_code":"ES","mobile_phone":"666666666","state":"Barcelona","extra":"","vat_number":""},"payment_method":[]}',
			),
			
		);
		
		foreach ( $rows as $row ) {
			$this->db->insert(
				$this->table_name(),
				$row,
				array(
					'%d',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
				) 
			);
		}
	}

	/**
	 * Reset the table to its initial state
	 */
	public function reset() {
		$this->db->query( 'TRUNCATE TABLE ' . $this->table_name() );
	}

	/**
	 * Get the IDs of the rows in the table
	 * 
	 * @return array
	 */
	public function get_ids() {
		return $this->db->get_col( "SELECT id FROM {$this->table_name()}" );
	}

	/**
	 * Get all rows from the table
	 * 
	 * @param bool $legacy If true, returns data from the legacy table.
	 * @return array
	 */
	public function get_all( $legacy ) {
		return $this->db->get_results( "SELECT * FROM {$this->table_name($legacy)}", ARRAY_A );
	}

	/**
	 * Remove the table from the database
	 * 
	 * @param bool $legacy If true, removes the legacy table.
	 */
	public function remove_table( $legacy = false ) {
		$table_name = $this->table_name( $legacy );
		$this->db->query( "DROP TABLE IF EXISTS {$table_name}" );
	}

	/**
	 * Get the next ID value for the table
	 * 
	 * @param bool $legacy If true, returns the next ID value for the legacy table.
	 * @return int
	 */
	public function get_next_id_value( $legacy = false ) {
		$table_name = $this->table_name( $legacy );
		return (int) $this->db->get_var( "SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = '{$this->db->dbname}' AND TABLE_NAME = '{$table_name}'" );
	}
}
