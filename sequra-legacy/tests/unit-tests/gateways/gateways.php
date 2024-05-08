<?php
/**
 * Unit tests for gateways.
 *
 * @package WooCommerceSequra\Tests\Gateways
 */
class WCSQ_Tests_Gateways extends WC_Unit_Test_Case {

	/**
	 * WooCommerce instance.
	 *
	 * @var \WooCommerce instance
	 */
	protected $wc;

	/**
	 * Setup test.
	 *
	 * @since 2.2
	 */
	public function setUp() {
		parent::setUp();
		$this->wc = WC();
	}

	/**
	 * Test for supports() method.
	 */
	public function test_supports() {
		$gateway = new WC_Mock_Payment_Gateway();

		$this->assertTrue( $gateway->supports( 'products' ) );
		$this->assertFalse( $gateway->supports( 'made-up-feature' ) );
	}

	/**
	 * Test for supports() method.
	 */
	public function test_can_refund_order() {
		$gateway = new WC_Mock_Payment_Gateway();
		$order   = WC_Helper_Order::create_order();

		$order->set_payment_method( 'mock' );
		$order->set_transaction_id( '12345' );
		$order->save();

		$this->assertFalse( $gateway->can_refund_order( $order ) );

		$gateway->supports[] = 'refunds';

		$this->assertTrue( $gateway->can_refund_order( $order ) );
	}

	/**
	 * Test for seQura supports() method.
	 */
	public function test_sequra_can_refund_order() {
		$order = WC_Helper_Order::create_order();

		$order->set_payment_method( 'sequra_i' );
		$order->set_transaction_id( '12345' );
		$order->save();

		// Add API credentials.
		$settings = array(
			'merchantref'        => 'wcsq_tests',
			'user'               => 'wcsq_tests',
			'password'           => 'dHvxbkpZcNnX6uk36XOf4P51lnkSE4',
			'assets_secret'      => 'i_S5YcXdxZ',
			'enable_for_virtual' => 'no',
			'env'                => 1,
			'debug'              => 'yes',
		);
		update_option( 'woocommerce_sequra_settings ', $settings );
		$gateway = new SequraInvoiceGateway();
		$this->assertFalse( $gateway->can_refund_order( $order ) );

		// And if it were pp...
		$order->set_payment_method( 'sequra_pp' );
		$order->save();
		$gateway = new SequraPartPaymentGateway();
		$this->assertFalse( $gateway->can_refund_order( $order ) );
	}
}

