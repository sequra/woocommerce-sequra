<?php
/**
 * Unit tests for Paypal standard gateway request.
 *
 * @package WooCommerceSQ\Tests\Gateways\Sequra
 */
class WCSQ_Tests_SequraPayment_Gateway_BuilderWC extends WC_Unit_Test_Case {

    /**
     * Initialize the Paypal gateway and Request objects.
     */
    public function setUp() {
        parent::setUp();
        // Add API credentials.
        $settings = array(
            'merchantref'        => 'wcsq_tests',
            'user'               => 'wcsq_tests',
            'password'           => 'dHvxbkpZcNnX6uk36XOf4P51lnkSE4',
            'assets_secret'      => 'i_S5YcXdxZ',
            'enable_for_virtual' => 'no',
            'env'                => 1,
            'debug'              => 'yes'
        );
        update_option('woocommerce_sequra_settings ', $settings);
        $pm = new SequraInvoicePaymentGateway();
        $this->sequraHelper = new SequraHelper($pm);
    }

    /**
     * Test built totals match.
     *
     * @param string $request_url Paypal request URL.
     * @param bool   $testmode    Whether Paypal sandbox is used or not.
     */
    protected function check_totals( $order) {
        $totals = SequraBuilderWC::totals($order['cart']);
        $this->assertEquals( $totals['with_tax'], $order['cart']['order_total_with_tax'] );
    }

    /**
     * Test for request_url() method.
     *
     * @group timeout
     * @throws WC_Data_Exception
     */
    public function test_build() {
        $order = WC_Helper_Order::create_order();
        $res = $this->sequraHelper->getBuilder($order)->build();

        $this->check_totals($res);
        $this->assertNotEmpty($res['cart']['delivery_method']);
        $this->assertEquals('',$res['state']);
        $this->assertNotEmpty($res['platform']);
        $this->assertNotEmpty($res['merchant']);
        $this->assertNotEmpty($res['customer']);
        $this->assertNotEmpty($res['delivery_address']);
        $this->assertNotEmpty($res['invoice_address']);
        $this->assertNotEmpty($res['gui']);
    }
}