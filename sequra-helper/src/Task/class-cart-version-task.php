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
class Cart_Version_Task extends WC_UI_Version_Task {

	/**
	 * Get the post content for the cart page
	 * 
	 * @param string $version The cart version.
	 * 
	 * @throws \Exception If the version is invalid.
	 */
	private function get_post_content( string $version ): string {
		switch ( $version ) {
			case self::CLASSIC:
				return '[woocommerce_cart]';
			case self::BLOCKS:
				return '<!-- wp:woocommerce/cart -->
				<div class="wp-block-woocommerce-cart alignwide is-loading"><!-- wp:woocommerce/filled-cart-block -->
				<div class="wp-block-woocommerce-filled-cart-block"><!-- wp:woocommerce/cart-items-block -->
				<div class="wp-block-woocommerce-cart-items-block"><!-- wp:woocommerce/cart-line-items-block -->
				<div class="wp-block-woocommerce-cart-line-items-block"></div>
				<!-- /wp:woocommerce/cart-line-items-block -->

				<!-- wp:woocommerce/cart-cross-sells-block -->
				<div class="wp-block-woocommerce-cart-cross-sells-block"><!-- wp:heading {"fontSize":"large"} -->
				<h2 class="wp-block-heading has-large-font-size">You may be interested in…</h2>
				<!-- /wp:heading -->

				<!-- wp:woocommerce/cart-cross-sells-products-block -->
				<div class="wp-block-woocommerce-cart-cross-sells-products-block"></div>
				<!-- /wp:woocommerce/cart-cross-sells-products-block --></div>
				<!-- /wp:woocommerce/cart-cross-sells-block --></div>
				<!-- /wp:woocommerce/cart-items-block -->

				<!-- wp:woocommerce/cart-totals-block -->
				<div class="wp-block-woocommerce-cart-totals-block"><!-- wp:woocommerce/cart-order-summary-block -->
				<div class="wp-block-woocommerce-cart-order-summary-block"><!-- wp:woocommerce/cart-order-summary-heading-block -->
				<div class="wp-block-woocommerce-cart-order-summary-heading-block"></div>
				<!-- /wp:woocommerce/cart-order-summary-heading-block -->

				<!-- wp:woocommerce/cart-order-summary-coupon-form-block -->
				<div class="wp-block-woocommerce-cart-order-summary-coupon-form-block"></div>
				<!-- /wp:woocommerce/cart-order-summary-coupon-form-block -->

				<!-- wp:woocommerce/cart-order-summary-subtotal-block -->
				<div class="wp-block-woocommerce-cart-order-summary-subtotal-block"></div>
				<!-- /wp:woocommerce/cart-order-summary-subtotal-block -->

				<!-- wp:woocommerce/cart-order-summary-fee-block -->
				<div class="wp-block-woocommerce-cart-order-summary-fee-block"></div>
				<!-- /wp:woocommerce/cart-order-summary-fee-block -->

				<!-- wp:woocommerce/cart-order-summary-discount-block -->
				<div class="wp-block-woocommerce-cart-order-summary-discount-block"></div>
				<!-- /wp:woocommerce/cart-order-summary-discount-block -->

				<!-- wp:woocommerce/cart-order-summary-shipping-block -->
				<div class="wp-block-woocommerce-cart-order-summary-shipping-block"></div>
				<!-- /wp:woocommerce/cart-order-summary-shipping-block -->

				<!-- wp:woocommerce/cart-order-summary-taxes-block -->
				<div class="wp-block-woocommerce-cart-order-summary-taxes-block"></div>
				<!-- /wp:woocommerce/cart-order-summary-taxes-block --></div>
				<!-- /wp:woocommerce/cart-order-summary-block -->

				<!-- wp:woocommerce/cart-express-payment-block -->
				<div class="wp-block-woocommerce-cart-express-payment-block"></div>
				<!-- /wp:woocommerce/cart-express-payment-block -->

				<!-- wp:woocommerce/proceed-to-checkout-block -->
				<div class="wp-block-woocommerce-proceed-to-checkout-block"></div>
				<!-- /wp:woocommerce/proceed-to-checkout-block -->

				<!-- wp:woocommerce/cart-accepted-payment-methods-block -->
				<div class="wp-block-woocommerce-cart-accepted-payment-methods-block"></div>
				<!-- /wp:woocommerce/cart-accepted-payment-methods-block --></div>
				<!-- /wp:woocommerce/cart-totals-block --></div>
				<!-- /wp:woocommerce/filled-cart-block -->

				<!-- wp:woocommerce/empty-cart-block -->
				<div class="wp-block-woocommerce-empty-cart-block"><!-- wp:heading {"textAlign":"center","className":"with-empty-cart-icon wc-block-cart__empty-cart__title"} -->
				<h2 class="wp-block-heading has-text-align-center with-empty-cart-icon wc-block-cart__empty-cart__title">Your cart is currently empty!</h2>
				<!-- /wp:heading -->

				<!-- wp:separator {"className":"is-style-dots"} -->
				<hr class="wp-block-separator has-alpha-channel-opacity is-style-dots"/>
				<!-- /wp:separator -->

				<!-- wp:heading {"textAlign":"center"} -->
				<h2 class="wp-block-heading has-text-align-center">New in store</h2>
				<!-- /wp:heading -->

				<!-- wp:woocommerce/product-new {"columns":4,"rows":1} /--></div>
				<!-- /wp:woocommerce/empty-cart-block --></div>
				<!-- /wp:woocommerce/cart -->';
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
		$cart_post_id = (int) get_option( 'woocommerce_cart_page_id' );
		if ( ! $cart_post_id ) {
			throw new \Exception( 'Cart page not found', 404 );
		}

		$result = wp_update_post(
			array(
				'ID'           => $cart_post_id,
				'post_content' => $this->get_post_content( $version ), 
			) 
		);
		
		if ( ! $result ) {
			throw new \Exception( 'Failed to update cart page', 500 );
		}
	}
}
