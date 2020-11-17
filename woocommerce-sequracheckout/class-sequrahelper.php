<?php
/**
 * Helper class.
 *
 * @package woocommerce-sequra
 */

/**
 * SequraHelper class
 */
class SequraHelper {

	const ISO8601_PATTERN = '^((\d{4})-([0-1]\d)-([0-3]\d))+$|P(\d+Y)?(\d+M)?(\d+W)?(\d+D)?(T(\d+H)?(\d+M)?(\d+S)?)?$';

	/**
	 * Seqtttings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Http client.
	 *
	 * @var \Sequra\Client
	 */
	private $client;
	/**
	 * Order Builder
	 *
	 * @var SequraBuilderWC
	 */
	private $builder;
	/**
	 * Identity forms array
	 *
	 * @var array
	 */
	private $identity_form = array();

	/**
	 * Constructor for payment module
	 *
	 * @param array $settings Payment method settings.
	 */
	public function __construct( $settings = null ) {
		$this->settings      = $settings ? $settings : get_option( 'woocommerce_sequra_settings', array() );
		$this->identity_form = null;
		$this->dir           = dirname( __FILE__ ) . '/';
		require_once $this->dir . 'vendor/autoload.php';
		if ( ! class_exists( 'SequraTempOrder' ) ) {
			require_once $this->dir . 'class-sequratemporder.php';
		}
	}

	/**
	 * Undocumented function
	 *
	 * @return array
	 */
	public static function get_empty_core_settings() {
		return array(
			'env'                => 1,
			'merchantref'        => '',
			'assets_secret'      => '',
			'user'               => '',
			'password'           => '',
			'enable_for_virtual' => 'no',
			'debug'              => 'no',
		);
	}
	/**
	 * Undocumented function
	 *
	 * @return string
	 */
	public static function get_cart_info_from_session() {
		sequra_add_cart_info_to_session();

		return WC()->session->get( 'sequra_cart_info' );
	}
	/**
	 * Check if all products in cart are virtual
	 *
	 * @param WC_Cart $cart Cart to check.
	 * @return boolean
	 */
	public static function is_fully_virtual( WC_Cart $cart ) {
		return ! $cart::needs_shipping();
	}
	/**
	 * Undocumented function
	 *
	 * @param string $service_date Service date.
	 * @return boolean
	 */
	public static function validate_service_date( $service_date ) {
		return preg_match( '/' . self::ISO8601_PATTERN . '/', $service_date );
	}
	/**
	 * Test if it is order_review ajax call
	 *
	 * @return boolean
	 */
	public static function is_order_review() {
		return is_ajax() && $_REQUEST['wc-ajax'] === "update_order_review";
	}

	/**
	 * Test if it is checkout url
	 *
	 * @return boolean
	 */
	public static function is_checkout() {
		$script_name = isset( $_SERVER['SCRIPT_NAME'] ) ?
			sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) : '';
		$is_checkout = 'admin-ajax.php' === basename( $script_name ) ||
			get_the_ID() == wc_get_page_id( 'checkout' ) ||
			(
				isset( $_SERVER['REQUEST_METHOD'] ) &&
				'POST' === $_SERVER['REQUEST_METHOD']
			);
		return $is_checkout;
	}
	/**
	 * Test if available for IP address
	 *
	 * @return boolean
	 */
	public function is_available_for_ip() {
		if ( '' !== $this->settings['test_ips'] ) {
			$ips         = explode( ',', $this->settings['test_ips'] );
			$remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
			return in_array( $remote_addr, $ips, true );
		}
		return true;
	}
	/**
	 * Undocumented function
	 *
	 * @param float $amount amount.
	 * @return array
	 */
	public function get_credit_agreements( $amount ) {
		return $this->get_client()->getCreditAgreements(
			$this->get_builder()->integerPrice( $amount ),
			$this->settings['merchantref']
		);
	}

	/**
	 * Get SeQura http client
	 *
	 * @return \Sequra\Client
	 */
	public function get_client() {
		if ( $this->client instanceof \Sequra\PhpClient\Client ) {
			return $this->client;
		}
		if ( ! class_exists( '\Sequra\PhpClient\Client' ) ) {
			require_once $this->dir . 'lib/\Sequra\PhpClient\Client.php';
		}
		\Sequra\PhpClient\Client::$endpoint   = SequraPaymentGateway::$endpoints[ $this->settings['env'] ];
		\Sequra\PhpClient\Client::$user       = $this->settings['user'];
		\Sequra\PhpClient\Client::$password   = $this->settings['password'];
		\Sequra\PhpClient\Client::$user_agent = 'cURL WooCommerce ' . WOOCOMMERCE_VERSION . ' php ' . phpversion();
		$this->client                         = new \Sequra\PhpClient\Client();

		return $this->client;
	}

	/**
	 * Get SeQura Builder
	 *
	 * @param WC_Order $order input order to build data.
	 * @return SequraBuilderWC
	 */
	public function get_builder( WC_Order $order = null ) {
		if ( $this->builder instanceof \Sequra\PhpClient\BuilderAbstract ) {
			return $this->builder;
		}
		if ( ! class_exists( 'SequraBuilderWC' ) ) {
			require_once $this->dir . 'class-sequrabuilderwc.php';
		}
		$builder_class = apply_filters( 'sequra_set_builder_class', 'SequraBuilderWC' );
		$this->builder = new $builder_class( $this->settings['merchantref'], $order );

		return $this->builder;
	}

	/**
	 * Undocumented function
	 *
	 * @param  WC_Order $order Approved order.
	 * @return boolean
	 */
	public function get_approval( $order ) {
		$client  = $this->get_client();
		$builder = $this->get_builder( $order );
		if ( isset( $_POST['signature'] ) &&
			$builder->sign( $order->get_id() ) !== sanitize_text_field( wp_unslash( $_POST['signature'] ) )
		) {
			http_response_code( 498 );
			die( 'Not valid signature' );
		}
		$sq_state = isset( $_POST['sq_state'] )? $_POST['sq_state'] : 'approved' ;
		$data      = $builder->build( 'confirmed' );
		$order_ref = isset( $_POST['order_ref'] ) ? sanitize_text_field( wp_unslash( $_POST['order_ref'] ) ) : '';
		$uri       = '/' . $order_ref;
		$client->updateOrder( $uri, $data );
		update_post_meta( (int) $order->get_id(), 'Transaction ID', $uri );
		update_post_meta( (int) $order->get_id(), 'Transaction Status', $client->getStatus() );
		/*TODO: Store more information for later use in stats, like browser*/
		if ( ! $client->succeeded() ) {
			http_response_code( 410 );
			die(
				'Error: ' .
				json_encode( $client->getJson() )
			);
		}

		return true;
	}

	/**
	 * Undocumented function
	 *
	 * @param  WC_Order $order Approved order.
	 * @return boolean
	 */
	public function set_on_hold( $order ) {
		$client  = $this->get_client();
		$builder = $this->get_builder( $order );
		if ( isset( $_POST['signature'] ) &&
			$builder->sign( $order->get_id() ) !== sanitize_text_field( wp_unslash( $_POST['signature'] ) )
		) {
			http_response_code( 498 );
			die( 'Not valid signature' );
		}
		$data      = $builder->build( 'on_hold' );
		$order_ref = isset( $_POST['order_ref'] ) ? sanitize_text_field( wp_unslash( $_POST['order_ref'] ) ) : '';
		$uri       = '/' . $order_ref;
		$client->updateOrder( $uri, $data );
		update_post_meta( (int) $order->get_id(), 'Transaction ID', $uri );
		update_post_meta( (int) $order->get_id(), 'Transaction Status', 'in review' );
		/*TODO: Store more information for later use in stats, like browser*/
		if ( ! $client->succeeded() ) {
			http_response_code( 410 );
			die(
				'Error: ' .
				json_encode( $client->getJson() )
			);
		}

		return true;
	}

	/**
	 * Undocumented function
	 *
	 * @param WC_Order $order order where post meta info will be added.
	 * @return void
	 */
	public function add_payment_info_to_post_meta( WC_Order $order ) {
		// phpcs:disable WordPress.Security.NonceVerification.NoNonceVerification,
		if ( isset( $_REQUEST['order_ref'] ) ) {
			$order_ref = sanitize_text_field( wp_unslash( $_REQUEST['order_ref'] ) );
			update_post_meta( (int) $order->get_id(), 'Transaction ID', $order_ref );
			update_post_meta( (int) $order->get_id(), '_order_ref', $order_ref );
			update_post_meta( (int) $order->get_id(), '_transaction_id', $order_ref );
		}
		if ( isset( $_REQUEST['product_code'] ) ) {
			update_post_meta( (int) $order->get_id(), '_product_code', sanitize_text_field( wp_unslash( $_REQUEST['product_code'] ) ) );
		}
		// phpcs:enable
	}

	/**
	 * Undocumented function
	 *
	 * @param array    $options  Options to request the identity form.
	 * @param WC_Order $wc_order Order.
	 * @return string
	 */
	public function start_solicitation( WC_Order $wc_order = null ) {
		$client  = $this->get_client();
		$builder = $this->get_builder( $wc_order );
		try {
			$order = $builder->build();
			$client->startSolicitation( $order );
			if ( $client->succeeded() ) {
				$uri = $client->getOrderUri();
				WC()->session->set( 'sequraURI', $uri );
				return $uri;
			} else {
				if ( 'yes' === $this->settings['debug'] ) {
					$this->pm->log->add( 'sequra', $client->getJson() );
					$this->pm->log->add( 'sequra', 'Invalid payload:' . $order );
				};
			}
		} catch ( Exception $e ) {
			if ( 'yes' === $this->settings['debug'] ) {
				$this->pm->log->add( 'sequra', $e->getMessage() );
			};
		}
		return false;
	}

	/**
	 * Undocumented function
	 *
	 * @param array    $options  Options to request the identity form.
	 * @param WC_Order $wc_order Order.
	 * @return string
	 */
	public function get_identity_form( array $options, WC_Order $wc_order = null ) {
		if ( is_null( $this->identity_form[$options[ 'product' ].'_'.$options[ 'campaign' ]] ) && $this->start_solicitation( $wc_order )) {
			$this->identity_form[$options[ 'product' ].'_'.$options[ 'campaign' ]] = $this->get_client()->getIdentificationForm(
				$this->get_client()->getOrderUri(), $options
			);
		}
		return $this->identity_form[$options[ 'product' ].'_'.$options[ 'campaign' ]];
	}
	/**
	 * Template loader function
	 *
	 * @param string $template template file name.
	 * @return string
	 */
	public static function template_loader( $template ) {
		if ( file_exists( get_stylesheet_directory() . '/' . WC_TEMPLATE_PATH . $template . '.php' ) ) {
			return get_stylesheet_directory() . '/' . WC_TEMPLATE_PATH . $template . '.php';
		} elseif ( file_exists( get_template_directory() . '/' . WC_TEMPLATE_PATH . $template . '.php' ) ) {
			return get_template_directory() . '/' . WC_TEMPLATE_PATH . $template . '.php';
		} elseif ( file_exists( get_stylesheet_directory() . '/' . $template . '.php' ) ) {
			return get_stylesheet_directory() . '/' . $template . '.php';
		} elseif ( file_exists( get_template_directory() . '/' . $template . '.php' ) ) {
			return get_template_directory() . '/' . $template . '.php';
		} else {
			return WP_CONTENT_DIR . '/plugins/' . plugin_basename( dirname( __FILE__ ) ) . '/templates/' . $template . '.php';
		}
	}
	/**
	 * Undocumented function
	 *
	 * @return boolean
	 */
	public function is_elegible_for_service_sale() {
		$elegible       = false;
		$services_count = 0;
		if ( is_array( WC()->cart->cart_contents ) ){
			foreach ( WC()->cart->cart_contents as $values ) {
				if ( get_post_meta( $values['product_id'], 'is_sequra_service', true ) != 'no' ) {
					$services_count += $values['quantity'];
					$elegible        = (1 == $services_count);
				}
			}
		}

		return apply_filters( 'woocommerce_cart_is_elegible_for_service_sale', $elegible );
	}

	/**
	 * Undocumented function
	 *
	 * @return boolean
	 */
	public function is_elegible_for_product_sale() {
		global $wp;
		$elegible = true;
		// Only reject if all products are virtual (don't need shipping).
		if( isset( $wp->query_vars['order-pay'] ) ) { //if paying an order
			$order     = wc_get_order( $wp->query_vars['order-pay'] );
			if ( ! $order->needs_shipping_address() ) {
				$elegible = false;
			}
		} elseif ( ! WC()->cart->needs_shipping() ) { //If paying cart
			$elegible = false;
		}
		return apply_filters( 'woocommerce_cart_is_elegible_for_product_sale', $elegible );
	}

	/**
	 * Undocumented function
	 *
	 * @return boolean
	 */
	public function is_available_in_checkout() {
		$return = true;
		foreach ( WC()->cart->cart_contents as $values ) {
			if ( get_post_meta( $values['product_id'], 'is_sequra_banned', true ) === 'yes' ) {
				$return = false;
			}
		}

		return apply_filters( 'woocommerce_cart_sq_is_available_in_checkout', $return );
	}

	/**
	 * Undocumented function
	 *
	 * @param int $product_id page's product id.
	 * @return boolean
	 */
	public function is_available_in_product_page( $product_id ) {
		$return = get_post_meta( $product_id, 'is_sequra_banned', true ) !== 'yes';
		return apply_filters( 'woocommerce_sq_is_available_in_product_page', $return, $product_id );
	}
}

// phpcs:disable
if ( ! function_exists( 'http_response_code' ) ) {
	/**
	 * php 5.3 compatibility
	 *
	 * @param int $code
	 * @return void
	 */
	function http_response_code( $code = null ) {

		if ( null !== $code ) {

			switch ( $code ) {
				case 100:
					$text = 'Continue';
					break;
				case 101:
					$text = 'Switching Protocols';
					break;
				case 200:
					$text = 'OK';
					break;
				case 201:
					$text = 'Created';
					break;
				case 202:
					$text = 'Accepted';
					break;
				case 203:
					$text = 'Non-Authoritative Information';
					break;
				case 204:
					$text = 'No Content';
					break;
				case 205:
					$text = 'Reset Content';
					break;
				case 206:
					$text = 'Partial Content';
					break;
				case 300:
					$text = 'Multiple Choices';
					break;
				case 301:
					$text = 'Moved Permanently';
					break;
				case 302:
					$text = 'Moved Temporarily';
					break;
				case 303:
					$text = 'See Other';
					break;
				case 304:
					$text = 'Not Modified';
					break;
				case 305:
					$text = 'Use Proxy';
					break;
				case 400:
					$text = 'Bad Request';
					break;
				case 401:
					$text = 'Unauthorized';
					break;
				case 402:
					$text = 'Payment Required';
					break;
				case 403:
					$text = 'Forbidden';
					break;
				case 404:
					$text = 'Not Found';
					break;
				case 405:
					$text = 'Method Not Allowed';
					break;
				case 406:
					$text = 'Not Acceptable';
					break;
				case 407:
					$text = 'Proxy Authentication Required';
					break;
				case 408:
					$text = 'Request Time-out';
					break;
				case 409:
					$text = 'Conflict';
					break;
				case 410:
					$text = 'Gone';
					break;
				case 411:
					$text = 'Length Required';
					break;
				case 412:
					$text = 'Precondition Failed';
					break;
				case 413:
					$text = 'Request Entity Too Large';
					break;
				case 414:
					$text = 'Request-URI Too Large';
					break;
				case 415:
					$text = 'Unsupported Media Type';
					break;
				case 500:
					$text = 'Internal Server Error';
					break;
				case 501:
					$text = 'Not Implemented';
					break;
				case 502:
					$text = 'Bad Gateway';
					break;
				case 503:
					$text = 'Service Unavailable';
					break;
				case 504:
					$text = 'Gateway Time-out';
					break;
				case 505:
					$text = 'HTTP Version not supported';
					break;
				default:
					exit( 'Unknown http status code "' . htmlentities( $code ) . '"' );
				break;
			}

			$protocol = ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' );

			header( $protocol . ' ' . $code . ' ' . $text );

			$GLOBALS['http_response_code'] = $code;

		} else {

			$code = ( isset( $GLOBALS['http_response_code'] ) ? $GLOBALS['http_response_code'] : 200 );

		}

		return $code;
	}
}
// phpcs:enable
