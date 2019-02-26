<?php
/**
 * Sequra Campaign Gateway.
 *
 * @package woocommerce-sequra
 */

/**
 * Pasarela Sequra Gateway Class
 * */
class SequraCampaignGateway extends WC_Payment_Gateway {
	/**
	 * Constructor
	 */
	public function __construct() {
		do_action( 'woocommerce_sequracampaign_before_load', $this );
		$this->id = 'sequracampaign';

		$this->method_title       = __( 'Campaña Sequra', 'wc_sequracampaign' );
		$this->method_description = __( 'Allows special campaign, service ofered by Sequra.', 'wc_sequracampaign' );
		$this->supports           = array(
			'products',
		);

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();
		$this->core_settings = get_option( 'woocommerce_sequra_settings', array() );

		// Get setting values.
		$this->enabled               = $this->settings['enabled'];
		$this->title                 = $this->settings['title'];
		$this->product               = 'pp5';// not an option.
		$this->campaign              = $this->settings['campaign'];
		$this->icon                  = sequra_get_script_basesurl() . 'images/badges/campaign.svg';
		$this->enable_for_countries  = array( 'ES' );
		$this->enable_for_currencies = array( 'EUR' );
		$this->has_fields            = true;
		$this->env                   = $this->core_settings['env'];
		$this->helper                = new SequraHelper( $this );
		// Logs.
		if ( 'yes' === $this->core_settings['debug'] ) {
			$this->log = new WC_Logger();
		}

		// Hooks.
		add_filter( 'wc_sequra_pumbaa_options', array( $this, 'pumbaa_options' ), 10, 3 );
		add_action( 'woocommerce_receipt_' . $this->id, array( $this->helper, 'receipt_page' ) );
		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array(
				$this,
				'process_admin_options',
			)
		);
		add_action( 'woocommerce_api_woocommerce_' . $this->id, array( $this->helper, 'check_response' ) );
		$json       = get_option( 'sequracampaign_conditions' );
		$conditions = json_decode( $json, true );
		if ( ! $conditions ) {
			$this->enabled = false;
		} else {
			foreach ( $conditions[ $this->product ] as $campaign ) {
				if ( $campaign['campaign'] === $this->campaign ) {
					$this->first_date = strtotime( $campaign['first_date'] );
					$this->last_date  = strtotime( $campaign['last_date'] );
					$this->fees_table = array_map(
						function ( $value ) {
							return array( $value[0] / 100, $value[1] / 100 );
						},
						$campaign['fees_table']
					);
					$this->max_amount = $campaign['max_amount'] / 100;
					$this->min_amount = $campaign['min_amount'] / 100;
					break;
				}
			}
		}
		do_action( 'woocommerce_sequracampaign_loaded', $this );
	}

	/**
	 * Initialize Gateway Settings Form Fields
	 */
	public function init_form_fields() {
		$shipping_methods = array();

		if ( is_admin() ) {
			foreach ( WC()->shipping->load_shipping_methods() as $method ) {
				$shipping_methods[ $method->id ] = $method->get_title();
			}
		}
		$this->form_fields = array(
			'enabled'      => array(
				'title'       => __( 'Enable/Disable', 'wc_sequracampaign' ),
				'type'        => 'checkbox',
				'description' => __( 'Enable sequra special campaign', 'wc_sequracampaign' ),
				'default'     => 'no',
			),
			'title'        => array(
				'title'       => __( 'Title', 'wc_sequracampaign' ),
				'type'        => 'text',
				'description' => __(
					'This controls the title which the user sees during checkout.',
					'wc_sequracampaign'
				),
				'default'     => __( 'Set campaign title.', 'wc_sequracampaign' ),
			),
			'campaign'     => array(
				'title'       => __( 'Campaign code', 'wc_sequracampaign' ),
				'type'        => 'text',
				'description' => __( 'Campaign code provided by Sequra.', 'wc_sequracampaign' ),
				'default'     => __( 'code', 'wc_sequracampaign' ),
			),
			'widget_theme' => array(
				'title'       => __( 'Widget theme', 'wc_sequra' ),
				'type'        => 'text',
				'description' => __( 'Widget visualization params', 'wc_sequra' ),
				'default'     => 'white',
			),
			'dest_css_sel'  => array(
				'title'       => __( 'CSS selector for teaser', 'wc_sequra' ),
				'type'        => 'text',
				'description' => __(
					'CSS after which the teaser will be draw. Leave #sequra_campaign_teaser to show it under add to cart button',
					'wc_sequra'
				),
				'default'     => '#sequra_campaign_teaser',
			),
		);
		$this->form_fields = apply_filters( 'woocommerce_sequracampaign_init_form_fields', $this->form_fields, $this );
	}

	/**
	 * Check If The Gateway Is Available For Use
	 *
	 * @return bool
	 */
	public function is_available() {
		if ( 'yes' !== $this->enabled ) {
			return false;
		} elseif ( is_admin() ) {
			return true;
		}
		if ( (
				get_the_ID() === wc_get_page_id( 'checkout' ) ||
				( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] )
			) &&
			! $this->is_available_in_checkout() ) {
			return false;
		}

		if ( is_product() && ! $this->is_available_in_product_page() ) {
			return false;
		}

		if ( WC()->cart && 0 < $this->get_order_total() && $this->min_amount > $this->get_order_total() ) {
			return false;
		}

		return $this->helper->is_available_for_country() &&
				$this->helper->is_available_for_currency() &&
				$this->helper->is_available_for_ip();
	}
	/**
	 * Undocumented function
	 *
	 * @return boolean
	 */
	public function is_available_in_checkout() {
		return WC()->cart &&
			WC()->cart->needs_shipping() &&
			$this->is_campaign_period() &&
			// Campaign not available for services.
			'no' === $this->core_settings['enable_for_virtual'];
	}
	/**
	 * Is campaign active now
	 *
	 * @return boolean
	 */
	private function is_campaign_period() {
		if ( isset( $_GET['sequra_campaign_preview'] ) && $_GET['sequra_campaign_preview'] === $this->campaign ) {
			return true;
		}

		return time() < $this->last_date && time() > $this->first_date;
	}
	/**
	 * Undocumented function
	 *
	 * @return boolean
	 */
	public function is_available_in_product_page() {
		$product = $GLOBALS['product'];
		if (
			! $this->is_campaign_period() ||
			$this->min_amount > $product->price
		) {
			return false;
		}
		if ( 'yes' === $this->core_settings['enable_for_virtual'] ) {
			return false;
		} elseif ( ! $product->needs_shipping() ) {
			return false;
		}
		return true;
	}

	/**
	 * There might be payment fields for Sequra, and we want to show the description if set.
	 * */
	public function payment_fields() {
		require self::template_loader( 'campaign-fields' );
	}
	/**
	 * Load template file
	 *
	 * @param string $template template name.
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
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 * */
	public function admin_options() {
		?>
		<h3><?php esc_html_e( 'Campañas Sequra', 'wc_sequracampaign' ); ?></h3>
		<p><?php esc_html_e( 'Permite ofrecer campañas especiales de Sequra', 'wc_sequracampaign' ); ?></p>
		<table class="form-table">
			<?php $this->generate_settings_html(); ?>
		</table><!--/.form-table-->
		<?php
	}
	/**
	 * Proccess payment
	 *
	 * @param int $order_id order id.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = new WC_Order( $order_id );
		do_action( 'woocommerce_sequracampaign_process_payment', $order, $this );
		$ret = array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true ),
		);

		return apply_filters( 'woocommerce_sequracampaign_process_payment_return', $ret, $this );
	}
	/**
	 * Undocumented function
	 *
	 * @param array $products available products.
	 * @return array
	 */
	public static function available_products( $products ) {
		$products[] = 'pp5';

		return $products;
	}
	/**
	 * Set missing options if needed
	 *
	 * @param array              $options    Already set options.
	 * @param WC_Order           $order      Current order.
	 * @param WC_Payment_Gateway $pm Current Payment method.
	 * @return array
	 */
	public function pumbaa_options( $options, $order, $pm ) {
		if ( $this->id === $pm->id && $this->campaign ) {
			$options['campaign'] = $this->campaign;
		}
		return $options;
	}
}

add_filter( 'sequra_available_products', array( 'SequraCampaignGateway', 'available_products' ) );
