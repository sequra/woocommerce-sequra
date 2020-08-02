<?php
/**
 * Plugin Name: SeQura Add on Service end date per variation
 * Plugin URI: http://sequra.es/
 * Description: SeQura Add on to allow defining service end date per variation.
 * Version: 1.1.0
 * Author: SeQura Engineering
 * Author URI: http://Sequra.es/
 * WC tested up to: 4.1.0
 *
 * @package woocommerce-sequra
 */

add_action( 'woocommerce_sequra_plugin_loaded', 'woocommerce_sequra_variations_end_date_init', 110 );

/**
 * Init plugin
 */
function woocommerce_sequra_variations_end_date_init() {
	$core_settings = get_option( 'woocommerce_sequra_settings', null );
	if ( ! isset($core_settings['enable_for_virtual']) || 'yes' !== $core_settings['enable_for_virtual'] ) {
		return;
	}
	add_action( 'woocommerce_product_after_variable_attributes', 'sequra_add_service_end_date_to_variations', 10, 3 );

	function sequra_add_service_end_date_to_variations( $loop, $variation_data, $variation ) {
		woocommerce_wp_text_input(
			array(
				'id'            => "sequra_service_end_date[{$loop}]",
				'desc_tip'      => true,
				'class'         => 'short',
				'label'         => __( 'Service end date', 'wc_sequra' ),
				'placeholder'   => __( 'date or period in ISO8601 format', 'wc_sequra' ),
				'pattern'       => SequraHelper::ISO8601_PATTERN,
				'description'   => __( 'Date i.e: 2018-06-06 or period i.e: P1Y for 1 year', 'wc_sequra' ),
				'value'         => get_post_meta( $variation->ID, 'sequra_service_end_date', true ),
				'wrapper_class' => 'form-row form-row-first hide_if_variation_virtual',
			)
		);
	}

	/**
	 * Save.
	 */
	add_action( 'woocommerce_save_product_variation', 'sequra_save_service_end_date_variations', 10, 2 );

	function sequra_save_service_end_date_variations( $variation_id, $i ) {
		$service_end_date = $_POST['sequra_service_end_date'][ $i ];
		if ( isset( $service_end_date ) ) {
			update_post_meta( $variation_id, 'sequra_service_end_date', esc_attr( $service_end_date ) );
		}
	}

	/**
	 * Load.
	 */
	add_filter( 'woocommerce_available_variation', 'sequra_add_service_end_date_variation_data' );

	function sequra_add_service_end_date_variation_data( $variations ) {
		$variations['sequra_service_end_date'] = '<div class="woocommerce_service_end_date">Custom Field: <span>' . get_post_meta( $variations['variation_id'], 'sequra_service_end_date', true ) . '</span></div>';
		return $variations;
	}

	/**
	 * Customize in builder.
	 */
	add_filter( 'woocommerce_sequra_add_service_end_date', 'woocommerce_sequra_add_service_end_date_variation_data', 10, 3 );

	function woocommerce_sequra_add_service_end_date_variation_data( $end_date, $product, $cart_item ) {
        $variation_id = $cart_item->get_variation_id();

		if ( $variation_id ) {
			$service_end_date = get_post_meta( $variation_id, 'sequra_service_end_date', true );
			if ( SequraHelper::validate_service_date( $service_end_date ) ) {
				$end_date = $service_end_date;
			}
        }
		return $end_date;
	}
}
