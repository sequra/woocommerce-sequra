<?php
/**
 * Proxy to remote configuration.
 *
 * @package woocommerce-sequra
 */

/**
 * SequraRemoteConfig class
 */
class SequraRemoteConfig {



	/**
	 * Seqtttings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Helper.
	 *
	 * @var SequraHelper
	 */
	private $helper;

	/**
	 * Payment methods available for merchant
	 *
	 * @var array
	 */
	private static $merchant_payment_methods = null;

	/**
	 * Payment methosds avilable for order
	 *
	 * @var array
	 */
	private static $order_payment_methods = null;

	/**
	 * Undocumented variable
	 *
	 * @var string
	 */
	private static $raw_merchant_payment_methods = null;

	/**
	 * Undocumented variable
	 *
	 * @var array
	 */
	private static $product_family_keys = array(
		'pp10' => 'CARD',       // Paga Ahora.
		'fp1'  => 'CARD',
		'i1'   => 'INVOICE',     // Paga después.
		'pp5'  => 'INVOICE',
		'pp3'  => 'PARTPAYMENT', // Paga fraccionado.
		'pp6'  => 'PARTPAYMENT',
		'pp9'  => 'PARTPAYMENT',
		'sp1'  => 'PARTPAYMENT',
	);

	/**
	 * Contructor
	 *
	 * @param array $settings Module settings.
	 */
	public function __construct( $settings ) {
		$this->settings = $settings;
		$this->helper   = SequraHelper::get_instance();
	}

	/**
	 * Undocumented function
	 *
	 * @param string $field the field to return.
	 * @return array
	 */
	public function get_merchant_active_payment_products( $field = 'product' ) {
		return array_map(
			function ( $method ) use ( $field ) {
				return $method[ $field ];
			},
			$this->get_merchant_payment_methods()
		);
	}

	/**
	 * Return a unique sting for the method i case there are multiple campaigns for the same products
	 *
	 * @param array $method the payment method.
	 * @return string
	 */
	public static function build_unique_product_code( $method ) {
		return $method['product'] . ( ( isset( $method['campaign'] ) && $method['campaign'] ) ? '_' . $method['campaign'] : '' );
	}

	/**
	 * Get method title from unique product code
	 *
	 * @param string $product_campaign unique product code.
	 * @return string
	 */
	public function get_title_from_unique_product_code( $product_campaign ) {
		list($product, $campaign) = explode( '_', $product_campaign );
		return $this->get_title_from_product_campaign( $product, $campaign );
	}
	/**
	 *  Get method title from unique product and campaign
	 *
	 * @param string $product product.
	 * @param string $campaign campaign.
	 * @return string
	 */
	public function get_title_from_product_campaign( $product, $campaign ) {
		foreach ( $this->get_merchant_payment_methods() as $method ) {
			if (
				$method['product'] === $product &&
				( ! $campaign || ! isset( $method['campaign'] ) || $method['campaign'] === $campaign )
			) {
				return $method['title'];
			}
		}
	}

	/**
	 * Undocumented function
	 *
	 * @param array $method the payment method.
	 * @return string
	 */
	public static function get_family_for( $method ) {
		return self::$product_family_keys[ $method['product'] ];
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function update_active_payment_methods() {
		$this->get_merchant_payment_methods( true );
		$sq_products = self::get_merchant_active_payment_products();
		update_option(
			'SEQURA_ACTIVE_METHODS',
			// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
			serialize( $sq_products )
			// phpcs:enable
		);
		if ( in_array( 'i1', $sq_products, true ) ) {
			update_option( 'SEQURA_INVOICE_PRODUCT', 'i1' );
		}
		if ( in_array( 'pp5', $sq_products, true ) ) {
			update_option( 'SEQURA_CAMPAIGN_PRODUCT', 'pp5' );
		}
		if ( in_array( 'pp3', $sq_products, true ) ) {
			update_option( 'SEQURA_PARTPAYMENT_PRODUCT', 'pp3' );
		} elseif ( in_array( 'pp6', $sq_products, true ) ) {
			update_option( 'SEQURA_PARTPAYMENT_PRODUCT', 'pp6' );
		} elseif ( in_array( 'pp9', $sq_products, true ) ) {
			update_option( 'SEQURA_PARTPAYMENT_PRODUCT', 'pp9' );
		}
	}

	/**
	 * Undocumented function
	 *
	 * @param boolean $force_refresh force reload config from sequra server.
	 * @return array
	 */
	public function get_merchant_payment_methods( $force_refresh = false ) {
		if ( ! $this->helper->is_valid_auth() ) {
			return array();
		}
		if ( $force_refresh || ! $this->get_stored_payment_methods() ) {
			$client = $this->helper->get_client();
			$client->getMerchantPaymentMethods( $this->helper->get_merchant_ref() );
			if ( $client->succeeded() ) {
				self::$raw_merchant_payment_methods = $client->getRawResult();
				$this->update_stored_payment_methods();
				$json                           = $client->getJson();
				self::$merchant_payment_methods = $json['payment_options'];
			}
		}
		if ( ! self::$merchant_payment_methods ) {
			$json                           = $this->get_stored_payment_methods();
			self::$merchant_payment_methods = $json['payment_options'];
		}
		return $this->flatten_payment_options(
			self::$merchant_payment_methods
		);
	}
	/**
	 * Undocumented function
	 *
	 * @return array
	 */
	public function get_available_payment_methods() {
		if ( ! self::$order_payment_methods ) {
			$client = $this->helper->get_client();
			if ( $this->helper->start_solicitation() ) {
				self::$order_payment_methods = $this->get_order_payment_methods( $client->getOrderUri() );
			}
		}

		return self::$order_payment_methods ?? array();
	}

	/**
	 * Undocumented function
	 *
	 * @param boolean $uri order uri in sequra.
	 * @return array
	 */
	public function get_order_payment_methods( $uri ) {
		$client = $this->helper->get_client();
		$client->getPaymentMethods( $uri );
		if ( $client->succeeded() ) {
			$json                     = $client->getJson();
			$merchant_payment_methods = $json['payment_options'];
			return $this->flatten_payment_options( $merchant_payment_methods );
		}
	}
	/**
	 * Create a flat array with all methods in all options.
	 *
	 * @param array|null $options Payment options to faltten.
	 * @return array
	 */
	private function flatten_payment_options( $options ) {
		return array_reduce(
			$options ? $options : array(),
			function ( $methods, $family ) {
				return array_merge(
					$methods,
					$family['methods']
				);
			},
			array()
		);
	}
	/**
	 * Recover the payment methods either in db or file.
	 *
	 * @return array
	 */
	private function get_stored_payment_methods() {
		if ( ! self::$raw_merchant_payment_methods ) {
			self::$raw_merchant_payment_methods = get_option(
				'SEQURA_PAYMENT_METHODS_' . $this->helper->get_merchant_ref(),
				get_option( 'SEQURA_PAYMENT_METHODS' )
			);
			if ( mb_strlen( self::$raw_merchant_payment_methods, '8bit' ) < 4096 && file_exists( self::$raw_merchant_payment_methods ) ) {
				// phpcs:disable WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
				self::$raw_merchant_payment_methods = file_get_contents( self::$raw_merchant_payment_methods );
				// phpcs:enable
			}
		}
		return json_decode( self::$raw_merchant_payment_methods, true );
	}
	// phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_tempnam, WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
	/**
	 * Store the payment methods either in db or file.
	 *
	 * @return void
	 */
	private function update_stored_payment_methods() {
		if ( mb_strlen( self::$raw_merchant_payment_methods, '8bit' ) > 64000 ) {
			$tmp_file = tempnam( sys_get_temp_dir(), 'sq_pms' );
			file_put_contents( $tmp_file, self::$raw_merchant_payment_methods );
			self::$raw_merchant_payment_methods = $tmp_file;
		}
		update_option(
			'SEQURA_PAYMENT_METHODS_' . $this->helper->get_merchant_ref(),
			self::$raw_merchant_payment_methods
		);
	}
	// phpcs:enable WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_tempnam, WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
}
