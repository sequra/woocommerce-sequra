<?php
/**
 * Upgrade script.
 *
 * @package woocommerce-sequra
 */

/**
 * Upgrade from versions lowed than 4.4.0 function
 *
 * @param mixed $version the current version.
 * @return void
 */
function sequra_upgrade_from_less_than_440( $version ) {
	if ( version_compare( '4.4.0', $version['from'] ) < 1 ) {
		return;
	}
	add_action( 'plugins_loaded', 'upgrade_service_end_date_format' );
}

add_action( 'sequra_upgrade', 'sequra_upgrade_from_less_than_440' );

/**
 * Undocumented function
 *
 * @return void
 */
function upgrade_service_end_date_format() {
	global $id;
	$wp_query = new WP_Query(
		array(
			'post_type' => 'product',
			'meta_key'  => 'service_end_date',
		)
	);
	while ( $wp_query->have_posts() ) {
		$wp_query->the_post();
		$sequra_service_end_date = get_post_meta( $id, 'service_end_date', true );
		if ( $sequra_service_end_date === (int) $sequra_service_end_date ) {
			$sequra_service_end_date = 'P' . $sequra_service_end_date . 'D';
		}
		update_post_meta( $id, 'sequra_service_end_date', $sequra_service_end_date );
	}
}
