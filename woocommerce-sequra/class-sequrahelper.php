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
	 * Core setting placeholder
	 *
	 * @var array
	 */
	public $empty_core_settings;
	/**
	 * Payment method
	 *
	 * @var mixed SequraInvoiceGateway or SequraPartPaymentGateway
	 */
	private $pm;
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
	 * Constructor for payment module
	 *
	 * @param mixed $pm SequraInvoiceGateway or SequraPartPaymentGateway.
	 */
	public function __construct( $pm ) {
		$this->pm           = $pm;
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
	 * @param string $service_end_date Service end date.
	 * @return boolean
	 */
	public static function validate_service_end_date( $service_end_date ) {
		return preg_match( '/' . self::ISO8601_PATTERN . '/', $service_end_date );
	}
	/**
	 * Test if is admin url
	 *
	 * @return boolean
	 */
	public static function is_admin() {
		$script_name = isset( $_SERVER['SCRIPT_NAME'] ) ?
			sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) : '';
		return ! is_admin() || 'admin-ajax.php' === basename( $script_name );
	}

	/**
	 * Test if it is checkout url
	 *
	 * @return boolean
	 */
	public static function is_checkout() {
		return get_the_ID() == wc_get_page_id( 'checkout' ) ||
			(
				isset( $_SERVER['REQUEST_METHOD'] ) &&
				'POST' === $_SERVER['REQUEST_METHOD']
			);
	}
	/**
	 * Test if available for Country
	 *
	 * @return boolean
	 */
	public function is_available_for_country() {
		return ! $this->pm->enable_for_countries ||
			in_array( WC()->customer->get_shipping_country(), $this->pm->enable_for_countries, true );
	}
	/**
	 * Test if available for currency
	 *
	 * @return boolean
	 */
	public function is_available_for_currency() {
		return ! $this->pm->enable_for_currencies ||
			in_array( get_woocommerce_currency(), $this->pm->enable_for_currencies, true );
	}
	/**
	 * Test if available for IP address
	 *
	 * @return boolean
	 */
	public function is_available_for_ip() {
		if ( '' !== $this->pm->core_settings['test_ips'] ) {
			$ips         = explode( ',', $this->core_settings['test_ips'] );
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
			$this->pm->merchantref
		);
	}

	/**
	 * Get Sequra http client
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
		\Sequra\PhpClient\Client::$endpoint   = SequraPaymentGateway::$endpoints[ $this->pm->core_settings['env'] ];
		\Sequra\PhpClient\Client::$user       = $this->pm->core_settings['user'];
		\Sequra\PhpClient\Client::$password   = $this->pm->core_settings['password'];
		\Sequra\PhpClient\Client::$user_agent = 'cURL WooCommerce ' . WOOCOMMERCE_VERSION . ' php ' . phpversion();
		$this->client                         = new \Sequra\PhpClient\Client();

		return $this->client;
	}

	/**
	 * Get Sequra Builder
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
		$builder_class  = apply_filters( 'sequra_setbuilder_class', 'SequraBuilderWC' );
		$this->builder = new $builder_class( $this->pm->core_settings['merchantref'], $order );

		return $this->builder;
	}

	/**
	 * Undocumented function
	 *
	 * @return mixed
	 */
	public function check_response() {
		if ( ! isset( $_REQUEST['order'] ) ) {
			return;
		}
		$order = new WC_Order( sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) );
		if ( isset( $_REQUEST['signature'] ) ) {
			return $this->check_ipn( $order );
		}
		$url = $this->pm->get_return_url( $order );
		if ( ! $order->is_paid() ) {
			wc_add_notice(
				__(
					'Ha habido un probelma con el pago. Por favor, inténtelo de nuevo o escoja otro método de pago.',
					'wc_sequra'
				),
				'error'
			);
			// $url = $pm->get_checkout_payment_url();  Notice is not shown in payment page
			$url = $order->get_cancel_order_url();
		}
		wp_safe_redirect( $url, 302 );
	}
	/**
	 * Undocumented function
	 *
	 * @param WC_Order $order check if ipn is correct.
	 * @return mixed
	 */
	public function check_ipn( WC_Order $order ) {
		do_action( 'woocommerce_' . $this->pm->id . '_process_payment', $order, $this->pm );
		$approval = apply_filters(
			'woocommerce_' . $this->pm->id . '_process_payment',
			$this->get_approval( $order ),
			$order,
			$this->pm
		);
		if ( $approval ) {
			// Payment completed.
			$order->add_order_note( __( 'Payment accepted by SeQura', 'wc_sequra' ) );
			$this->add_payment_info_to_post_meta( $order );
			$order->payment_complete();
		}
		exit();
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
		$builder->setPaymentMethod( $this->pm );
		if ( isset( $_REQUEST['signature'] ) &&
			$builder->sign( $order->id ) !== sanitize_text_field( wp_unslash( $_REQUEST['signature'] ) ) &&
			$this->pm->ipn
		) {
			http_response_code( 498 );
			die( 'Not valid signature' );
		}
		$data      = $builder->build( 'confirmed' );
		$order_ref = isset( $_REQUEST['order_ref'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['order_ref'] ) ) : '';
		$uri       = $this->pm->endpoint . '/' . $order_ref;
		$client->updateOrder( $uri, $data );
		update_post_meta( (int) $order->id, 'Transaction ID', $uri );
		update_post_meta( (int) $order->id, 'Transaction Status', $client->getStatus() );
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
		if ( $this->pm->ipn ) {
			// phpcs:disable WordPress.Security.NonceVerification.NoNonceVerification,
			if ( isset( $_REQUEST['order_ref'] ) ) {
				$order_ref = sanitize_text_field( wp_unslash( $_REQUEST['order_ref'] ) );
				update_post_meta( (int) $order->id, 'Transaction ID', $order_ref );
				update_post_meta( (int) $order->id, '_order_ref', $order_ref );
				update_post_meta( (int) $order->id, '_transaction_id', $order_ref );
			}
			if ( isset( $_REQUEST['product_code'] ) ) {
				update_post_meta( (int) $order->id, '_product_code', sanitize_text_field( wp_unslash( $_REQUEST['product_code'] ) ) );
			}
			// phpcs:enable
			// @todo: .
			// update_post_meta((int)$order->id, '_sequra_cart_ref', $sequra_cart_info['ref']);
		} else {
			$sequra_cart_info = WC()->session->get( 'sequra_cart_info' );
			update_post_meta( (int) $order->id, 'Transaction ID', WC()->session->get( 'sequraURI' ) );
			update_post_meta( (int) $order->id, '_sequra_cart_ref', $sequra_cart_info['ref'] );
		}
	}

	/**
	 * Undocumented function
	 *
	 * @param int $order_id common receipt_page method.
	 * @return void
	 */
	public function receipt_page( $order_id ) {
		$order = new WC_Order( $order_id );
		echo '<p>' . wp_kses_post(
			__(
				'Thank you for your order, please click the button below to pay with SeQura.',
				'wc_sequra'
			)
		) . '</p>';
		$options = array( 'product' => $this->pm->product );
		$options['rebranding'] = 'true'; // Temporarly.
		$this->get_identity_form(
			apply_filters( 'wc_sequra_pumbaa_options', $options, $order, $this->pm ),
			$order
		);
		require self::template_loader( 'payment-identification' );
	}
	/**
	 * Undocumented function
	 *
	 * @param array    $options  Options to request the identity form.
	 * @param WC_Order $wc_order Order.
	 * @return string
	 */
	public function get_identity_form( array $options, WC_Order $wc_order = null ) {
		if ( is_null( $this->identity_form ) ) {
			$client  = $this->get_client();
			$builder = $this->get_builder( $wc_order );
			$builder->setPaymentMethod( $this->pm );
			try {
				$order = $builder->build();
				$client->startSolicitation( $order );
				if ( $client->succeeded() ) {
					$uri = $client->getOrderUri();
					WC()->session->set( 'sequraURI', $uri );

					$this->identity_form = $client->getIdentificationForm( $uri, $options );
				} else {
					if ( 'yes' === $this->pm->debug ) {
						$this->pm->log->add( 'sequra', $client->getJson() );
						$this->pm->log->add( 'sequra', 'Invalid payload:' . $order );
					};
				}
			} catch ( Exception $e ) {
				if ( 'yes' === $this->pm->debug ) {
					$this->pm->log->add( 'sequra', $e->getMessage() );
				};
			}
		}

		return $this->identity_form;
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
		foreach ( WC()->cart->cart_contents as $values ) {
			if ( get_post_meta( $values['product_id'], 'is_sequra_service', true ) != 'no' ) {
				$services_count += $values['quantity'];
				$elegible        = 1 === $services_count;
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
		$elegible = true;
		// Only reject if all products are virtual (don't need shipping).
		if ( ! WC()->cart->needs_shipping() ) {
			$elegible = false;
		}
		return apply_filters( 'woocommerce_cart_is_elegible_for_product_sale', $elegible );
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
