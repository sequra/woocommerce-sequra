<?php
/**
 * Tests for the Payment_Method_Service class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Services\Payment;

use SeQura\Core\BusinessLogic\Domain\Multistore\StoreContext;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\Order\Builders\Interface_Create_Order_Request_Builder;
use SeQura\WC\Services\Log\Interface_Logger_Service;
use SeQura\WC\Services\Order\Interface_Current_Order_Provider;
use SeQura\WC\Services\Order\Interface_Order_Service;
use SeQura\WC\Services\Payment\Payment_Method_Service;
use WP_UnitTestCase;

class PaymentMethodServiceTest extends WP_UnitTestCase {

	private $service;

	public function set_up(): void {
		$this->service = new Payment_Method_Service(
			$this->createMock( Interface_Create_Order_Request_Builder::class ),
			$this->createMock( Interface_Order_Service::class ),
			$this->createMock( Interface_Logger_Service::class ),
			$this->createMock( Interface_Current_Order_Provider::class ),
			$this->createMock( StoreContext::class )
		);
	}

	public function testIsDelegatedPaymentSelection_filterAbsent_returnsFalse(): void {
		$this->assertFalse( $this->service->is_delegated_payment_selection() );
	}

	public function testIsDelegatedPaymentSelection_filterReturnsTrue_returnsTrue(): void {
		add_filter( 'sequra_delegate_payment_method_selection', '__return_true' );
		$this->assertTrue( $this->service->is_delegated_payment_selection() );
		remove_filter( 'sequra_delegate_payment_method_selection', '__return_true' );
	}

	public function testIsDelegatedPaymentSelection_filterReturnsFalsy_returnsFalse(): void {
		add_filter( 'sequra_delegate_payment_method_selection', '__return_false' );
		$this->assertFalse( $this->service->is_delegated_payment_selection() );
		remove_filter( 'sequra_delegate_payment_method_selection', '__return_false' );
	}

	public function testIsDelegatedPaymentSelection_passesOrderToFilter(): void {
		$received_order = 'not-set';
		add_filter(
			'sequra_delegate_payment_method_selection',
			function ( $enabled, $order ) use ( &$received_order ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				$received_order = $order;
				return $enabled;
			},
			10,
			2
		);

		$this->service->is_delegated_payment_selection( null );
		$this->assertNull( $received_order );

		remove_all_filters( 'sequra_delegate_payment_method_selection' );
	}
}
