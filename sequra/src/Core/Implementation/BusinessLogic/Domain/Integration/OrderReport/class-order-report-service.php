<?php
/**
 * Implementation of OrderReportServiceInterface
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Implementation\BusinessLogic\Domain\Integration\OrderReport;

use SeQura\Core\BusinessLogic\Domain\Integration\OrderReport\OrderReportServiceInterface;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Cart;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\MerchantReference;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Platform;
use SeQura\Core\BusinessLogic\Domain\OrderReport\Models\OrderReport;
use SeQura\Core\BusinessLogic\Domain\OrderReport\Models\OrderStatistics;
use SeQura\WC\Core\Extension\Infrastructure\Configuration\Configuration;
use SeQura\WC\Services\Cart\Interface_Cart_Service;
use SeQura\WC\Services\I18n\Interface_I18n;
use SeQura\WC\Services\Order\Interface_Order_Service;
use SeQura\WC\Services\Pricing\Interface_Pricing_Service;
use SeQura\WC\Services\Pricing\Pricing_Service;
use WC_Order;

/**
 * Implementation of the OrderReportServiceInterface.
 */
class Order_Report_Service implements OrderReportServiceInterface {

	/**
	 * Configuration instance.
	 *
	 * @var Configuration
	 */
	private $configuration;

	/**
	 * Pricing service.
	 *
	 * @var Pricing_Service
	 */
	private $pricing_service;

	/**
	 * Cart service.
	 *
	 * @var Interface_Cart_Service
	 */
	private $cart_service;

	/**
	 * Order service.
	 *
	 * @var Interface_Order_Service
	 */
	private $order_service;

	/**
	 * I18n service.
	 *
	 * @var Interface_I18n
	 */
	private $i18n;

	/**
	 * Constructor.
	 */
	public function __construct( 
		Configuration $configuration, 
		Interface_Pricing_Service $pricing_service,
		Interface_Cart_Service $cart_service,
		Interface_Order_Service $order_service,
		Interface_I18n $i18n
	) {
		$this->configuration   = $configuration;
		$this->pricing_service = $pricing_service;
		$this->cart_service    = $cart_service;
		$this->order_service   = $order_service;
		$this->i18n            = $i18n;
	}

	/**
	 * Returns reports for all orders made by SeQura payment methods in the last 24 hours.
	 *
	 * @param string[] $orderIds
	 *
	 * @return OrderReport[]
	 */
	public function getOrderReports( array $orderIds ): array { 
		$order_reports = array();
		foreach ( $orderIds as $order_id ) {
			$order_id = (int) $order_id;
			/**
			 * Order instance.
			 *
			 * @var WC_Order $order
			 */
			$order = wc_get_order( $order_id );
			if ( ! $order instanceof WC_Order ) {
				continue;
			}
			
			$cart_info = $this->order_service->get_cart_info( $order );

			$order_reports[] = new OrderReport(
				'delivered', // State.
				new MerchantReference( $order->get_id() ), // Merchant Reference.
				new Cart(
					$order->get_currency( 'edit' ), // Currency.
					false, // Gift.
					array_merge(
						$this->cart_service->get_items( $order ),
						$this->cart_service->get_handling_items( $order ),
						$this->cart_service->get_discount_items( $order )
					), // Items.
					$cart_info ? $cart_info->ref : null,
					$cart_info ? $cart_info->created_at : null, // Created at.
					$order->get_date_completed()->format( 'Y-m-d H:i:s' ) // Updated At.
				), // Cart.
				$this->order_service->get_delivery_method( $order ), // Delivery Method.
				$this->order_service->get_customer( $order, $this->i18n->get_lang() ), // Customer.
				null, // Sent at.
				null, // Trackings.
				null, // Remaining cart.
				$this->order_service->get_address( $order, true ), // Delivery Address.
				$this->order_service->get_address( $order, false ) // Invoice Address.
			);
		}
		return $order_reports;
	}

	/**
	 * Returns statistics for all shop orders created in the last 7 days.
	 *
	 * @param string[] $orderIds
	 *
	 * @return OrderStatistics[]
	 */
	public function getOrderStatistics( array $orderIds ): array {
		$statics = array();
		foreach ( $orderIds as $order_id ) {
			$order_id = (int) $order_id;
			/**
			 * Order instance.
			 *
			 * @var WC_Order $order
			 */
			$order = wc_get_order( $order_id );
			if ( ! $order instanceof WC_Order ) {
				continue;
			}

			$statics[] = new OrderStatistics(
				$order->get_date_created()->format( 'Y-m-d' ), // Completed At.
				$order->get_currency(), // Currency.
				$this->pricing_service->to_cents( $order->get_total( 'edit' ) ), // Amount in cents.
				new MerchantReference( $order->get_id() ), // Merchant Reference.
				$this->map_payment_method( $order->get_payment_method() ), // Payment method.
				$order->get_billing_country() ?? 'ES', // Country.
				null, // TODO: Device.
				$this->map_status( $order->get_status() ), // seQura Status.
				$order->get_status(), // Raw status.
				null // TODO: seQura offered.
			);
		}
		return $statics;
	}

	/**
	 * Returns the Platform instance.
	 *
	 * @return Platform
	 */
	public function getPlatform(): Platform {
		return $this->configuration->get_platform();
	}

	/**
	 * Map payment method
	 */
	private function map_payment_method( string $payment_method_raw ): string {
		switch ( $payment_method_raw ) {
			case 'ceca':
			case 'servired':
			case 'redsys':
			case 'iupay':
			case 'univia':
			case 'banesto':
			case 'ruralvia':
			case 'cuatrob':
			case 'paytpvcom':
			case 'cc':
				return 'CC';
			case 'paypal':
				return 'PP';
			case 'cheque':
			case 'banktransfer':
			case 'trustly':
				return 'TR';
			case 'cashondelivery':
			case 'cod':
				return 'COD';
			case 'sequra':
				return 'SQ';
			default:
				return 'O/' . $payment_method_raw;
		}
	}

	/**
	 * Map raw status to seQura status.
	 */
	private function map_status( string $raw_status ): string {
		switch ( $raw_status ) {
			case 'completed':
				return 'shipped';
			case 'cancelled':
			case 'refunded':
				return 'cancelled';
			default:
				return 'processing';
		}
	}
}
