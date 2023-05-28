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
 * Sequra_Meta_Box_Service_Options
 */
class Sequra_Meta_Box_Service_Options {



	/**
	 * Output the metabox
	 *
	 * @param WP_Post $post the post.
	 */
	public static function output( $post ) {
		$core_settings                    = get_option( 'woocommerce_sequra_settings', SequraHelper::get_empty_core_settings() );
		$is_sequra_service                = get_post_meta( $post->ID, 'is_sequra_service', true );
		$sequra_service_end_date          = get_post_meta( $post->ID, 'sequra_service_end_date', true );
		$sequra_desired_first_charge_date = get_post_meta( $post->ID, 'sequra_desired_first_charge_date', true );
		$sequra_registration_amount       = get_post_meta( $post->ID, 'sequra_registration_amount', true );
		if ( ! $sequra_service_end_date ) {
			$sequra_service_end_date = $core_settings['default_service_end_date'];
		} ?>
		<div class="wc-metaboxes-wrapper">
			<div id="sequra_service">
				<div id="sequra_service_service_options" class="service_end_date-edit wcs-date-input">
					<div>
						<label for="sequra_service_end_date">
							<?php esc_html_e( 'Service end date', 'sequra' ); ?>
						</label>
						<input id="sequra_service_end_date" name="sequra_service_end_date" type="text" value="<?php echo esc_attr( $sequra_service_end_date ); ?>" placeholder="<?php esc_attr_e( 'date or period in ISO8601 format', 'sequra' ); ?>" pattern="<?php echo esc_attr( SequraHelper::ISO8601_PATTERN ); ?>" /><br />
						<small>
							<?php esc_html_e( 'Date i.e: 2021-06-06 or period i.e: P1Y for 1 year', 'sequra' ); ?>
						</small>
					</div>
					<?php if ( $core_settings['allow_payment_delay'] ) { ?>
						<div>
							<label for="sequra_desired_first_charge_date">
								<?php esc_html_e( 'First instalment delay or date', 'sequra' ); ?>
							</label>
							<input id="sequra_desired_first_charge_date" name="sequra_desired_first_charge_date" type="text" value="<?php echo esc_attr( $sequra_desired_first_charge_date ); ?>" placeholder="<?php esc_attr_e( 'date or period in ISO8601 format', 'sequra' ); ?>" pattern="<?php echo esc_attr( SequraHelper::ISO8601_PATTERN ); ?>" /><br />
							<small>
								<?php esc_html_e( 'Date i.e: 2021-01-01 or period i.e: P1M for 1 month', 'sequra' ); ?>
							</small>
						</div>
					<?php } ?>
					<?php if ( $core_settings['allow_registration_items'] ) { ?>
						<div>
							<label for="sequra_registration_amount">
								<?php esc_html_e( 'Registration amount', 'sequra' ); ?>
							</label>
							<input id="sequra_registration_amount" name="sequra_registration_amount" type="number" value="<?php echo esc_attr( $sequra_registration_amount ); ?>" step="0.01" /> &euro;<br />
							<small>
								<?php esc_html_e( 'Part of the price that will be paid as registration fee', 'sequra' ); ?>
							</small>
						</div>
					<?php } ?>
				</div>
				<div id="sequra_service_is_service" class="service-edit wcs">
					<input id="is_sequra_service" name="is_sequra_service" type="checkbox" value="no" <?php echo 'no' === $is_sequra_service ? 'checked' : ''; ?> onclick="toggleSequraService();" />
					<label for="sequra_service_is_service">
						<?php esc_html_e( 'This is not a service', 'sequra' ); ?>
					</label>
				</div>
			</div>
		</div>
		<script>
			function toggleSequraService() {
				if (jQuery('#is_sequra_service').is(':checked')) {
					jQuery('#sequra_service_end_date').enabled = false;
					jQuery('#sequra_desired_first_charge_date').enabled = false;
					jQuery('#sequra_registration_amount').enabled = false;
					jQuery('#sequra_service_service_options').hide();
				} else {
					jQuery('#sequra_service_end_date').enabled = true;
					jQuery('#sequra_desired_first_charge_date').enabled = true;
					jQuery('#sequra_registration_amount').enabled = true;
					jQuery('#sequra_service_service_options').show();
				}
			}
			toggleSequraService();
		</script>
		<?php
	}

	// phpcs:disable WordPress.Security.NonceVerification.NoNonceVerification, WordPress.Security.NonceVerification.Missing, VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
	/**
	 * Save meta box data
	 *
	 * @param int     $post_id the post id.
	 * @param WP_Post $post the post.
	 */
	public static function save( $post_id, $post ) {
		$is_service = isset( $_POST['is_sequra_service'] ) && 'no' === $_POST['is_sequra_service'] ? 'no' : 'yes';
		update_post_meta( $post_id, 'is_sequra_service', $is_service );
		$service_end_date = isset( $_POST['sequra_service_end_date'] ) ?
			sanitize_text_field( wp_unslash( $_POST['sequra_service_end_date'] ) ) :
			'';
		if ( SequraHelper::validate_service_date( $service_end_date ) ) {
			update_post_meta( $post_id, 'sequra_service_end_date', $service_end_date );
		}
		$desired_first_charge_date = isset( $_POST['sequra_desired_first_charge_date'] ) ?
			sanitize_text_field( wp_unslash( $_POST['sequra_desired_first_charge_date'] ) ) :
			'';
		if ( SequraHelper::validate_service_date( $desired_first_charge_date ) ) {
			update_post_meta( $post_id, 'sequra_desired_first_charge_date', $desired_first_charge_date );
		}
		$registration_amount = isset( $_POST['sequra_registration_amount'] ) ?
			sanitize_text_field( wp_unslash( $_POST['sequra_registration_amount'] ) ) :
			'';
		update_post_meta( $post_id, 'sequra_registration_amount', $registration_amount );
	}
	// phpcs:enable

	/**
	 * Show warning
	 *
	 * @return void
	 */
	public static function warn() {         
		?>
		<div class="notice error sequra_meta_box_service_en_date is-dismissible">
			<p>
				<?php esc_html_e( 'Invalid service end date, please enter a valid one', 'sequra' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public static function add_meta_box() {
		add_meta_box( 'service_end_date', 'seQura Service options', 'Sequra_Meta_Box_Service_Options::output', 'product', 'side', 'default' );
	}
}
