<?php
/**
 * Task class
 * 
 * @package SeQura/Helper
 */

namespace SeQura\Helper\Task;

// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.Security.EscapeOutput.ExceptionNotEscaped

/**
 * Task class
 */
class Checkout_Version_Task extends WC_UI_Version_Task {

	/**
	 * Get the post content for the cart page
	 * 
	 * @param string $version The checkout version.
	 * 
	 * @throws \Exception If the version is invalid.
	 */
	private function get_post_content( string $version ): string {
		switch ( $version ) {
			case self::CLASSIC:
				return '[woocommerce_checkout]';
			case self::BLOCKS:
				return '<!-- wp:woocommerce/checkout -->
				<div class="wp-block-woocommerce-checkout alignwide wc-block-checkout is-loading"><!-- wp:woocommerce/checkout-fields-block -->
				<div class="wp-block-woocommerce-checkout-fields-block"><!-- wp:woocommerce/checkout-express-payment-block -->
				<div class="wp-block-woocommerce-checkout-express-payment-block"></div>
				<!-- /wp:woocommerce/checkout-express-payment-block -->

				<!-- wp:woocommerce/checkout-contact-information-block -->
				<div class="wp-block-woocommerce-checkout-contact-information-block"></div>
				<!-- /wp:woocommerce/checkout-contact-information-block -->

				<!-- wp:woocommerce/checkout-shipping-method-block -->
				<div class="wp-block-woocommerce-checkout-shipping-method-block"></div>
				<!-- /wp:woocommerce/checkout-shipping-method-block -->

				<!-- wp:woocommerce/checkout-pickup-options-block -->
				<div class="wp-block-woocommerce-checkout-pickup-options-block"></div>
				<!-- /wp:woocommerce/checkout-pickup-options-block -->

				<!-- wp:woocommerce/checkout-shipping-address-block -->
				<div class="wp-block-woocommerce-checkout-shipping-address-block"></div>
				<!-- /wp:woocommerce/checkout-shipping-address-block -->

				<!-- wp:woocommerce/checkout-billing-address-block -->
				<div class="wp-block-woocommerce-checkout-billing-address-block"></div>
				<!-- /wp:woocommerce/checkout-billing-address-block -->

				<!-- wp:woocommerce/checkout-shipping-methods-block -->
				<div class="wp-block-woocommerce-checkout-shipping-methods-block"></div>
				<!-- /wp:woocommerce/checkout-shipping-methods-block -->

				<!-- wp:woocommerce/checkout-payment-block -->
				<div class="wp-block-woocommerce-checkout-payment-block"></div>
				<!-- /wp:woocommerce/checkout-payment-block -->

				<!-- wp:woocommerce/checkout-additional-information-block -->
				<div class="wp-block-woocommerce-checkout-additional-information-block"></div>
				<!-- /wp:woocommerce/checkout-additional-information-block -->

				<!-- wp:woocommerce/checkout-order-note-block -->
				<div class="wp-block-woocommerce-checkout-order-note-block"></div>
				<!-- /wp:woocommerce/checkout-order-note-block -->

				<!-- wp:woocommerce/checkout-terms-block -->
				<div class="wp-block-woocommerce-checkout-terms-block"></div>
				<!-- /wp:woocommerce/checkout-terms-block -->

				<!-- wp:woocommerce/checkout-actions-block -->
				<div class="wp-block-woocommerce-checkout-actions-block"></div>
				<!-- /wp:woocommerce/checkout-actions-block --></div>
				<!-- /wp:woocommerce/checkout-fields-block -->

				<!-- wp:woocommerce/checkout-totals-block -->
				<div class="wp-block-woocommerce-checkout-totals-block"><!-- wp:woocommerce/checkout-order-summary-block -->
				<div class="wp-block-woocommerce-checkout-order-summary-block"><!-- wp:woocommerce/checkout-order-summary-cart-items-block -->
				<div class="wp-block-woocommerce-checkout-order-summary-cart-items-block"></div>
				<!-- /wp:woocommerce/checkout-order-summary-cart-items-block -->

				<!-- wp:woocommerce/checkout-order-summary-coupon-form-block -->
				<div class="wp-block-woocommerce-checkout-order-summary-coupon-form-block"></div>
				<!-- /wp:woocommerce/checkout-order-summary-coupon-form-block -->

				<!-- wp:woocommerce/checkout-order-summary-subtotal-block -->
				<div class="wp-block-woocommerce-checkout-order-summary-subtotal-block"></div>
				<!-- /wp:woocommerce/checkout-order-summary-subtotal-block -->

				<!-- wp:woocommerce/checkout-order-summary-fee-block -->
				<div class="wp-block-woocommerce-checkout-order-summary-fee-block"></div>
				<!-- /wp:woocommerce/checkout-order-summary-fee-block -->

				<!-- wp:woocommerce/checkout-order-summary-discount-block -->
				<div class="wp-block-woocommerce-checkout-order-summary-discount-block"></div>
				<!-- /wp:woocommerce/checkout-order-summary-discount-block -->

				<!-- wp:woocommerce/checkout-order-summary-shipping-block -->
				<div class="wp-block-woocommerce-checkout-order-summary-shipping-block"></div>
				<!-- /wp:woocommerce/checkout-order-summary-shipping-block -->

				<!-- wp:woocommerce/checkout-order-summary-taxes-block -->
				<div class="wp-block-woocommerce-checkout-order-summary-taxes-block"></div>
				<!-- /wp:woocommerce/checkout-order-summary-taxes-block --></div>
				<!-- /wp:woocommerce/checkout-order-summary-block --></div>
				<!-- /wp:woocommerce/checkout-totals-block --></div>
				<!-- /wp:woocommerce/checkout -->';
			default:
				throw new \Exception( 'Invalid version', 400 );
		}
	}

	/**
	 * Execute the task
	 * 
	 * @param array<string, string> $args Arguments for the task.
	 * 
	 * @throws \Exception If the task fails.
	 */
	public function execute( array $args = array() ): void {
		$version      = $this->get_version( $args );
		$cart_post_id = (int) get_option( 'woocommerce_checkout_page_id' );
		if ( ! $cart_post_id ) {
			throw new \Exception( 'Checkout page not found', 404 );
		}

		$result = wp_update_post(
			array(
				'ID'           => $cart_post_id,
				'post_content' => $this->get_post_content( $version ), 
			) 
		);
		
		if ( ! $result ) {
			throw new \Exception( 'Failed to update checkout page', 500 );
		}
	}
}
