<?php
/**
 * Tests for Create_Order_Request_Builder country resolution.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Core\Implementation\BusinessLogic\Domain\Order\Builders;

use SeQura\Core\BusinessLogic\Domain\Connection\Services\CredentialsService;
use SeQura\Core\BusinessLogic\Domain\Order\Builders\MerchantOrderRequestBuilder;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Address;
use SeQura\Core\BusinessLogic\Domain\Order\Models\SeQuraOrder;
use SeQura\Core\BusinessLogic\Domain\Order\RepositoryContracts\SeQuraOrderRepositoryInterface;
use SeQura\WC\Core\Implementation\BusinessLogic\Domain\Order\Builders\Create_Order_Request_Builder;
use SeQura\WC\Services\Cart\Interface_Cart_Service;
use SeQura\WC\Services\I18n\Interface_I18n;
use SeQura\WC\Services\Log\Interface_Logger_Service;
use SeQura\WC\Services\Order\Builder\Interface_Order_Address_Builder;
use SeQura\WC\Services\Order\Builder\Interface_Order_Customer_Builder;
use SeQura\WC\Services\Order\Builder\Interface_Order_Delivery_Method_Builder;
use SeQura\WC\Services\Order\Interface_Current_Order_Provider;
use SeQura\WC\Services\Order\Interface_Order_Service;
use SeQura\WC\Services\Platform\Platform_Provider;
use SeQura\WC\Services\Product\Interface_Product_Service;
use SeQura\WC\Services\Shopper\Interface_Shopper_Service;
use WP_UnitTestCase;

class CreateOrderRequestBuilderTest extends WP_UnitTestCase {

	private $shopper_service;
	private $sequra_order_repository;
	private $i18n;
	private $current_order_provider;

	/** @var Create_Order_Request_Builder */
	private $builder;

	public function set_up(): void {
		$this->shopper_service         = $this->createMock( Interface_Shopper_Service::class );
		$this->sequra_order_repository = $this->createMock( SeQuraOrderRepositoryInterface::class );
		$this->i18n                    = $this->createMock( Interface_I18n::class );
		$this->current_order_provider  = $this->createMock( Interface_Current_Order_Provider::class );

		$this->builder = new Create_Order_Request_Builder(
			$this->createMock( Interface_Cart_Service::class ),
			$this->createMock( Platform_Provider::class ),
			$this->createMock( Interface_Product_Service::class ),
			$this->createMock( Interface_Order_Service::class ),
			$this->i18n,
			$this->shopper_service,
			$this->createMock( Interface_Logger_Service::class ),
			$this->current_order_provider,
			$this->createMock( MerchantOrderRequestBuilder::class ),
			$this->createMock( Interface_Order_Delivery_Method_Builder::class ),
			$this->createMock( Interface_Order_Address_Builder::class ),
			$this->createMock( Interface_Order_Customer_Builder::class ),
			$this->createMock( CredentialsService::class ),
			$this->sequra_order_repository
		);
	}

	public function testGetCountry_liveCountryAvailable_returnsIt(): void {
		$this->shopper_service->method( 'get_country' )->willReturn( 'PT' );

		$this->assertSame( 'PT', $this->builder->get_country() );
	}

	public function testGetCountry_noLiveCountry_recoversFromStoredOrderDelivery(): void {
		$this->shopper_service->method( 'get_country' )->willReturn( '' );

		$delivery = $this->createMock( Address::class );
		$delivery->method( 'getCountryCode' )->willReturn( 'IT' );
		$sequra_order = $this->createMock( SeQuraOrder::class );
		$sequra_order->method( 'getDeliveryAddress' )->willReturn( $delivery );
		$this->sequra_order_repository->method( 'getByCartId' )->willReturn( $sequra_order );

		$this->assertSame( 'IT', $this->builder->get_country( 'cart-ref' ) );
	}

	public function testGetCountry_storedOrderDeliveryEmpty_usesInvoiceCountry(): void {
		$this->shopper_service->method( 'get_country' )->willReturn( '' );

		$delivery = $this->createMock( Address::class );
		$delivery->method( 'getCountryCode' )->willReturn( '' );
		$invoice = $this->createMock( Address::class );
		$invoice->method( 'getCountryCode' )->willReturn( 'FR' );
		$sequra_order = $this->createMock( SeQuraOrder::class );
		$sequra_order->method( 'getDeliveryAddress' )->willReturn( $delivery );
		$sequra_order->method( 'getInvoiceAddress' )->willReturn( $invoice );
		$this->sequra_order_repository->method( 'getByCartId' )->willReturn( $sequra_order );

		$this->assertSame( 'FR', $this->builder->get_country( 'cart-ref' ) );
	}

	public function testGetCountry_noLiveNoStored_fallsBackToLocaleUppercased(): void {
		$this->shopper_service->method( 'get_country' )->willReturn( '' );
		$this->sequra_order_repository->method( 'getByCartId' )->willReturn( null );
		$this->i18n->method( 'get_lang' )->willReturn( 'es' );

		$this->assertSame( 'ES', $this->builder->get_country( 'cart-ref' ) );
	}
}
