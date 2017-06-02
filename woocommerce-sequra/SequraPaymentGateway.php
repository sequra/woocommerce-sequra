<?php

/**
 * Pasarela SeQura Gateway Class
 * */
class SequraPaymentGateway extends WC_Payment_Gateway {
	public static $endpoints = array(
		'https://live.sequrapi.com/orders',
		'https://sandbox.sequrapi.com/orders'
	);

	public function __construct() {
		do_action( 'woocommerce_sequra_before_load', $this );
		$this->id   = 'sequra';

		$this->method_title       = __( 'Configuración SeQura', 'wc_sequra' );
		$this->method_description = __( 'Configurtación para los métodos de pago SeQura', 'wc_sequra' );
		$this->supports           = array(
			'products'
		);

		// Load the form fields
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		$this->title = $this->method_title;
		$this->enabled = false;

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
			$this,
			'process_admin_options'
		) );
		do_action( 'woocommerce_sequra_loaded', $this );
	}

	/**
	 * Initialize Gateway Settings Form Fields
	 */
	function init_form_fields() {
		$shipping_methods = array();

		if ( is_admin() ) {
			foreach ( WC()->shipping->load_shipping_methods() as $method ) {
				$shipping_methods[ $method->id ] = $method->get_title();
			}
		}
		$this->form_fields = array(
			'merchantref'        => array(
				'title'       => __( 'SeQura Merchant Reference', 'wc_sequra' ),
				'type'        => 'text',
				'description' => __( 'Id de comerciante proporcionado por SeQura.', 'wc_sequra' ),
				'default'     => ''
			),
			'user'               => array(
				'title'       => __( 'SeQura Username', 'wc_sequra' ),
				'type'        => 'text',
				'description' => __( 'Usuario proporcionado por SeQura.', 'wc_sequra' ),
				'default'     => ''
			),
			'password'           => array(
				'title'       => __( 'Password', 'wc_sequra' ),
				'type'        => 'text',
				'description' => __( 'Password proporcionada por SeQura.', 'wc_sequra' ),
				'default'     => ''
			),
			'assets_secret'      => array(
				'title'       => __( 'Assets secret', 'wc_sequra' ),
				'type'        => 'text',
				'description' => __( 'Código proporcionada por SeQura.', 'wc_sequra' ),
				'default'     => ''
			),
			'enable_for_methods' => array(
				'title'             => __( 'Enable for shipping methods', 'wc_sequra' ),
				'type'              => 'multiselect',
				'class'             => 'chosen_select',
				'css'               => 'width: 450px;',
				'default'           => '',
				'description'       => __( 'If SeQura is only available for certain methods, set it up here. Leave blank to enable for all methods.', 'wc_sequra' ),
				'options'           => $shipping_methods,
				'desc_tip'          => true,
				'custom_attributes' => array(
					'data-placeholder' => __( 'Select shipping methods', 'wc_sequra' )
				)
			),
			'enable_for_virtual' => array(
				'title'   => __( 'Enable for virtual orders', 'woocommerce' ),
				'label'   => __( 'Enable SeQura for services', 'wc_sequra' ),
				'type'    => 'checkbox',
				'description' => __( 'Your contract must allow selling services, SeQura will be enabled only for virtual products that have a "Service end date" specified. Only one product can be purchased at a time', 'wc_sequra' ),
				'default' => 'no'
			),
			'env'                => array(
				'title'       => __( 'Entorno', 'wc_sequra' ),
				'type'        => 'select',
				'description' => __( 'While working in Sandbox the methods will only show to the following IP addresses.', 'wc_sequra' ),
				'default'     => '1',
				'desc_tip'    => true,
				'options'     => array(
					'1' => __( 'Sandbox - Pruebas', 'wc_sequra' ),
					'0' => __( 'Live - Real', 'wc_sequra' )
				)
			),
			'test_ips'           => array(
				'title'       => __( 'IPs for testing', 'wc_sequra' ),
				'label'       => '',
				'type'        => 'test',
				'description' => __( 'When working is sandbox mode only these ips addresses will see the plugin', 'wc_sequra' ),
				'desc_tip'    => true,
				'default'     => '54.76.175.81,' . $_SERVER['REMOTE_ADDR']
			),
			'debug'              => array(
				'title'       => __( 'Debugging', 'wc_sequra' ),
				'label'       => __( 'Modo debug', 'wc_sequra' ),
				'type'        => 'checkbox',
				'description' => __( 'Sólo para desarrolladores.', 'wc_sequra' ),
				'default'     => 'no'
			)
		);
		$this->form_fields = apply_filters( 'woocommerce_sequra_init_form_fields', $this->form_fields, $this );
	}

	/**
	 * Check If The Gateway Is Available For Use
	 *
	 * @return bool
	 */
	public function is_available() {
		return false;
	}

	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 * */
	public function admin_options() {
		?>
        <h3><?php _e( 'Configuración SeQura', 'wc_sequra' ); ?></h3>
        <p><?php _e( 'La pasarela <a href="https://sequra.es/">SeQura</a> para Woocommerce le permitirá configurar los métodos de pago disponibles con SeQura.', 'wc_sequra' ); ?></p>
        <table class="form-table">
			<?php $this->generate_settings_html(); ?>
        </table><!--/.form-table-->
		<?php
	}

}
