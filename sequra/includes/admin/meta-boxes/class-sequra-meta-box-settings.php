<?php
/**
 * Metabox to set service end date.
 *
 * @package woocommerce-sequra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sequra_Meta_Box_Settings
 */
class Sequra_Meta_Box_Settings {


	/**
	 * Output the metabox
	 *
	 * @param WP_Post $post the post.
	 */
	public static function output( $post ) {
		$is_sequra_banned = get_post_meta( $post->ID, 'is_sequra_banned', true );
		?>
		<div class="wc-metaboxes-wrapper">
			<div id="sequra_settings">
				<div id="sequra_settings_is_banned" class="service-edit wcs">
					<input id="is_sequra_banned" name="is_sequra_banned" type="checkbox" value="yes" <?php echo 'yes' === $is_sequra_banned ? 'checked' : ''; ?> onclick="toggleSequraService();" />
					<label for="sequra_settings_is_banned">
						<?php esc_html_e( 'Do not offer seQura for this product', 'sequra' ); ?>
					</label>
				</div>
			</div>
		</div>
		<?php
	}
	// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
	/**
	 * Save meta box data
	 *
	 * @param int     $post_id the post id.
	 * @param WP_Post $post the post.
	 */
	public static function save( $post_id, $post ) {
		// phpcs:disable WordPress.Security.NonceVerification.NoNonceVerification,WordPress.Security.NonceVerification.Missing
		$is_banned = isset( $_POST['is_sequra_banned'] ) && 'yes' === $_POST['is_sequra_banned'] ? 'yes' : 'no';
		update_post_meta( $post_id, 'is_sequra_banned', $is_banned );
	}
	// phpcs:enable


	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public static function add_meta_box() {
		add_meta_box( 'sequra_settings', 'seQura settings', 'Sequra_Meta_Box_Settings::output', 'product', 'side', 'default' );
	}
}
