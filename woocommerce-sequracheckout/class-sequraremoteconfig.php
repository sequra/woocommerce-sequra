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
	 * Undocumented variable
	 *
	 * @var array
	 */
	private static $merchant_payment_methods = null;

	/**
	 * Undocumented variable
	 *
	 * @var array
	 */
	private static $product_family_keys = array(
		'pp10' => 'CARD',       // Paga Ahora
		'fp1'  => 'CARD',
		'i1'   => 'INVOICE',     // Paga después
		'pp5'  => 'INVOICE',
		'pp3'  => 'PARTPAYMENT', // Paga fraccionado
		'pp6'  => 'PARTPAYMENT',
		'pp9'  => 'PARTPAYMENT',
	);

	/**
	 * Contructor
	 *
	 * @param array $settings Module settings.
	 */
	public function __construct( $settings ) {
		$this->settings = $settings;
		$this->helper   = new SequraHelper( $settings );
	}

	/**
	 * Undocumented function
	 *
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
		return $method['product'] . ( isset( $method['campaign'] ) ? '_' . $method['campaign'] : '' );
	}

	public function get_title_from_unique_product_code( $product_campaign ) {
		list($product, $campaign) = explode( '_', $product_campaign);
		foreach ( $this->get_merchant_payment_methods() as $method ) {
			if (
				$method['product'] == $product &&
				( ! $campaign || $method['product'] == $campaign)
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
			serialize( $sq_products )
		);
		if ( in_array( 'i1', $sq_products ) ) {
			update_option( 'SEQURA_INVOICE_PRODUCT', 'i1' );
		}
		if ( in_array( 'pp5', $sq_products ) ) {
			update_option( 'SEQURA_CAMPAIGN_PRODUCT', 'pp5' );
		}
		if ( in_array( 'pp3', $sq_products ) ) {
			update_option( 'SEQURA_PARTPAYMENT_PRODUCT', 'pp3' );
		} elseif ( in_array( 'pp6', $sq_products ) ) {
			update_option( 'SEQURA_PARTPAYMENT_PRODUCT', 'pp6' );
		} elseif ( in_array( 'pp9', $sq_products ) ) {
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
		if ( $force_refresh || ! get_option( 'SEQURA_PAYMENT_METHODS' ) ) {
			$client = $this->helper->get_client();
			$client->getMerchantPaymentMethods( $this->settings['merchantref'] );
			if ( $client->succeeded() ) {
				self::$merchant_payment_methods = ( $client->getJson() )['payment_options'];
				update_option(
					'SEQURA_PAYMENT_METHODS',
					$client->getRawResult()
				);
			}
		}
		if ( ! self::$merchant_payment_methods ) {
			self::$merchant_payment_methods = (
				json_decode(
					get_option( 'SEQURA_PAYMENT_METHODS' ),
					true
				)
			)['payment_options'];
		}
		return $this->flatten_payment_options(
			self::$merchant_payment_methods
		);
	}

	public function get_available_payment_methods() {
		$ret = array();
		$client = $this->helper->get_client();
		if ( $this->helper->start_solicitation() ) {
			$ret = $this->get_order_payment_methods( $client->getOrderUri() );
		}
		return $ret;
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
			$merchant_payment_methods = ( $client->getJson() )['payment_options'];
			return $this->flatten_payment_options($merchant_payment_methods);
		}
	}

	private function flatten_payment_options($options) {
		return array_reduce(
			$options
			,
			function ( $methods, $family ) {
				return array_merge(
					$methods,
					$family['methods']
				);
			},
			[]
		);
	}
}
