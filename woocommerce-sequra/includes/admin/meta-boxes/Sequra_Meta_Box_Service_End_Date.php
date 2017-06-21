<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Sequra_Meta_Box_Service_End_Date
 */
class Sequra_Meta_Box_Service_End_Date {

	/**
	 * Output the metabox
	 */
	public static function output( $post ) {
		global $post;
		$service_end_date = get_post_meta( $post->ID, 'service_end_date', true ); ?>
        <div class="wc-metaboxes-wrapper">
            <div id="service_end_date">
                <div class="service_end_date-edit wcs-date-input">
                    <input name="service_end_date" type="text" value="<?php echo $service_end_date; ?>"
                           placeholder="<?php echo __( 'yyyy-mm-dd or period in days' ); ?>"
                           pattern="(\d*)|((\d{4})-([0-1]\d)-([0-3]\d))+"/><br/>
                    <small><?php echo __( 'Date i.e: 2018-06-06 or period i.e: 365 for 1 year' ); ?></small>
                </div>
            </div>
        </div>
		<?php
	}

	/**
	 * Save meta box data
	 */
	public static function save( $post_id, $post ) {
		$error            = false;
		$service_end_date = SequraHelper::validateServiceEndDate( $_POST['service_end_date'] );
		if ( ! $service_end_date ) {
			update_post_meta( $post_id, 'service_end_date', null );
			add_action( 'admin_notices', 'Sequra_Meta_Box_Service_End_Date::warn' );
		} else {
			update_post_meta( $post_id, 'service_end_date', $service_end_date );
		}
	}

	public static function warn() {
		?>
        <div class="notice error sequra_meta_box_service_en_date is-dismissible">
            <p><?php __( 'Invalid service end date, pleease enter a valid one' ); ?></p>
        </div>
		<?php
	}

	public static function add_meta_box() {
		add_meta_box( "service_end_date", "Service end date", "Sequra_Meta_Box_Service_End_Date::output", "product", "side", "default" );
	}
}
