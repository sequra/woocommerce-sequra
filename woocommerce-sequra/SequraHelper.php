<?php

class SequraHelper {
	/* Payment Method */
	private $_pm;
	/* Sequra Client */
	private $_client;
	/* Json builder */
	private $_builder;

	public function __construct( $pm ) {
		$this->_pm = $pm;
		$this->dir = WP_PLUGIN_DIR . "/" . plugin_basename( dirname( __FILE__ ) ) . '/';
		if ( ! class_exists( 'SequraTempOrder' ) ) {
			require_once( $this->dir . 'SequraTempOrder.php' );
		}
	}

	function get_identity_form( $options, $order = null ) {
		$client  = $this->getClient();
		$builder = $this->getBuilder( $order );
		$builder->setPaymentMethod( $this->_pm );
		try {
			$order = $builder->build();
		} catch ( Exception $e ) {
			if ( $this->_pm->debug == 'yes' ) {
				$this->_pm->log->add( 'sequra', $e->getMessage() );
			}

			return '';
		}

		$client->startSolicitation( $order );
		if ( $client->succeeded() ) {
			$uri = $client->getOrderUri();
			WC()->session->set( 'sequraURI', $uri );

			return $client->getIdentificationForm( $uri, $options );
		}
	}

	function get_credit_agreements( $amount ) {
		return $this->getClient()->getCreditAgreements( $this->getBuilder()->integerPrice( $amount ), $this->_pm->merchantref );
	}

	function check_response( $pm ) {
		$order = new WC_Order( $_REQUEST['order'] );
		if ( isset( $_REQUEST['signature'] ) ) {
			return $this->check_ipn( $pm, $order );
		}
		$url = $pm->get_return_url( $order );
		if ( ! $order->is_paid() ) {
			wc_add_notice( __( 'Ha habido un probelma con el pago. Por favor, inténtelo de nuevo o escoja otro método de pago.', 'wc_sequra' ), 'error' );
			//$url = $pm->get_checkout_payment_url();  Notice is not shown in payment page
			$url = $order->get_cancel_order_url();
		}
		wp_redirect( $url, 302 );
	}

	function check_ipn( $pm, $order ) {
		$url = $order->get_cancel_order_url();
		do_action( 'woocommerce_' . $pm->id . '_process_payment', $order, $pm );
		if ( $approval = apply_filters( 'woocommerce_' . $pm->id . '_process_payment', $this->get_approval( $order ), $order, $pm ) ) {
			// Payment completed
			$order->add_order_note( __( 'Payment accepted by SeQura', 'wc_sequra' ) );
			$this->add_payment_info_to_post_meta( $order );
			$order->payment_complete();
			$url = $pm->get_return_url( $order );
		}
		exit();
	}


	function get_approval( $order ) {
		$client  = $this->getClient();
		$builder = $this->getBuilder( $order );
		$builder->setPaymentMethod( $this->_pm );
		if ( $builder->sign( $order->id ) != $_REQUEST['signature'] &&
		     $this->_pm->ipn
		) {
			return false;
		}
		$data = $builder->build( 'confirmed' );
		$uri  = $this->_pm->endpoint . '/' . $_REQUEST['order_ref'];
		$client->updateOrder( $uri, $data );
		update_post_meta( (int) $order->id, 'Transaction ID', $uri );
		update_post_meta( (int) $order->id, 'Transaction Status', $client->getStatus() );
		/*TODO: Store more information for later use in stats, like browser*/
		if ( ! $client->succeeded() ) {
			http_response_code( 410 );

			return false;
		}

		return true;
	}

	function add_payment_info_to_post_meta( $order ) {
		if ( $this->_pm->ipn ) {
			update_post_meta( (int) $order->id, 'Transaction ID', $_REQUEST['order_ref'] );
			update_post_meta( (int) $order->id, '_order_ref', $_REQUEST['order_ref'] );
			update_post_meta( (int) $order->id, '_product_code', $_REQUEST['product_code'] );
			//@TODO
			//update_post_meta((int)$order->id, '_sequra_cart_ref', $sequra_cart_info['ref']);
		} else {
			$sequra_cart_info = WC()->session->get( 'sequra_cart_info' );
			update_post_meta( (int) $order->id, 'Transaction ID', WC()->session->get( 'sequraURI' ) );
			update_post_meta( (int) $order->id, '_sequra_cart_ref', $sequra_cart_info['ref'] );
		}
	}

	public function getClient() {
		if ( $this->_client instanceof SequraClient ) {
			return $this->_client;
		}
		if ( ! class_exists( 'SequraClient' ) ) {
			require_once( $this->dir . 'lib/SequraClient.php' );
		}
		SequraClient::$endpoint   = SequraPaymentGateway::$endpoints[ $this->_pm->coresettings['env'] ];
		SequraClient::$user       = $this->_pm->coresettings['user'];
		SequraClient::$password   = $this->_pm->coresettings['password'];
		SequraClient::$user_agent = 'cURL WooCommerce ' . WOOCOMMERCE_VERSION . ' php ' . phpversion();
		$this->_client            = new SequraClient();

		return $this->_client;
	}

	public function getBuilder( $order = null ) {
		if ( $this->_builder instanceof SequraBuilderAbstract ) {
			return $this->_builder;
		}

		if ( ! class_exists( 'SequraBuilderAbstract' ) ) {
			require_once( $this->dir . 'lib/SequraBuilderAbstract.php' );
		}
		if ( ! class_exists( 'SequraBuilderWC' ) ) {
			require_once( $this->dir . 'SequraBuilderWC.php' );
		}
		$builderClass   = apply_filters( 'sequra_set_builder_class', 'SequraBuilderWC' );
		$this->_builder = new $builderClass( $this->_pm->coresettings['merchantref'], $order );

		return $this->_builder;
	}

	public function template_loader( $template ) {
		if ( file_exists( STYLESHEETPATH . '/' . WC_TEMPLATE_PATH . $template . '.php' ) ) {
			return STYLESHEETPATH . '/' . WC_TEMPLATE_PATH . $template . '.php';
		} elseif ( file_exists( TEMPLATEPATH . '/' . WC_TEMPLATE_PATH . $template . '.php' ) ) {
			return TEMPLATEPATH . '/' . WC_TEMPLATE_PATH . $template . '.php';
		} elseif ( file_exists( STYLESHEETPATH . '/' . $template . '.php' ) ) {
			return STYLESHEETPATH . '/' . $template . '.php';
		} elseif ( file_exists( TEMPLATEPATH . '/' . $template . '.php' ) ) {
			return TEMPLATEPATH . '/' . $template . '.php';
		} else {
			return WP_CONTENT_DIR . "/plugins/" . plugin_basename( dirname( __FILE__ ) ) . '/templates/' . $template . '.php';
		}
	}

	public static function get_cart_info_from_session() {
		sequra_add_cart_info_to_session();

		return WC()->session->get( 'sequra_cart_info' );
	}

	/*
	 * Test if order is virtual.
	 *
	 * @param WC_Order $order
	 */
	public static function isFullyVirtual( WC_Cart $cart ) {
		return ! $cart::needs_shipping();
	}


	/*
	 * Test if order elgible for services
	 * It must have 1 and no more serivces
	 *
	 * @param WC_Order $order
	 */
	public function isElegibleForServiceSale() {
		$elegible       = false;
		$services_count = 0;
		foreach ( WC()->cart->cart_contents as $values ) {
			if ( self::validateServiceEndDate( get_post_meta( $values['data']->id, 'service_end_date', true ) ) ) {
				$services_count += $values['quantity'];
				$elegible       = $services_count == 1;
			}
		}

		return apply_filters( 'woocommerce_cart_is_elegible_for_service_sale', $elegible );
	}

	public static function validateServiceEndDate( $service_end_date ) {
		if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})/', $service_end_date, $parts ) ) {
			list( $service_end_date, $year, $month, $day ) = $parts;
			$service_end_time = strtotime( $service_end_date );
			if ( $service_end_time < time() ) {
				return false;
			}

			return date( 'Y-m-d', $service_end_time );
		} else if ( is_numeric( $service_end_date ) ) {
			return (int) $service_end_date;
		} else {
			return false;
		}

	}

}
