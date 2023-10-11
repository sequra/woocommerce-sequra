<?php
/**
 * Helper class.
 *
 * @package woocommerce-sequra
 */

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
/**
 * SequraHelper class
 */
class SequraHelper {


	const ISO8601_PATTERN = '^((\d{4})-([0-1]\d)-([0-3]\d))+$|P(\d+Y)?(\d+M)?(\d+W)?(\d+D)?(T(\d+H)?(\d+M)?(\d+S)?)?$';
	/**
	 * The Monolog Logger
	 *
	 * @var Logger $logger
	 */
	protected $logger;
	
	/**
	 * Seqtttings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Are valid creadentials set?.
	 *
	 * @var bool
	 */
	private $valid_auth;

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
	 * Undocumented variable
	 * 
	 * @var string
	 */
	private $dir;

	/**
	 * Instance
	 *
	 * @var SequraHelper
	 */
	public static $instance = null;
	/**
	 * Function to get instance of SequraHelper
	 * 
	 * @param array $settings configuration settings.
	 * @return SequraHelper 
	 */
	public static function get_instance( $settings = null ) {
		if ( ! self::$instance ) {
			self::$instance = new SequraHelper( $settings );
		}
		return self::$instance;
	}

	/**
	 * Constructor for payment module
	 *
	 * @param array $settings Payment method settings.
	 */
	public function __construct( $settings = null ) {
		$this->settings      = $settings ? $settings : get_option( 'woocommerce_sequra_settings', self::get_empty_core_settings() );
		$this->identity_form = null;
		$this->dir           = dirname( __FILE__ ) . '/';
		// phpcs:disable WordPressVIPMinimum.Files.IncludingFile.UsingVariable
		require_once $this->dir . 'vendor/autoload.php';
		if ( ! class_exists( 'SequraTempOrder' ) ) {
			require_once $this->dir . 'class-sequratemporder.php';
		}
		// phpcs:enable
		$this->logger = new Logger( 'SEQURA-LOGGER' );
		$this->logger->pushHandler( new StreamHandler( wp_upload_dir()['basedir'] . '/wc-logs/sequra.log', 'yes' === $this->settings['debug'] ? Logger::DEBUG : Logger::INFO ) );
	}
	/**
	 * Get logger
	 *
	 * @return Logger 
	 */
	public function get_logger() {
		return $this->logger;
	}
	/**
	 * Get merchant reference
	 *
	 * @return mixed 
	 */
	public function get_merchant_ref() {
		/**
		 * Filter merchant reference
		 *
		 * @since 2.0.0
		 */
		return apply_filters(
			'woocommerce_sequra_get_merchant_ref',
			isset( $this->settings['merchantref'] ) ? $this->settings['merchantref'] : '',
			$this
		);
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
	 * Test if it is an ajax or REST api Request
	 *
	 * @return boolean
	 */
	public static function is_ajax_request() {
		return is_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST );
	}

	/**
	 * Test if it is order_review ajax call
	 *
	 * @return boolean
	 */
	public static function is_order_review() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		return is_ajax() && isset( $_REQUEST['wc-ajax'] ) && 'update_order_review' === $_REQUEST['wc-ajax'];
		// phpcs:enable
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
			get_the_ID() === wc_get_page_id( 'checkout' ) ||
			( isset( $_SERVER['REQUEST_METHOD'] ) &&
				'POST' === $_SERVER['REQUEST_METHOD']
			);
		return $is_checkout;
	}
	// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
	/**
	 * Use to prevent css attribute removal
	 *
	 * @param array $allowed_css allowed css attributes.
	 * @return null
	 */
	public static function allow_css_attributes( $allowed_css ) {
		return null;
	}
	// phpcs:enable

	/**
	 * Test if credentials are valid
	 *
	 * @return boolean
	 */
	public function is_valid_auth() {
		if ( ( is_null( $this->valid_auth ) || ! $this->valid_auth ) && ! $this->is_ajax_request() && is_admin() ) {
			$this->valid_auth = $this->get_client()->isValidAuth();
			update_option(
				'SEQURA_VALID_AUTH',
				$this->valid_auth
			);
		}
		return ! ! get_option( 'SEQURA_VALID_AUTH' );
	}

	/**
	 * Test if available for IP address
	 *
	 * @return boolean
	 */
	public function is_available_for_ip() {
		if ( '' !== $this->settings['test_ips'] ) {
			// phpcs:disable WordPressVIPMinimum.Variables.ServerVariables.UserControlledHeaders, WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__REMOTE_ADDR__
			$ips         = explode( ',', $this->settings['test_ips'] );
			$remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
			// phpcs:enable
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
			$this->get_merchant_ref()
		);
	}

	/**
	 * Get seQura http client
	 *
	 * @return \Sequra\Client
	 */
	public function get_client() {
		// phpcs:disable WordPressVIPMinimum.Files.IncludingFile.UsingVariable
		if ( $this->client instanceof \Sequra\PhpClient\Client ) {
			return $this->client;
		}
		if ( ! class_exists( '\Sequra\PhpClient\Client' ) ) {
			require_once $this->dir . 'lib/\Sequra\PhpClient\Client.php';
		}
		// phpcs:enable
		\Sequra\PhpClient\Client::$endpoint   = SequraPaymentGateway::$endpoints[ isset( $this->settings['env'] ) ? $this->settings['env'] : 1 ];
		\Sequra\PhpClient\Client::$user       = isset( $this->settings['user'] ) ? $this->settings['user'] : '';
		\Sequra\PhpClient\Client::$password   = isset( $this->settings['password'] ) ? $this->settings['password'] : '';
		\Sequra\PhpClient\Client::$user_agent = 'cURL WooCommerce ' . WOOCOMMERCE_VERSION . ' php ' . phpversion();
		$this->client                         = new \Sequra\PhpClient\Client();

		return $this->client;
	}

	/**
	 * Get seQura Builder
	 *
	 * @param WC_Order $order input order to build data.
	 * @return SequraBuilderWC
	 */
	public function get_builder( WC_Order $order = null ) {
		// phpcs:disable WordPressVIPMinimum.Files.IncludingFile.UsingVariable
		if ( $this->builder instanceof \Sequra\PhpClient\BuilderAbstract ) {
			return $this->builder;
		}
		if ( ! class_exists( 'SequraBuilderWC' ) ) {
			require_once $this->dir . 'class-sequrabuilderwc.php';
		}
		// phpcs:enable
		/**
		 * Filter builder class
		 *
		 * @since 2.0.0
		 */
		$builder_class = apply_filters( 'sequra_set_builder_class', 'SequraBuilderWC' );
		$this->builder = new $builder_class( $this->get_merchant_ref(), $order );

		return $this->builder;
	}

	/**
	 * Undocumented function
	 *
	 * @param  WC_Order $order Approved order.
	 * @return boolean
	 */
	public function get_approval( $order ) {
		// phpcs:disable WordPress.Security.NonceVerification.NoNonceVerification, WordPress.Security.NonceVerification.Missing
		$client  = $this->get_client();
		$builder = $this->get_builder( $order );
		if (
			isset( $_POST['signature'] ) &&
			$builder->sign( $order->get_id() ) !== sanitize_text_field( wp_unslash( $_POST['signature'] ) )
		) {
			$this->logger->error( 'Error: Not valid signature' . sanitize_text_field( wp_unslash( $_POST['signature'] ) ) . '!=' . $builder->sign( $order->get_id() ) );
			http_response_code( 498 );
			die( 'Not valid signature' );
		}
		$data      = $builder->build( 'confirmed' );
		$order_ref = isset( $_POST['order_ref'] ) ? sanitize_text_field( wp_unslash( $_POST['order_ref'] ) ) : '';
		$uri       = '/' . $order_ref;
		$client->updateOrder( $uri, $data );
		update_post_meta( (int) $order->get_id(), 'Transaction ID', $uri );
		update_post_meta( (int) $order->get_id(), 'Transaction Status', $client->getStatus() );
		// phpcs:enable WordPress.Security.NonceVerification.NoNonceVerification, WordPress.Security.NonceVerification.Missing
		if ( ! $client->succeeded() ) {
			$this->logger->error( 'Error: ' . wp_json_encode( $client->getJson() ) );
			http_response_code( 410 );
			die(
				'Error: ' .
				wp_json_encode( $client->getJson() )
			);
		}

		return true;
	}

	/**
	 * Undocumented function
	 *
	 * @param  WC_Order $order holded order.
	 * @return boolean
	 */
	public function set_on_hold( $order ) {
		$client  = $this->get_client();
		$builder = $this->get_builder( $order );
		// phpcs:disable WordPress.Security.NonceVerification.NoNonceVerification, WordPress.Security.NonceVerification.Missing
		if (
			isset( $_POST['signature'] ) &&
			$builder->sign( $order->get_id() ) !== sanitize_text_field( wp_unslash( $_POST['signature'] ) )
		) {
			$this->logger->error( 'Error: Not valid signature' . sanitize_text_field( wp_unslash( $_POST['signature'] ) ) . '!=' . $builder->sign( $order->get_id() ) );
			http_response_code( 498 );
			die( 'Not valid signature' );
		}
		$data      = $builder->build( 'on_hold' );
		$order_ref = isset( $_POST['order_ref'] ) ? sanitize_text_field( wp_unslash( $_POST['order_ref'] ) ) : '';
		$uri       = '/' . $order_ref;
		$client->updateOrder( $uri, $data );
		update_post_meta( (int) $order->get_id(), 'Transaction ID', $uri );
		update_post_meta( (int) $order->get_id(), 'Transaction Status', 'in review' );
		// phpcs:enable WordPress.Security.NonceVerification.NoNonceVerification, WordPress.Security.NonceVerification.Missing
		if ( ! $client->succeeded() ) {
			$this->logger->error( 'Error: ' . wp_json_encode( $client->getJson() ) );
			http_response_code( 410 );
			die(
				'Error: ' .
				wp_json_encode( $client->getJson() )
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
		// phpcs:disable WordPress.Security.NonceVerification.NoNonceVerification, WordPress.Security.NonceVerification.Recommended
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
				$this->logger->error( $client->getJson() );
				$this->logger->debug( 'Invalid payload:' . $order );
			}
		} catch ( Exception $e ) {
			$this->logger->error( $e->getMessage() );
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
		if (
			( is_null( $this->identity_form ) || is_null( $this->identity_form[ $options['product'] . '_' . $options['campaign'] ] ) )
			&& $this->start_solicitation( $wc_order )
		) {
			$this->identity_form[ $options['product'] . '_' . $options['campaign'] ] = $this->get_client()->getIdentificationForm(
				$this->get_client()->getOrderUri(),
				$options
			);
		}
		return $this->identity_form[ $options['product'] . '_' . $options['campaign'] ];
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
		if ( ! WC()->cart ) {
			return false;
		}
		$elegible       = false;
		$services_count = 0;
		foreach ( WC()->cart->cart_contents as $values ) {
			if ( get_post_meta( $values['product_id'], 'is_sequra_service', true ) !== 'no' ) {
				$services_count += $values['quantity'];
				$elegible        = ( 1 === $services_count );
			}
		}
		/**
		 * Filter if cart is elegible for service sale
		 *
		 * @since 2.0.0
		 */
		return apply_filters( 'woocommerce_cart_is_elegible_for_service_sale', $elegible );
	}

	/**
	 * Undocumented function
	 *
	 * @return boolean
	 */
	public function is_elegible_for_product_sale() {
		global $wp;
		if ( ! WC()->cart ) {
			return false;
		}
		$elegible = true;
		// Only reject if all products are virtual (don't need shipping).
		if ( isset( $wp->query_vars['order-pay'] ) ) { // if paying an order.
			$order = wc_get_order( $wp->query_vars['order-pay'] );
			if ( ! $order->needs_shipping_address() ) {
				$this->logger->debug( 'Order doesn\'t need shipping address seQura will not be offered.' );
				$elegible = false;
			}
		} elseif ( ! WC()->cart->needs_shipping() ) { // If paying cart.
			$this->logger->debug( 'Order doesn\'t need shipping seQura will not be offered.' );
			$elegible = false;
		}
		/**
		 * Filter if cart is elegible for product sale
		 *
		 * @since 2.0.0
		 */
		return apply_filters( 'woocommerce_cart_is_elegible_for_product_sale', $elegible );
	}

	/**
	 * Undocumented function
	 *
	 * @return boolean
	 */
	public function is_available_in_checkout() {
		if ( ! WC()->cart ) {
			return false;
		}
		$return = true;
		foreach ( WC()->cart->cart_contents as $values ) {
			if ( get_post_meta( $values['product_id'], 'is_sequra_banned', true ) === 'yes' ) {
				$this->logger->debug( 'Banned product in the cart seQura will not be offered. Product Id :' . $values['product_id'] );
				$return = false;
			}
		}
		/**
		 * Filter seQura availablity at checkout
		 *
		 * @since 2.0.0
		 */
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
		/**
		 * Filter seQura availablity at product page
		 *
		 * @since 2.0.0
		 */
		return apply_filters( 'woocommerce_sq_is_available_in_product_page', $return, $product_id );
	}
}
