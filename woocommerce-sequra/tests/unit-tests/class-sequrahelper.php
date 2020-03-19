<?php

/**
 * Unit tests for gateways.
 *
 * @package WooCommerceSequra\Tests\Gateways
 */
class WCSQ_Tests_SequraHelper extends SQ_Unit_Test_Case {

	/**
	 * WooCommerce instance.
	 *
	 * @var \WooCommerce instance
	 */
	protected $wc;

	/**
	 * List of path to look overrides for.
	 *
	 * @var string
	 */
	protected $override_paths = [];

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
	 * Test for sequra_activation() method.
	 */
	public function test_get_cart_info_from_session() {
		$helper                = new SequraHelper( new SequraInvoiceGateway() );
		$cart_info = $helper->get_cart_info_from_session();
		$this->assertNotFalse( $cart_info['ref'] );
		$this->assertNotFalse( $cart_info['created_at'] );
	}

	/**
	 * Test for sequra_test_is_fully_virtual() method.
	 */
	public function test_is_fully_virtual(){
		$this->wc->cart->add_to_cart(
			SQ_Helper_Product::create_simple_virtual_product(),
			1
		);
		$this->assertTrue(
			SequraHelper::is_fully_virtual( $this->wc->cart )
		);
		$this->wc->cart->add_to_cart(
			SQ_Helper_Product::create_simple_product(),
			1
		);
		$this->assertTrue(
			SequraHelper::is_fully_virtual( $this->wc->cart )
		);
	}


}
