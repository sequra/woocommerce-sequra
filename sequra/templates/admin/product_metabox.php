<?php
/**
 * Product metabox
 *
 * @package    SeQura/WC
 * @var string $args The arguments. Must contain keys:
 * - 'is_banned' The is_banned value (bool).
 * - 'is_banned_field_name' The name of the field to store the is_banned value (string).
 * - 'enabled_for_services' The enabled_for_services value (bool).
 * - 'is_service' The is_service value (bool).
 * - 'is_not_service_field_name' The name of the field to store the is_service value (string).
 * - 'allow_payment_delay' The allow_payment_delay value (bool).
 * - 'service_end_date' The service_end_date value (string).
 * - 'service_end_date_field_name' The name of the field to store the service_end_date value (string).
 */

defined( 'WPINC' ) || die;
if ( ! isset( 
	$args['is_banned'], 
	$args['is_banned_field_name'],
	$args['enabled_for_services'], 
	$args['is_service'], 
	$args['is_service_field_name'], 
	$args['service_end_date'], 
	$args['service_end_date_default'], 
	$args['service_end_date_field_name'], 
	$args['allow_payment_delay'], 
	$args['service_desired_first_charge_date'], 
	$args['service_desired_first_charge_date_field_name'],
	$args['date_or_duration_regex'],
	$args['allow_registration_items'],
	$args['service_registration_amount'], 
	$args['service_registration_amount_field_name'],
	$args['nonce_name']
) ) {
	return;
}

wp_nonce_field( -1, $args['nonce_name'] );
?>

<div class="wc-metaboxes-wrapper">
	<div id="sequra_settings_is_banned" style="padding: 6px 0 8px;">
		<input id="is_sequra_banned" name="<?php echo esc_attr( $args['is_banned_field_name'] ); ?>" type="checkbox" value="yes" <?php echo ( (bool) $args['is_banned'] ) ? 'checked' : ''; ?>/>
		<label for="is_sequra_banned">
			<?php esc_html_e( 'Do not offer seQura for this product', 'sequra' ); ?>
		</label>
	</div>
	<?php
	if ( ( (bool) $args['enabled_for_services'] ) ) :
		?>
	<div id="sequra_service_is_service" style="padding: 6px 0 8px;">
		<input id="is_sequra_service" name="<?php echo esc_attr( $args['is_service_field_name'] ); ?>" type="checkbox" value="no" <?php echo ! ( (bool) $args['is_service'] ) ? 'checked' : ''; ?>/>
		<label for="is_sequra_service">
			<?php esc_html_e( 'This is not a service', 'sequra' ); ?>
		</label>
	</div>
	<div id="sequra_service_service_options">
		<!-- Service end date -->
		<div style="padding: 6px 0 8px;display:grid;gap:4px">
			<label for="sequra_service_end_date"><?php esc_html_e( 'Service end date', 'sequra' ); ?></label>
			<input id="sequra_service_end_date" name="<?php echo esc_attr( $args['service_end_date_field_name'] ); ?>" type="text" value="<?php echo esc_attr( $args['service_end_date'] ); ?>" placeholder="<?php echo esc_attr( $args['service_end_date_default'] ); ?>" pattern="<?php echo esc_attr( $args['date_or_duration_regex'] ); ?>" />
			<small><?php esc_html_e( 'Date i.e: 2021-06-06 or period i.e: P1Y for 1 year', 'sequra' ); ?></small>
		</div>
		<?php if ( (bool) $args['allow_payment_delay'] ) : ?>
			<!-- Service desired first charge date -->
			<div style="padding: 6px 0 8px;display:grid;gap:4px">
				<label for="sequra_desired_first_charge_date"><?php esc_html_e( 'First instalment delay or date', 'sequra' ); ?></label>
				<input id="sequra_desired_first_charge_date" name="<?php echo esc_attr( $args['service_desired_first_charge_date_field_name'] ); ?>" type="text" value="<?php echo esc_attr( $args['service_desired_first_charge_date'] ); ?>" placeholder="<?php esc_attr_e( 'date or period in ISO8601 format', 'sequra' ); ?>" pattern="<?php echo esc_attr( $args['date_or_duration_regex'] ); ?>" />
				<small><?php esc_html_e( 'Date i.e: 2021-01-01 or period i.e: P1M for 1 month', 'sequra' ); ?></small>
			</div>
		<?php endif; ?>
		<?php if ( (bool) $args['allow_registration_items'] ) : ?>
			<!-- Service registration amount -->
			<div style="padding: 6px 0 8px;display:grid;gap:4px">
				<label for="sequra_registration_amount"><?php esc_html_e( 'Registration amount', 'sequra' ); ?> (&euro;)</label>
				<input id="sequra_registration_amount" name="<?php echo esc_attr( $args['service_registration_amount_field_name'] ); ?>" type="number" value="<?php echo esc_attr( $args['service_registration_amount'] ); ?>" step="0.01" />
				<small><?php esc_html_e( 'Part of the price that will be paid as registration fee', 'sequra' ); ?></small>
			</div>
		<?php endif; ?>
	</div>
	<script>
		(function(){
			const toggleServiceFields = function() {
				const checkbox = document.querySelector('#is_sequra_service');
				document.querySelector('#sequra_service_service_options').style.display = checkbox.checked ? 'none' : 'block';
				document.querySelector('#sequra_service_end_date').disabled = checkbox.checked;
				document.querySelector('#sequra_desired_first_charge_date').disabled = checkbox.checked;
				document.querySelector('#sequra_registration_amount').disabled = checkbox.checked;
			};
			document.querySelector('#is_sequra_service').addEventListener('change', toggleServiceFields);
			toggleServiceFields();
		})();
	</script>
	<?php endif; ?>
</div>
