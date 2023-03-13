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
	 * @return void 
	 */
	public function add_form_fields() {
		$this->pm->form_fields = array(
			'enabled'                  => array(
				'title'       => __( 'Enable/Disable', 'wc_sequra' ),
				'type'        => 'checkbox',
				'description' => __( 'Enable SeQura payments', 'wc_sequra' ),
				'default'     => 'no',
			),
			'title'                    => array(
				'title'       => __( 'Title', 'wc_sequra' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'wc_sequra' ),
				'default'     => __( 'Fraccionar pago', 'wc_sequra' ),
			),
			'merchantref'              => array(
				'title'       => __( 'SeQura Merchant Reference', 'wc_sequra' ),
				'type'        => 'text',
				'description' => __( 'Id de comerciante proporcionado por SeQura.', 'wc_sequra' ),
				'default'     => '',
			),
			'user'                     => array(
				'title'       => __( 'SeQura Username', 'wc_sequra' ),
				'type'        => 'text',
				'description' => __( 'Usuario proporcionado por SeQura.', 'wc_sequra' ),
				'default'     => '',
				'css'		  => 'color:' . $this->pm->is_valid_auth?'green':'red'. ';width: 450px;',
			),
			'password'                 => array(
				'title'       => __( 'Password', 'wc_sequra' ),
				'type'        => 'text',
				'description' => __( 'Password proporcionada por SeQura.', 'wc_sequra' ),
				'default'     => '',
			),
			'assets_secret'            => array(
				'title'       => __( 'Assets secret', 'wc_sequra' ),
				'type'        => 'text',
				'description' => __( 'Código proporcionada por SeQura.', 'wc_sequra' ),
				'default'     => '',
			),
			'enable_for_virtual'       => array(
				'title'       => __( 'Enable for virtual orders', 'wc_sequra' ),
				'label'       => __( 'Enable SeQura for services', 'wc_sequra' ),
				'type'        => 'checkbox',
				'description' => __( 'Your contract must allow selling services, SeQura will be enabled only for virtual products that have a "Service end date" specified. Only one product can be purchased at a time', 'wc_sequra' ),
				'default'     => 'no',
			),
			'default_service_end_date' => array(
				'title'             => __( 'Default service end date', 'wc_sequra' ),
				'desc_tip'          => true,
				'type'              => 'text',
				'description'       => __( 'Fecha como 2017-08-31, plazo como P3M15D (3 meses y 15 días). Se aplicará por defecto a todos los productos si no se especifica algo diferente en la ficha de producto.', 'wc_sequra' ),
				'default'           => 'P1Y',
				'placeholder'       => __( 'ISO8601 format', 'wc_sequra' ),
				'custom_attributes' => array(
					'pattern'   => SequraHelper::ISO8601_PATTERN,
					'dependson' => 'enable_for_virtual',
				),
			),
			'allow_payment_delay'      => array(
				'title'             => __( 'Allow first payment delay', 'wc_sequra' ),
				'desc_tip'          => true,
				'type'              => 'checkbox',
				'description'       => __( 'Pago primera cuota diferido. No habilitar si no está indicado por SeQura', 'wc_sequra' ),
				'default'           => 'no',
				'custom_attributes' => array(
					'dependson' => 'enable_for_virtual',
				),
			),
			'allow_registration_items' => array(
				'title'             => __( 'Allow registration items', 'wc_sequra' ),
				'desc_tip'          => true,
				'type'              => 'checkbox',
				'description'       => __( 'Permitir configurar parte del pago por adelantado. No habilitar si no está indicado por SeQura', 'wc_sequra' ),
				'default'           => 'no',
				'custom_attributes' => array(
					'dependson' => 'enable_for_virtual',
				),
			),
			'env'                      => array(
				'title'       => __( 'Environment', 'wc_sequra' ),
				'type'        => 'select',
				'description' => __( 'While working in Sandbox the methods will only show to the following IP addresses.', 'wc_sequra' ),
				'default'     => '1',
				'desc_tip'    => true,
				'options'     => array(
					'1' => __( 'Sandbox', 'wc_sequra' ),
					'0' => __( 'Live', 'wc_sequra' ),
				),
			),
			'test_ips'                 => array(
				'title'       => __( 'IPs for testing', 'wc_sequra' ),
				'label'       => '',
				'type'        => 'test',
				'description' => sprintf(
					__( 'When working is sandbox mode only these ips addresses will see the plugin. Current IP: %s', 'wc_sequra' ),
					isset( $_SERVER['REMOTE_ADDR'] ) ?
					esc_html( sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) ) :
					''
				),
				'desc_tip'    => false,
				'default'     => gethostbyname( 'proxy-es.dev.sequra.es' ) .
					(
						isset( $_SERVER['REMOTE_ADDR'] ) ?
							',' . sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) :
							''
					),
			),
			'debug'                    => array(
				'title'       => __( 'Debugging', 'wc_sequra' ),
				'label'       => __( 'Modo debug', 'wc_sequra' ),
				'type'        => 'checkbox',
				'description' => __( 'Sólo para desarrolladores.', 'wc_sequra' ),
				'default'     => 'no',
			),
		);
		$this->add_active_methods_info();
		$this->init_communication_form_fields();
		$this->pm->form_fields = apply_filters( 'woocommerce_sequra_init_form_fields', $this->pm->form_fields, $this );
	}

	/**
	 * Initialize Gateway Settings Form Fields
	 */
	private function add_active_methods_info() {
		$this->pm->form_fields['active_methods_info'] = array(
			'title'       => __( 'Active payent methods', 'wc_sequra' ),
			'type'        => 'title',
			/* translators: %s: URL */
			'description' => __( 'Information will be available once the credentials are set and correct', 'wc_sequra' ),
		);
		if( $this->pm->is_valid_auth ) {
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
			'title'       => __( 'Comunication configuration', 'wc_sequra' ),
			'type'        => 'title',
			/* translators: %s: URL */
			'description' => '',
		);
		$this->pm->form_fields['price_css_sel']        = array(
			'title'       => __( 'CSS price selector', 'wc_sequra' ),
			'type'        => 'text',
			'description' => __( 'CSS selector to get the price for widgets in products', 'wc_sequra' ),
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
				__( 'Simulator config for %s' , 'wc_sequra' ),
				$method['title']
			),
			'type'  => 'title',
		);
		$this->pm->form_fields[ 'enabled_in_product_' . $product ] = array(
			'title'       => __( 'Show in product page', 'wc_sequra' ),
			'type'        => 'checkbox',
			'description' => __( 'Mostrar widget en la página del producto', 'wc_sequra' ),
			'default'     => 'yes',
		);
		$this->pm->form_fields[ 'dest_css_sel_' . $product ]       = array(
			'title'       => __( 'CSS selector for widget in product page', 'wc_sequra' ),
			'type'        => 'text',
			'description' => __(
				'CSS after which the simulator will be drawn.',
				'wc_sequra'
			),
			'default'     => '.summary .price',
			'custom_attributes' => array(
				'dependson' => 'enabled_in_product_' . $product,
			),
		);
		$this->pm->form_fields[ 'widget_theme_' . $product ]       = array(
			'title'       => __( 'Simulator params', 'wc_sequra' ),
			'type'        => 'text',
			'description' => __( 'Widget visualization params', 'wc_sequra' ),
			'default'     => 'L',
		);
	}
	/**
	 * Initialize Gateway Settings Form Fields for each method
	 *
	 * @param array $method payment method.
	 */
	private function fields_for_invoice( $method ) {
		$product = $this->pm->get_remote_config()->build_unique_product_code($method);

		$this->pm->form_fields[ 'invoice_config_' . $product ] = array(
			'title' => sprintf(
				__( 'Teaser config for %s' , 'wc_sequra' ),
				$method['title']
			),
			'type'  => 'title',
		);
		$this->pm->form_fields[ 'enabled_in_product_' . $product ] = array(
			'title'       => __( 'Show in product page', 'wc_sequra' ),
			'type'        => 'checkbox',
			'description' => __( 'Mostrar widget en la página del producto', 'wc_sequra' ),
			'default'     => 'yes',
		);
		$this->pm->form_fields[ 'dest_css_sel_' . $product ] = array(
			'title'       => __( 'CSS selector for widget in product page', 'wc_sequra' ),
			'type'        => 'text',
			'description' => __(
				'CSS after which the simulator will be drawn.',
				'wc_sequra'
			),
			'default'     => '.single_add_to_cart_button, .woocommerce-variation-add-to-cart',
			'custom_attributes' => array(
				'dependson' => 'enabled_in_product_' . $product,
			),
		);
		$this->pm->form_fields[ 'widget_theme_' . $product ]       = array(
			'title'       => __( 'Teaser params', 'wc_sequra' ),
			'type'        => 'text',
			'description' => __( 'Teaser visualization params', 'wc_sequra' ),
			'default'     => 'L',
		);
	}
}
