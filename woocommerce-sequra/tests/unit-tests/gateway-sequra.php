<?php

/**
 * Unit tests for gateways.
 *
 * @package WooCommerceSequra\Tests\Gateways
 */
class WCSQ_Tests_GatewaySequra extends SQ_Unit_Test_Case {

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
		$this->override_paths = [
			get_template_directory() .'/',
			get_stylesheet_directory() . '/',
			get_template_directory() . '/' . WC_TEMPLATE_PATH,
			get_stylesheet_directory() . '/' . WC_TEMPLATE_PATH,
		];
	}

	/**
	 * Test for sequra_activation() method.
	 */
	public function test_sequra_activation() {
		sequra_activation();
		$display_order = get_option( 'woocommerce_gateway_order' );
		$this->assertEquals( $display_order['sequra_i'], 0 );
		$this->assertEquals( $display_order['sequra_pp'], 1 );
		$this->assertEquals( $display_order['sequra'], 2 );
	}

	/**
	 * Test for sequra_activation() method.
	 */
	public function test_sequra_banner() {
		// No banner if not spain, euro and IP.
		$this->assertTrue( ! sequra_banner( [ 'product' => 'i1' ] ) );

		// Force availability.
		add_filter( 'woocommerce_sequra_i_is_available', [ $this , 'forceTrue' ] );
		add_filter( 'woocommerce_sequra_pp_is_available', [ $this , 'forceTrue' ] );
		// Clear overrides.
		$this->clear_overrides( 'banner-pp3.php' );
		foreach ( [ 'i1', 'pp3', 'pp6', 'pp9' ] as $sq_prod ) {
			$this->assertRegExp( '<!-- //BOF banner-' . $sq_prod . ' -->', sequra_banner( [ 'product' => $sq_prod ] ) );
		}

		// Test overrides work.
		foreach ( $this->override_paths as $key => $path ) {
			if( !file_exists($path) ) {
				mkdir( $path );
			}
			file_put_contents( $path . 'banner-pp3.php', 'test'.$key );
			$this->assertEquals( sequra_banner( [ 'product' => 'pp3' ] ), 'test'.$key );
		}
	}
	/**
	 * Undocumented function
	 *
	 * @param [type] $anything
	 * @return void
	 */
	static function forceTrue( $anything ) {
		return true;
	}
	/**
	 * Undocumented function
	 *
	 * @param string $filename
	 * @return void
	 */
	private function clear_overrides( $filename ) {
		foreach ( $this->override_paths as $path ){
			$full_path = $path . $filename;
			if ( file_exists( $full_path ) ) {
				unlink( $full_path );
			}
		}
	}
}
