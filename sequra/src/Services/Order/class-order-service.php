<?php
/**
 * Order service
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Order;

use SeQura\Core\BusinessLogic\Domain\Connection\Services\ConnectionService;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Cart;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderUpdateData;
use SeQura\Core\BusinessLogic\Domain\Order\RepositoryContracts\SeQuraOrderRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\Order\Service\OrderService;
use SeQura\Core\BusinessLogic\SeQuraAPI\BaseProxy;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\OrderStatusSettings\Services\Order_Status_Settings_Service;
use SeQura\WC\Dto\Cart_Info;
use SeQura\WC\Dto\Payment_Method_Data;
use SeQura\WC\Repositories\Interface_Deletable_Repository;
use SeQura\WC\Repositories\Interface_Table_Migration_Repository;
use SeQura\WC\Services\Cart\Interface_Cart_Service;
use SeQura\WC\Services\Log\Interface_Logger_Service;
use SeQura\WC\Services\Pricing\Interface_Pricing_Service;
use SeQura\WC\Services\Time\Interface_Time_Checker_Service;
use Throwable;
use WC_DateTime;
use WC_Order;

/**
 * Handle use cases related to Order
 */
class Order_Service implements Interface_Order_Service {

	private const META_KEY_METHOD_TITLE    = '_sq_method_title';
	private const META_KEY_PRODUCT         = '_sq_product';
	private const META_KEY_CAMPAIGN        = '_sq_campaign';
	private const META_KEY_CART_REF        = '_sq_cart_ref';
	private const META_KEY_CART_CREATED_AT = '_sq_cart_created_at';
	private const META_KEY_SENT_TO_SEQURA  = '_sq_sent_to_sequra';

	/**
	 * Pricing service
	 *
	 * @var Interface_Pricing_Service
	 */
	private $pricing_service;

	/**
	 * Order status service
	 *
	 * @var Order_Status_Settings_Service
	 */
	private $order_status_service;

	/**
	 * Cart service
	 *
	 * @var Interface_Cart_Service
	 */
	private $cart_service;

	/**
	 * Logger
	 *
	 * @var Interface_Logger_Service
	 */
	private $logger;

	/**
	 * SeQura Order repository
	 *
	 * @var SeQuraOrderRepositoryInterface
	 */
	private $sequra_order_repository;

	/**
	 * Time checker service
	 * 
	 * @var Interface_Time_Checker_Service
	 */
	private $time_checker_service;

	/**
	 * Deletable repository
	 *
	 * @var Interface_Deletable_Repository
	 */
	private $deletable_repository;

	/**
	 * Table migration repository
	 *
	 * @var Interface_Table_Migration_Repository
	 */
	private $table_migration_repository;

	/**
	 * Payment gateway ID
	 *
	 * @var string
	 */
	private $payment_gateway_id;

	/**
	 * Connection service
	 *
	 * @var ConnectionService
	 */
	private $connection_service;

	/**
	 * Order service
	 *
	 * @var OrderService
	 */
	private $order_service;

	/**
	 * Constructor
	 */
	public function __construct(
		SeQuraOrderRepositoryInterface $sequra_order_repository,
		Interface_Pricing_Service $pricing_service,
		Order_Status_Settings_Service $order_status_service,
		Interface_Cart_Service $cart_service,
		Interface_Logger_Service $logger,
		Interface_Time_Checker_Service $time_checker_service,
		Interface_Deletable_Repository $deletable_repository,
		Interface_Table_Migration_Repository $table_migration_repository,
		string $payment_gateway_id,
		ConnectionService $connection_service,
		OrderService $order_service
	) {
		$this->pricing_service            = $pricing_service;
		$this->order_status_service       = $order_status_service;
		$this->cart_service               = $cart_service;
		$this->logger                     = $logger;
		$this->sequra_order_repository    = $sequra_order_repository;
		$this->time_checker_service       = $time_checker_service;
		$this->deletable_repository       = $deletable_repository;
		$this->table_migration_repository = $table_migration_repository;
		$this->payment_gateway_id         = $payment_gateway_id;
		$this->connection_service         = $connection_service;
		$this->order_service              = $order_service;
	}

	/**
	 * Get order meta value by key from a seQura order.
	 * If the order is not a seQura order an empty string is returned.
	 */
	private function get_order_meta( WC_Order $order, $meta_key ): string {
		if ( $order->get_payment_method() !== $this->payment_gateway_id ) {
			return '';
		}
		return strval( $order->get_meta( $meta_key, true ) );
	}

	/**
	 * Get the seQura payment method title for the order.
	 * If the order is not a seQura order an empty string is returned.
	 */
	public function get_payment_method_title( WC_Order $order ): string {
		return $this->get_order_meta( $order, self::META_KEY_METHOD_TITLE );
	}

	/**
	 * Get the seQura product for the order.
	 * If the value is not found an empty string is returned.
	 */
	public function get_product( WC_Order $order ): string {
		return $this->get_order_meta( $order, self::META_KEY_PRODUCT );
	}

	/**
	 * Get the seQura campaign for the order.
	 * If the value is not found an empty string is returned.
	 */
	public function get_campaign( WC_Order $order ): string {
		return $this->get_order_meta( $order, self::META_KEY_CAMPAIGN );
	}

	/**
	 * Get the seQura cart info for the order.
	 * If the value is not found null is returned.
	 */
	public function get_cart_info( WC_Order $order ): ?Cart_Info {
		return Cart_Info::from_array(
			array(
				'ref'        => $this->get_order_meta( $order, self::META_KEY_CART_REF ),
				'created_at' => $this->get_order_meta( $order, self::META_KEY_CART_CREATED_AT ),
			)
		);
	}

	/**
	 * Save required metadata for the order.
	 * Returns true if the metadata was saved, false otherwise.
	 */
	public function set_order_metadata( WC_Order $order, ?Payment_Method_Data $dto, ?Cart_Info $cart_info ): bool {
		if ( ! $dto ) {
			return false;
		}

		if ( ! $cart_info ) {
			return false;
		}

		$order->update_meta_data( self::META_KEY_PRODUCT, $dto->product );
		if ( ! empty( $dto->campaign ) ) {
			$order->update_meta_data( self::META_KEY_CAMPAIGN, $dto->campaign );
		} else {
			$order->delete_meta_data( self::META_KEY_CAMPAIGN );
		}
		$order->update_meta_data( self::META_KEY_METHOD_TITLE, $dto->title );

		$this->set_cart_info( $order, $cart_info );
		return true;
	}

	/**
	 * Set cart info if it is not already set
	 */
	public function create_cart_info( WC_Order $order ): ?Cart_Info {
		$cart_info = $this->get_cart_info( $order );
		if ( $this->cart_service->is_cart_info_valid( $cart_info ) ) {
			// Skip if the cart info is already set.
			return null;
		}

		$date      = $order->get_date_created();
		$cart_info = new Cart_Info( null, $date ? $date->date( 'c' ) : null );
		$this->set_cart_info( $order, $cart_info );
		return $cart_info;
	}

	/**
	 * Set cart info for the order
	 * 
	 * @param Cart_Info $cart_info Cart info
	 */
	public function set_cart_info( WC_Order $order, $cart_info ): void {
		if ( $cart_info instanceof Cart_Info ) {    
			$order->update_meta_data( self::META_KEY_CART_REF, $cart_info->ref );
			$order->update_meta_data( self::META_KEY_CART_CREATED_AT, $cart_info->created_at );
			$order->save();
		}
	}

	/**
	 * Get the meta key used to store the sent to seQura value.
	 */
	public function get_sent_to_sequra_meta_key(): string {
		return self::META_KEY_SENT_TO_SEQURA;
	}

	/**
	 * Set the order as sent to seQura
	 */
	public function set_as_sent_to_sequra( WC_Order $order ): void {
		$order->update_meta_data( self::META_KEY_SENT_TO_SEQURA, 1 );
		$order->save();
	}

	/**
	 * Call the Order Update API to sync the order status with SeQura
	 * 
	 * @throws Throwable
	 */
	public function update_sequra_order_status( WC_Order $order, string $old_store_status, string $new_store_status ): void {
		if ( $order->get_payment_method( 'edit' ) !== $this->payment_gateway_id
			|| ! in_array( $new_store_status, $this->order_status_service->get_shop_status_completed( true ), true ) 
			|| ! $order->needs_processing() ) {
			// Prevent updating orders that:
			// 1. Were not paid with SeQura.
			// 1. Are not completed.
			// 2. Contain only virtual & downloadable products.
			return; 
		}

		$this->set_sequra_order_status_to_shipped( $order );
	}

	/**
	 * Set the order status to shipped in SeQura
	 *
	 * @throws Throwable 
	 */
	private function set_sequra_order_status_to_shipped( WC_Order $order ): void {
		$cart_info     = $this->get_cart_info( $order );
		$currency      = $order->get_currency( 'edit' );
		$cart_ref      = $cart_info ? $cart_info->ref : null;
		$created_at    = $cart_info ? $cart_info->created_at : null;
		$updated_at    = $this->get_order_completion_date( $order );
		$shipped_items = array_merge(
			$this->cart_service->get_items( $order ),
			$this->cart_service->get_handling_items( $order ),
			$this->cart_service->get_discount_items( $order ),
			$this->cart_service->get_refund_items( $order )
		);

		try {
			/**
			 * Filter the order_ref_1.
			 *
			 * @since 2.0.0
			 */
			$ref_1 = \apply_filters( 'woocommerce_sequra_get_order_ref_1', $order->get_id(), $order );
			$this->order_service->updateOrder(
				new OrderUpdateData(
					$ref_1, // Order reference.
					new Cart( $currency, false, $shipped_items, $cart_ref, $created_at, $updated_at ), // Shipped cart.
					new Cart( $currency ), // Unshipped cart.
					null, // Delivery address.
					null // Invoice address.
				) 
			);
		} catch ( Throwable $e ) {
			throw $e;
		}
	}

	/**
	 * Update the order amount in SeQura after a refund
	 *
	 * @throws Throwable 
	 */
	public function handle_refund( WC_Order $order, float $amount ): void {
		$cart_info     = $this->get_cart_info( $order );
		$currency      = $order->get_currency( 'edit' );
		$cart_ref      = $cart_info ? $cart_info->ref : null;
		$created_at    = $cart_info ? $cart_info->created_at : null;
		$updated_at    = $this->get_order_completion_date( $order );
		$shipped_items = array();
		if ( $order->get_total( 'edit' ) > $amount ) {
			$shipped_items = array_merge(
				$this->cart_service->get_items( $order ),
				$this->cart_service->get_handling_items( $order ),
				$this->cart_service->get_discount_items( $order ),
				$this->cart_service->get_refund_items( $order )
			);
		}
		
		try {
			/**
			 * Filter the order_ref_1.
			 *
			 * @since 2.0.0
			 */
			$ref_1 = \apply_filters( 'woocommerce_sequra_get_order_ref_1', $order->get_id(), $order );
			$this->order_service->updateOrder(
				new OrderUpdateData(
					$ref_1, // Order reference.
					new Cart( $currency, false, $shipped_items, $cart_ref, $created_at, $updated_at ), // Shipped cart.
					new Cart( $currency ), // Unshipped cart.
					null, // Delivery address.
					null // Invoice address.
				) 
			);
		} catch ( Throwable $e ) {
			throw $e;
		}
	}

	/**
	 * Get the link to the SeQura back office for the order
	 */
	public function get_link_to_sequra_back_office( WC_Order $order ): ?string {
		if ( $order->get_payment_method() !== $this->payment_gateway_id ) {
			return null;
		}

		$merchant_id = $this->get_merchant_id( $order );
		if ( ! $merchant_id ) {
			return null;
		}

		try {
			$conn_data = $this->connection_service->getConnectionDataByMerchantId( $merchant_id );
			switch ( $conn_data->getEnvironment() ) {
				case BaseProxy::TEST_MODE:
					return 'https://simbox.sequrapi.com/orders/' . $order->get_transaction_id();
				case BaseProxy::LIVE_MODE:
					return 'https://simba.sequra.es/orders/' . $order->get_transaction_id();
				default:
					return null;
			}
		} catch ( Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			return null;
		}
	}

	/**
	 * Get the total amount of the order
	 * 
	 * @param WC_Order $order
	 * @return float|int
	 */
	public function get_total( $order, $in_cents = true ) {
		$total = 0;
		if ( $order instanceof WC_Order ) {
			$total = (float) $order->get_total( 'edit' );
		}
		return $in_cents ? $this->pricing_service->to_cents( $total ) : $total;
	}

	/**
	 * Cleanup orders
	 * 
	 * @return void
	 */
	public function cleanup_orders() {
		$this->deletable_repository->delete_old_and_invalid();
	}

	/**
	 * Get the Merchant ID
	 * 
	 * @param WC_Order $order
	 * @return string|null
	 */
	public function get_merchant_id( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return null;
		}

		$sq_order = $this->sequra_order_repository->getByShopReference( $order->get_id() );
		return $sq_order ? (string) $sq_order->getMerchant()->getId() : null;
	}


	/**
	 * Check if the migration process is complete
	 * 
	 * @return bool True if they are missing indexes, false otherwise
	 */
	public function is_migration_complete() {
		return $this->table_migration_repository->is_migration_complete();
	}

	/**
	 * Execute the migration process
	 */
	public function migrate_data() {
		try {
			$this->table_migration_repository->prepare_tables_for_migration();
		} catch ( Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			return;
		}

		/**
		 * Filters the start hour for the migration process.
		 * 
		 * @since 3.1.2
		 * @return int The hour to start the migration process, default is 2 (2AM).
		 */
		$from = (int) apply_filters( 'sequra_migration_from', 2 );

		/**
		 * Filters the end hour for the migration process.
		 * 
		 * @since 3.1.2
		 * @return int The hour to end the migration process, default is 6 (6AM).
		 */
		$to = (int) apply_filters( 'sequra_migration_to', 6 );

		if ( ! $this->time_checker_service->is_current_hour_in_range( $from, $to ) ) { 
			return;
		}

		/**
		 * Filters the batch size for the migration process.
		 * 
		 * @since 3.1.2
		 * @return int The number of rows to process in each batch, default is 100.
		 */
		$batch_size = (int) apply_filters( 'sequra_migration_batch_size', 100 );
		for ( $i = 0; $i < $batch_size; $i++ ) {
			$this->table_migration_repository->migrate_next_row();
			if ( $this->table_migration_repository->maybe_remove_legacy_table() ) {
				// Migration is complete.
				break;
			}
		}
	}

	/**
	 * Get the order completion date or current date if not completed.
	 * 
	 * @param WC_Order $order
	 * @return string
	 */
	public function get_order_completion_date( $order ) {
		$datetime = $order instanceof WC_Order ? $order->get_date_completed() : null;
		return ( $datetime ?? new WC_DateTime() )->format( 'Y-m-d H:i:s' );
	}
}
