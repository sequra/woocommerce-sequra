<?php
/**
 * Helper class from config form.
 *
 * @package woocommerce-sequra
 */

/**
 * SequraConfigFormFields class
 */
class SequraConfigFormFields {

	/**
	 * SequraPaymentGateway variable
	 *
	 * @var SequraPaymentGateway
	 */
	protected $pm;

	/**
	 * Initialize Gateway Settings Form Fields
	 *
	 * @param SequraPaymentGateway $pm the gateway.
	 */
	public function __construct( &$pm ) {
		$this->pm = $pm;
	}

	/**
	 * Add form fields
	 *
	 * @return void 
	 */
	public function add_form_fields() {
		$this->pm->form_fields = array(
			'enabled'                  => array(
				'title'       => __( 'Enable/Disable', 'sequra' ),
				'type'        => 'checkbox',
				'description' => __( 'Enable seQura payments', 'sequra' ),
				'default'     => 'no',
			),
			'title'                    => array(
				'title'       => __( 'Title', 'sequra' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'sequra' ),
				'default'     => __( 'Flexible payment with seQura', 'sequra' ),
			),
			'sign-up-info'             => array(
				'title'       => __( 'Credentials:', 'sequra' ),
				'type'        => 'title',
				'description' => sprintf(
					// translators: %s: URL to seQura signup page.
					__( 'Following information should be provided by seQura, you can sign-up <a href="%s">here</a> to get it.', 'sequra' ),
					SEQURA_SIGNUP_URL
				),
			),
			'merchantref'              => array(
				'title'   => __( 'seQura Merchant Reference', 'sequra' ),
				'type'    => 'text',
				'default' => '',
			),
			'user'                     => array(
				'title'   => __( 'seQura Username', 'sequra' ),
				'type'    => 'text',
				'default' => '',
				'css'     => 'color:' . ( $this->pm->is_valid_auth ? 'green' : 'red' ) . ';width: 450px;',
			),
			'password'                 => array(
				'title'   => __( 'Password', 'sequra' ),
				'type'    => 'text',
				'default' => '',
			),
			'assets_secret'            => array(
				'title'   => __( 'Assets secret', 'sequra' ),
				'type'    => 'text',
				'default' => '',
			),
			'enable_for_virtual'       => array(
				'title'       => __( 'Enable for virtual orders', 'sequra' ),
				'label'       => __( 'Enable seQura for services', 'sequra' ),
				'type'        => 'checkbox',
				'description' => __( 'Your contract must allow selling services, seQura will be enabled only for virtual products that have a "Service end date" specified. Only one product can be purchased at a time', 'sequra' ),
				'default'     => 'no',
			),
			'default_service_end_date' => array(
				'title'             => __( 'Default service end date', 'sequra' ),
				'desc_tip'          => true,
				'type'              => 'text',
				'description'       => __( 'Dates as 2017-08-31, time ranges as P3M15D (3 months and 15 days). It applies by default to all product unless a different value is set at the product settings page.', 'sequra' ),
				'default'           => 'P1Y',
				'placeholder'       => __( 'ISO8601 format', 'sequra' ),
				'custom_attributes' => array(
					'pattern'   => SequraHelper::ISO8601_PATTERN,
					'dependson' => 'enable_for_virtual',
				),
			),
			'allow_payment_delay'      => array(
				'title'             => __( 'Allow first payment delay', 'sequra' ),
				'desc_tip'          => true,
				'type'              => 'checkbox',
				'description'       => __( 'Do not enable except by indication of seQura.', 'sequra' ),
				'default'           => 'no',
				'custom_attributes' => array(
					'dependson' => 'enable_for_virtual',
				),
			),
			'allow_registration_items' => array(
				'title'             => __( 'Allow registration items', 'sequra' ),
				'desc_tip'          => true,
				'type'              => 'checkbox',
				'description'       => __( 'Allows configuring part of the product price to be paid in advance as registration', 'sequra' ) . __( 'Do not enable except by indication of seQura.', 'sequra' ),
				'default'           => 'no',
				'custom_attributes' => array(
					'dependson' => 'enable_for_virtual',
				),
			),
			'env'                      => array(
				'title'       => __( 'Environment', 'sequra' ),
				'type'        => 'select',
				'description' => __( 'While working in Sandbox the methods will only show to the following IP addresses.', 'sequra' ),
				'default'     => '1',
				'desc_tip'    => true,
				'options'     => array(
					'1' => __( 'Sandbox', 'sequra' ),
					'0' => __( 'Live', 'sequra' ),
				),
			),
			// phpcs:disable WordPressVIPMinimum.Variables.ServerVariables.UserControlledHeaders, WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__REMOTE_ADDR__
			'test_ips'                 => array(
				'title'       => __( 'IPs for testing', 'sequra' ),
				'label'       => '',
				'type'        => 'test',
				'description' => sprintf(
					// translators: %s: IP address.
					__( 'When working is sandbox mode only these ips addresses will see the plugin. Current IP: %s', 'sequra' ),
					isset( $_SERVER['REMOTE_ADDR'] ) ?
					esc_html( sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) ) :
					''
				),
				'desc_tip'    => false,
				'default'     => gethostbyname( 'proxy-es.dev.sequra.es' ) .
				( isset( $_SERVER['REMOTE_ADDR'] ) ?
					',' . sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) :
					''
				),
			),
			// phpcs:enable WordPressVIPMinimum.Variables.ServerVariables.UserControlledHeaders, WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__REMOTE_ADDR__
			'debug'                    => array(
				'title'       => __( 'Debugging', 'sequra' ),
				'label'       => __( 'Debug mode', 'sequra' ),
				'type'        => 'checkbox',
				'description' => __( 'Only for developers.', 'sequra' ),
				'default'     => 'no',
			),
		);
		$this->add_active_methods_info();
		$this->init_communication_form_fields();
		/**
		 * Filter to add custom settings
		 * 
		 * @since 2.0.0
		 */
		$this->pm->form_fields = apply_filters( 'woocommerce_sequra_init_form_fields', $this->pm->form_fields, $this );
	}

	/**
	 * Initialize Gateway Settings Form Fields
	 */
	private function add_active_methods_info() {
		$this->pm->form_fields['active_methods_info'] = array(
			'title'       => __( 'Active payent methods', 'sequra' ),
			'type'        => 'title',
			/* translators: %s: URL */
			'description' => __( 'Information will be available once the credentials are set and correct', 'sequra' ),
		);
		if ( $this->pm->is_valid_auth ) {
			$this->pm->form_fields['active_methods_info']['description'] =
				'<ul><li>' . implode(
					'</li><li>',
					$this->pm->get_remote_config()->get_merchant_active_payment_products( 'title' )
				) . '</li></ul>';
		}
	}
	/**
	 * Initialize Gateway Settings Form Fields
	 */
	private function init_communication_form_fields() {
		$this->pm->form_fields['communication_fields'] = array(
			'title'       => __( 'Comunication configuration', 'sequra' ),
			'type'        => 'title',
			/* translators: %s: URL */
			'description' => '',
		);
		$this->pm->form_fields['price_css_sel']        = array(
			'title'       => __( 'CSS price selector', 'sequra' ),
			'type'        => 'text',
			'description' => __( 'CSS selector to get the price for widgets in products', 'sequra' ),
			'default'     => '.summary .price>.amount,.summary .price ins .amount',
		);
		$methods                                       = $this->pm->get_remote_config()->get_merchant_payment_methods();
		array_walk(
			$methods,
			array( $this, 'init_communication_form_fields_for_method' )
		);
	}

	/**
	 * Initialize Gateway Settings Form Fields for each method
	 *
	 * @param array $method payment method.
	 */
	private function init_communication_form_fields_for_method( $method ) {
		switch ( SequraRemoteConfig::get_family_for( $method ) ) {
			case 'INVOICE':
				$this->fields_for_invoice( $method );
				break;
			case 'PARTPAYMENT':
				$this->fields_for_partpayment( $method );
				break;
		}
	}

	/**
	 * Initialize Gateway Settings Form Fields for each method
	 *
	 * @param array $method payment method.
	 */
	private function fields_for_partpayment( $method ) {
		$product = $this->pm->get_remote_config()->build_unique_product_code( $method );
		$this->pm->form_fields[ 'partpayment_config_' . $product ] = array(
			'title' => sprintf(
				// translators: %s: payment method title.
				__( 'Simulator config for %s', 'sequra' ),
				$method['title']
			),
			'type'  => 'title',
		);
		$this->pm->form_fields[ 'enabled_in_product_' . $product ] = array(
			'title'       => __( 'Show in product page', 'sequra' ),
			'type'        => 'checkbox',
			'description' => __( 'Mostrar widget en la página del producto', 'sequra' ),
			'default'     => 'yes',
		);
		$this->pm->form_fields[ 'dest_css_sel_' . $product ]       = array(
			'title'             => __( 'CSS selector for widget in product page', 'sequra' ),
			'type'              => 'text',
			'description'       => __(
				'CSS after which the simulator will be drawn.',
				'sequra'
			),
			'default'           => '.summary .price',
			'custom_attributes' => array(
				'dependson' => 'enabled_in_product_' . $product,
			),
		);
		$this->pm->form_fields[ 'widget_theme_' . $product ]       = array(
			'title'       => __( 'Simulator params', 'sequra' ),
			'type'        => 'text',
			'description' => __( 'Widget visualization params', 'sequra' ),
			'default'     => 'L',
			'custom_attributes' => array(
				'dependson' => 'enabled_in_product_' . $product,
			),
		);
	}
	/**
	 * Initialize Gateway Settings Form Fields for each method
	 *
	 * @param array $method payment method.
	 */
	private function fields_for_invoice( $method ) {
		$product = $this->pm->get_remote_config()->build_unique_product_code( $method );

		$this->pm->form_fields[ 'invoice_config_' . $product ]     = array(
			'title' => sprintf(
				// translators: %s: payment method title.
				__( 'Teaser config for %s', 'sequra' ),
				$method['title']
			),
			'type'  => 'title',
		);
		$this->pm->form_fields[ 'enabled_in_product_' . $product ] = array(
			'title'       => __( 'Show in product page', 'sequra' ),
			'type'        => 'checkbox',
			'description' => __( 'Mostrar widget en la página del producto', 'sequra' ),
			'default'     => 'yes',
		);
		$this->pm->form_fields[ 'dest_css_sel_' . $product ]       = array(
			'title'             => __( 'CSS selector for widget in product page', 'sequra' ),
			'type'              => 'text',
			'description'       => __(
				'CSS after which the simulator will be drawn.',
				'sequra'
			),
			'default'           => '.single_add_to_cart_button, .woocommerce-variation-add-to-cart',
			'custom_attributes' => array(
				'dependson' => 'enabled_in_product_' . $product,
			),
		);
		$this->pm->form_fields[ 'widget_theme_' . $product ]       = array(
			'title'       => __( 'Teaser params', 'sequra' ),
			'type'        => 'text',
			'description' => __( 'Teaser visualization params', 'sequra' ),
			'default'     => 'L',
		);
	}
}
