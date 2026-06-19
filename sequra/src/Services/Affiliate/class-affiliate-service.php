<?php
/**
 * Affiliate tracking service.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services/Affiliate
 */

namespace SeQura\WC\Services\Affiliate;

use SeQura\Core\BusinessLogic\Domain\Order\OrderStates;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\OrderStatusSettings\Services\Order_Status_Settings_Service;
use SeQura\WC\Services\Log\Interface_Logger_Service;
use WC_Order;

/**
 * Affiliate click attribution and conversion/cancellation postbacks.
 *
 * Outbound postbacks are delegated to an Interface_Affiliate_Postback_Client; the plugin
 * does not call third-party endpoints directly (see QRD-7898).
 */
class Affiliate_Service implements Interface_Affiliate_Service {

	// The attribution cookie is required by the affiliate contract and is not the seQura API proxy.
	// phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.cookies_setcookie
	// phpcs:disable WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE

	public const COOKIE_NAME = '__sequra_afm';

	private const QUERY_PARAM                = 'transaction_id';
	private const COOKIE_TTL                 = 2592000; // 30 days in seconds.
	private const META_TRANSACTION_ID        = '_sq_affiliate_transaction_id';
	private const META_POSTBACK_STATUS       = '_sq_affiliate_postback_status';
	private const META_POSTBACK_ATTEMPTS     = '_sq_affiliate_postback_attempts';
	private const META_CANCELLATION_ATTEMPTS = '_sq_affiliate_cancellation_attempts';
	private const STATUS_PENDING             = 'pending';
	private const STATUS_SENT                = 'sent';
	private const STATUS_FAILED              = 'failed';
	private const STATUS_REJECTED            = 'rejected';
	private const STATUS_CANCELLATION_FAILED = 'cancellation_failed';
	private const KIND_CONVERSION            = 'conversion';
	private const KIND_CANCELLATION          = 'cancellation';
	private const MAX_DISPATCH_ATTEMPTS      = 3;
	private const RETRY_BACKOFF              = 300; // Base seconds between retries, scaled by attempt count.

	/**
	 * Affiliate configuration provider.
	 *
	 * @var Interface_Affiliate_Config_Provider
	 */
	private $config;

	/**
	 * Order status settings service (shop to seQura status mapping).
	 *
	 * @var Order_Status_Settings_Service
	 */
	private $order_status_settings;

	/**
	 * Outbound postback client.
	 *
	 * @var Interface_Affiliate_Postback_Client
	 */
	private $postback_client;

	/**
	 * Logger service.
	 *
	 * @var Interface_Logger_Service
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @param Interface_Affiliate_Config_Provider $config                Affiliate configuration provider.
	 * @param Order_Status_Settings_Service       $order_status_settings Order status mapping service.
	 * @param Interface_Affiliate_Postback_Client $postback_client       Outbound postback client.
	 * @param Interface_Logger_Service            $logger                Logger service.
	 */
	public function __construct( Interface_Affiliate_Config_Provider $config, Order_Status_Settings_Service $order_status_settings, Interface_Affiliate_Postback_Client $postback_client, Interface_Logger_Service $logger ) {
		$this->config                = $config;
		$this->order_status_settings = $order_status_settings;
		$this->postback_client       = $postback_client;
		$this->logger                = $logger;
	}

	/**
	 * Capture the affiliate transaction id from the current request and store it in a cookie.
	 */
	public function capture_click(): void {
		if ( ! $this->is_active() ) {
			return;
		}
		if ( ! isset( $_GET[ self::QUERY_PARAM ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$transaction_id = \sanitize_text_field( \wp_unslash( $_GET[ self::QUERY_PARAM ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! $this->is_valid_transaction_id( $transaction_id ) ) {
			return;
		}
		if ( ! headers_sent() ) {
			\setcookie(
				self::COOKIE_NAME,
				$transaction_id,
				array(
					'expires'  => time() + self::COOKIE_TTL,
					'path'     => defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/',
					'domain'   => defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '',
					'secure'   => \is_ssl(),
					'httponly' => true,
					'samesite' => 'Lax',
				)
			);
		}
		$_COOKIE[ self::COOKIE_NAME ] = $transaction_id;
	}

	/**
	 * Attribute the captured transaction id to an order (idempotent).
	 *
	 * @param WC_Order $order The order.
	 */
	public function attribute_order( WC_Order $order ): void {
		if ( ! $this->is_active() ) {
			return;
		}
		if ( '' !== (string) $order->get_meta( self::META_TRANSACTION_ID ) ) {
			return;
		}
		$transaction_id = $this->get_transaction_id_from_cookie();
		if ( '' === $transaction_id ) {
			return;
		}
		$order->update_meta_data( self::META_TRANSACTION_ID, $transaction_id );
		$order->update_meta_data( self::META_POSTBACK_STATUS, self::STATUS_PENDING );
		$order->save();
		$this->logger->log_info( 'Affiliate order attributed', __FUNCTION__, __CLASS__ );
	}

	/**
	 * Send the conversion postback to TUNE when the order is paid (deduplicated).
	 *
	 * @param WC_Order $order The order.
	 */
	private function maybe_send_conversion( WC_Order $order ): void {
		if ( ! $this->is_active() ) {
			return;
		}
		$transaction_id = (string) $order->get_meta( self::META_TRANSACTION_ID );
		if ( '' === $transaction_id ) {
			return;
		}
		if ( self::STATUS_SENT === (string) $order->get_meta( self::META_POSTBACK_STATUS ) ) {
			return;
		}
		// Cron events are not ordered: if the order was cancelled before this dispatch ran (the
		// cancellation cron may have fired first), bail so we do not report a conversion for a
		// cancelled order. Bail ONLY on cancellation: an order that legitimately advanced past
		// "paid" (e.g. shipped/completed, which no longer maps to STATE_APPROVED) must still convert.
		if ( OrderStates::STATE_CANCELLED === $this->order_status_settings->map_status_from_shop_to_sequra( $order->get_status() ) ) {
			return;
		}
		$settings = $this->config->get_settings();
		// Amount is the order subtotal: cashback is calculated on the product price, excluding
		// tax (VAT) and shipping, not the order total (confirmed with the business side).
		$success = $this->postback_client->send_conversion(
			(string) $settings['offer_id'],
			(string) $settings['security_token'],
			$transaction_id,
			(float) $order->get_subtotal(),
			(int) $order->get_id()
		);
		if ( ! $success ) {
			// A transient failure must not silently drop the conversion (and the shopper's
			// cashback): retry with a backoff up to a cap, then give up and mark it failed.
			$this->retry_or_fail( $order, self::KIND_CONVERSION, 'Affiliate conversion postback', self::META_POSTBACK_ATTEMPTS, self::STATUS_FAILED );
			return;
		}
		$order->update_meta_data( self::META_POSTBACK_STATUS, self::STATUS_SENT );
		$order->delete_meta_data( self::META_POSTBACK_ATTEMPTS );
		$order->save();
		// The attribution cookie is cleared on the order-received page (clear_cookie_on_received,
		// hooked on template_redirect); there is no shopper response to attach a Set-Cookie to here.
		$this->logger->log_info( 'Affiliate conversion postback sent', __FUNCTION__, __CLASS__ );
	}

	/**
	 * Re-schedule a failed postback with a backoff, or settle it on a terminal status once the
	 * attempt cap is reached.
	 *
	 * Conversion and cancellation track their attempts under distinct meta keys and settle on
	 * distinct terminal statuses, so a failed cancellation never masquerades as a failed conversion
	 * nor reopens the STATUS_SENT gate the cancellation path depends on.
	 *
	 * @param WC_Order $order           The order.
	 * @param string   $kind            The postback kind (conversion or cancellation).
	 * @param string   $context         Human-readable label for the log entry.
	 * @param string   $attempts_key    Order-meta key holding this kind's attempt counter.
	 * @param string   $terminal_status Postback status to set once the attempt cap is reached.
	 */
	private function retry_or_fail( WC_Order $order, string $kind, string $context, string $attempts_key, string $terminal_status ): void {
		$attempts = (int) $order->get_meta( $attempts_key ) + 1;
		if ( $attempts < self::MAX_DISPATCH_ATTEMPTS ) {
			$order->update_meta_data( $attempts_key, $attempts );
			$order->save();
			$this->enqueue_dispatch( $order, $kind, self::RETRY_BACKOFF * $attempts );
			$this->logger->log_error( $context . ' failed; retry scheduled', __FUNCTION__, __CLASS__ );
			return;
		}
		$order->update_meta_data( self::META_POSTBACK_STATUS, $terminal_status );
		$order->save();
		$this->logger->log_error( $context . ' failed; giving up after max attempts', __FUNCTION__, __CLASS__ );
	}

	/**
	 * React to an order status change.
	 *
	 * @param WC_Order $order      The order.
	 * @param string   $new_status The new status (without the wc- prefix).
	 */
	public function handle_status_change( WC_Order $order, $new_status ): void {
		if ( ! $this->is_active() ) {
			return;
		}
		$sequra_state = $this->order_status_settings->map_status_from_shop_to_sequra( (string) $new_status );
		if ( OrderStates::STATE_APPROVED === $sequra_state ) {
			$this->enqueue_dispatch( $order, self::KIND_CONVERSION );
		} elseif ( OrderStates::STATE_CANCELLED === $sequra_state ) {
			$this->enqueue_dispatch( $order, self::KIND_CANCELLATION );
		}
	}

	/**
	 * Execute a scheduled affiliate postback (WP-cron callback target).
	 *
	 * @param WC_Order $order The order.
	 * @param string   $kind  The postback kind (conversion or cancellation).
	 */
	public function dispatch( WC_Order $order, string $kind ): void {
		if ( ! $this->is_active() ) {
			return;
		}
		if ( self::KIND_CONVERSION === $kind ) {
			$this->maybe_send_conversion( $order );
		} elseif ( self::KIND_CANCELLATION === $kind ) {
			$this->maybe_send_cancellation( $order );
		}
	}

	/**
	 * Schedule an affiliate postback off the request thread (deduplicated by order + kind).
	 *
	 * @param WC_Order $order The order.
	 * @param string   $kind  The postback kind (conversion or cancellation).
	 * @param int      $delay Seconds to wait before the event becomes eligible (used for retry backoff).
	 */
	private function enqueue_dispatch( WC_Order $order, string $kind, int $delay = 0 ): void {
		$args = array( $order->get_id(), $kind );
		if ( ! \wp_next_scheduled( self::DISPATCH_HOOK, $args ) ) {
			\wp_schedule_single_event( time() + $delay, self::DISPATCH_HOOK, $args );
		}
	}

	/**
	 * Remove the attribution cookie (e.g. on the order-received page).
	 */
	public function clear_cookie(): void {
		if ( '' !== $this->get_transaction_id_from_cookie() ) {
			$this->delete_cookie();
		}
	}

	/**
	 * Send a cancellation/rejection to the Simba conversion-status webhook.
	 *
	 * @param WC_Order $order The order.
	 */
	private function maybe_send_cancellation( WC_Order $order ): void {
		$transaction_id = (string) $order->get_meta( self::META_TRANSACTION_ID );
		if ( '' === $transaction_id ) {
			return;
		}
		if ( self::STATUS_SENT !== (string) $order->get_meta( self::META_POSTBACK_STATUS ) ) {
			return;
		}
		$settings = $this->config->get_settings();
		if ( $this->postback_client->send_cancellation( (string) $settings['offer_id'], (string) $settings['security_token'], $transaction_id ) ) {
			$order->update_meta_data( self::META_POSTBACK_STATUS, self::STATUS_REJECTED );
			$order->delete_meta_data( self::META_CANCELLATION_ATTEMPTS );
			$order->save();
			$this->logger->log_info( 'Affiliate cancellation reported', __FUNCTION__, __CLASS__ );
		} else {
			// A transient failure must not silently drop the reversal (which would leave cashback on
			// a cancelled order): retry with a backoff up to a cap, then settle on a distinct terminal
			// status so it neither looks like a failed conversion nor reopens the STATUS_SENT gate.
			$this->retry_or_fail( $order, self::KIND_CANCELLATION, 'Affiliate cancellation postback', self::META_CANCELLATION_ATTEMPTS, self::STATUS_CANCELLATION_FAILED );
		}
	}

	/**
	 * Read and validate the transaction id from the cookie.
	 */
	private function get_transaction_id_from_cookie(): string {
		if ( ! isset( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			return '';
		}
		$transaction_id = \sanitize_text_field( \wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) );
		return $this->is_valid_transaction_id( $transaction_id ) ? $transaction_id : '';
	}

	/**
	 * Delete the attribution cookie.
	 */
	private function delete_cookie(): void {
		if ( ! headers_sent() ) {
			\setcookie(
				self::COOKIE_NAME,
				'',
				array(
					'expires'  => time() - 3600,
					'path'     => defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/',
					'domain'   => defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '',
					'secure'   => \is_ssl(),
					'httponly' => true,
					'samesite' => 'Lax',
				)
			);
		}
		unset( $_COOKIE[ self::COOKIE_NAME ] );
	}

	/**
	 * Whether the feature is active: enabled, configured and not superseded by the standalone plugin.
	 */
	private function is_active(): bool {
		if ( $this->standalone_plugin_active() ) {
			return false; // Avoid duplicate postbacks while the standalone plugin is present.
		}
		return $this->config->is_enabled();
	}

	/**
	 * Whether the standalone affiliate marketing plugin is active.
	 */
	private function standalone_plugin_active(): bool {
		return class_exists( 'SeQura_Affiliate_Marketing' );
	}

	/**
	 * Validate the transaction id format.
	 *
	 * @param string $transaction_id The transaction id.
	 */
	private function is_valid_transaction_id( $transaction_id ): bool {
		$length = strlen( (string) $transaction_id );
		if ( $length < 3 || $length > 255 ) {
			return false;
		}
		if ( false !== strpos( $transaction_id, '..' ) ) {
			return false;
		}
		return 1 === preg_match( '/^[a-zA-Z0-9_\-.]+$/', $transaction_id );
	}

	// phpcs:enable WordPressVIPMinimum.Functions.RestrictedFunctions.cookies_setcookie
	// phpcs:enable WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE
}
