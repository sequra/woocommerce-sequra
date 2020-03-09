<?php
/**
 * Unit tests for Paypal standard gateway request.
 *
 * @package WooCommerceSQ\Tests\Gateways\Sequra
 */
class WCSQ_Tests_SequraBuilderWC extends SQ_Unit_Test_Case {

	/**
	 * Initialize the SeQura gateway and Request objects.
	 */
	public function setUp() {
		parent::setUp();
		$pm                  = new SequraInvoiceGateway();
		$this->sequra_helper = new SequraHelper( $pm );
	}

	/**
	 * Test built totals match.
	 *
	 * @param string $request_url Paypal request URL.
	 * @param bool   $testmode    Whether Paypal sandbox is used or not.
	 */
	protected function check_totals( $order ) {
		$totals = \Sequra\PhpClient\Helper::totals( $order['cart'] );
		$this->assertEquals( $totals['with_tax'], $order['cart']['order_total_with_tax'] );
	}

	/**
	 * Test for request_url() method.
	 *
	 * @group timeout
	 */
	public function test_build() {
		$order = WC_Helper_Order::create_order();
		$res   = $this->sequra_helper->get_builder( $order )->build();

		$this->check_totals( $res );
		$this->assertNotEmpty( $res['cart']['delivery_method'] );
		$this->assertEquals( '', $res['state'] );
		$this->assertNotEmpty( $res['platform'] );
		$this->assertNotEmpty( $res['merchant'] );
		$this->assertNotEmpty( $res['customer'] );
		$this->assertNotEmpty( $res['delivery_address'] );
		$this->assertNotEmpty( $res['invoice_address'] );
		$this->assertNotEmpty( $res['gui'] );
	}
}
