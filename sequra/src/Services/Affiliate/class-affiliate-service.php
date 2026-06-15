<?php
/**
 * Affiliate tracking service.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services/Affiliate
 */

namespace SeQura\WC\Services\Affiliate;

use SeQura\WC\Services\Log\Interface_Logger_Service;
use WC_Order;

/**
 * Affiliate click attribution and conversion/cancellation postbacks.
 *
 * The outbound contracts (TUNE postback and Simba cancellation webhook) and the cookie
 * must not change: see QRD-7898. The cancellation webhook URL is hardcoded in this PR.
 */
class Affiliate_Service implements Interface_Affiliate_Service {

	public const COOKIE_NAME = '__sequra_afm';

	private const QUERY_PARAM              = 'transaction_id';
	private const COOKIE_TTL               = 2592000; // 30 days in seconds.
	private const TUNE_POSTBACK_URL        = 'https://sequra.go2cloud.org/aff_lsr';
	private const CANCELLATION_WEBHOOK_URL = 'https://simba.sequra.com/affiliate_network/webhooks/conversion_status';
	private const META_TRANSACTION_ID      = '_sq_affiliate_transaction_id';
	private const META_POSTBACK_STATUS     = '_sq_affiliate_postback_status';
	private const STATUS_PENDING           = 'pending';
	private const STATUS_SENT              = 'sent';
	private const STATUS_FAILED            = 'failed';
	private const STATUS_REJECTED          = 'rejected';
	private const MAX_RETRIES              = 3;
	private const HTTP_TIMEOUT             = 30;
	private const USER_AGENT               = 'WooCommerce-SeQura-Affiliate';

	/**
	 * Settings service.
	 *
	 * @var Interface_Affiliate_Settings_Service
	 */
	private $settings;

	/**
	 * Logger service.
	 *
	 * @var Interface_Logger_Service
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @param Interface_Affiliate_Settings_Service $settings Settings service.
	 * @param Interface_Logger_Service             $logger   Logger service.
	 */
	public function __construct( Interface_Affiliate_Settings_Service $settings, Interface_Logger_Service $logger ) {
		$this->settings = $settings;
		$this->logger   = $logger;
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
			return; // Already attributed.
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
	public function maybe_send_conversion( WC_Order $order ): void {
		if ( ! $this->is_active() ) {
			return;
		}
		$transaction_id = (string) $order->get_meta( self::META_TRANSACTION_ID );
		if ( '' === $transaction_id ) {
			return;
		}
		if ( self::STATUS_SENT === (string) $order->get_meta( self::META_POSTBACK_STATUS ) ) {
			return; // Already reported.
		}
		if ( ! $order->is_paid() ) {
			return;
		}
		$settings = $this->settings->get_settings();
		$success  = $this->send_get_with_retries( $this->build_postback_url( $order, $transaction_id, $settings ) );
		$order->update_meta_data( self::META_POSTBACK_STATUS, $success ? self::STATUS_SENT : self::STATUS_FAILED );
		$order->save();
		if ( $success ) {
			$this->delete_cookie();
			$this->logger->log_info( 'Affiliate conversion postback sent', __FUNCTION__, __CLASS__ );
		} else {
			$this->logger->log_error( 'Affiliate conversion postback failed', __FUNCTION__, __CLASS__ );
		}
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
		if ( $this->is_cancellation_status( (string) $new_status ) ) {
			$this->maybe_send_cancellation( $order );
			return;
		}
		$this->maybe_send_conversion( $order );
	}

	/**
	 * Remove the attribution cookie.
	 *
	 * @param int $order_id The order ID.
	 */
	public function clear_cookie_for_order( $order_id ): void {
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
		// Only reject a conversion that was actually reported.
		if ( self::STATUS_SENT !== (string) $order->get_meta( self::META_POSTBACK_STATUS ) ) {
			return;
		}
		$settings = $this->settings->get_settings();
		if ( $this->send_cancellation_webhook( $transaction_id, $settings ) ) {
			$order->update_meta_data( self::META_POSTBACK_STATUS, self::STATUS_REJECTED );
			$order->save();
			$this->logger->log_info( 'Affiliate cancellation reported', __FUNCTION__, __CLASS__ );
		} else {
			$this->logger->log_error( 'Affiliate cancellation webhook failed', __FUNCTION__, __CLASS__ );
		}
	}

	/**
	 * Build the TUNE conversion postback URL.
	 *
	 * @param WC_Order             $order          The order.
	 * @param string               $transaction_id The transaction id.
	 * @param array<string, mixed> $settings       The affiliate settings.
	 */
	private function build_postback_url( WC_Order $order, $transaction_id, array $settings ): string {
		return \add_query_arg(
			array(
				'offer_id'       => rawurlencode( (string) $settings['offer_id'] ),
				'amount'         => rawurlencode( number_format( (float) $order->get_subtotal(), 2, '.', '' ) ),
				'transaction_id' => rawurlencode( $transaction_id ),
				'security_token' => rawurlencode( (string) $settings['security_token'] ),
				'adv_sub'        => rawurlencode( (string) $order->get_id() ),
			),
			self::TUNE_POSTBACK_URL
		);
	}

	/**
	 * Perform a GET request with retries. Returns true on a 2xx response.
	 *
	 * @param string $url The URL.
	 */
	private function send_get_with_retries( $url ): bool {
		for ( $attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++ ) {
			$response = \wp_remote_get(
				$url,
				array(
					'timeout'    => self::HTTP_TIMEOUT,
					'user-agent' => self::USER_AGENT,
					'sslverify'  => true,
				)
			);
			if ( ! \is_wp_error( $response ) ) {
				$code = (int) \wp_remote_retrieve_response_code( $response );
				if ( $code >= 200 && $code < 300 ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * POST the cancellation payload to the Simba webhook. Returns true on a 2xx response.
	 *
	 * @param string               $transaction_id The transaction id.
	 * @param array<string, mixed> $settings       The affiliate settings.
	 */
	private function send_cancellation_webhook( $transaction_id, array $settings ): bool {
		$body = \wp_json_encode(
			array(
				'transaction_id' => $transaction_id,
				'offer_id'       => (string) $settings['offer_id'],
				'status'         => 'cancelled',
				'security_token' => (string) $settings['security_token'],
			)
		);
		for ( $attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++ ) {
			$response = \wp_remote_post(
				self::CANCELLATION_WEBHOOK_URL,
				array(
					'timeout'    => self::HTTP_TIMEOUT,
					'user-agent' => self::USER_AGENT,
					'headers'    => array( 'Content-Type' => 'application/json' ),
					'body'       => $body,
					'sslverify'  => true,
				)
			);
			if ( ! \is_wp_error( $response ) ) {
				$code = (int) \wp_remote_retrieve_response_code( $response );
				if ( $code >= 200 && $code < 300 ) {
					return true;
				}
			}
		}
		return false;
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
		return $this->settings->is_enabled();
	}

	/**
	 * Whether the standalone affiliate marketing plugin is active.
	 */
	private function standalone_plugin_active(): bool {
		return class_exists( 'SeQura_Affiliate_Marketing' );
	}

	/**
	 * Whether a status represents a cancellation/refund.
	 *
	 * @param string $status The status (without the wc- prefix).
	 */
	private function is_cancellation_status( $status ): bool {
		$status = strtolower( (string) $status );
		if ( in_array( $status, array( 'cancelled', 'canceled', 'refunded', 'failed' ), true ) ) {
			return true;
		}
		return false !== strpos( $status, 'cancel' ) || false !== strpos( $status, 'refund' );
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
}
