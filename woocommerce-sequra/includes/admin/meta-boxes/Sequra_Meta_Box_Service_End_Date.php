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
		$is_sequra_service = get_post_meta( $post->ID, 'is_sequra_service', true );
        $sequra_service_end_date = get_post_meta( $post->ID, 'sequra_service_end_date', true );
        if(!$sequra_service_end_date){
	        $coresettings = get_option( 'woocommerce_sequra_settings', array() );
	        $sequra_service_end_date = $coresettings['default_service_end_date'];
        } ?>
        <div class="wc-metaboxes-wrapper">
            <div id="sequra_service">
                <div id="sequra_service_service_end_date" class="service_end_date-edit wcs-date-input">
                    <input id="sequra_service_end_date" name="sequra_service_end_date" type="text" value="<?php echo $sequra_service_end_date; ?>"
                           placeholder="<?php echo __( 'date or period in ISO8601 forma', 'wc_sequra' ); ?>"
                           pattern="<?php echo SequraHelper::ISO8601_PATTERN; ?>"/><br/>
                    <small><?php echo __( 'Date i.e: 2018-06-06 or period i.e: P1Y for 1 year', 'wc_sequra' ); ?></small>
                </div>
                <div id="sequra_service_is_service" class="service-edit wcs">
                    <input id="is_sequra_service" name="is_sequra_service" type="checkbox" value="no" <?php echo $is_sequra_service=='no'?'checked':''; ?>
                           onclick="toggleSequraService();"/> <label for="sequra_service_is_service"><?php echo __( 'This is not a service','wc_sequra' ); ?></label>
                </div>
            </div>
        </div>
        <script>
            function toggleSequraService(){
                if(jQuery('#is_sequra_service').is(':checked')){
                    jQuery('#sequra_service_end_date').enabled=false;
                    jQuery('#sequra_service_service_end_date').hide();
                }else{
                    jQuery('#sequra_service_end_date').enabled=true;
                    jQuery('#sequra_service_service_end_date').show();
                }
            }
            toggleSequraService();
        </script>
		<?php
	}

	/**
	 * Save meta box data
	 */
	public static function save( $post_id, $post ) {
		$is_service = isset($_POST['is_sequra_service']) && $_POST['is_sequra_service']=='no'?'no':'yes';
        update_post_meta( $post_id, 'is_sequra_service',$is_service);
		if ( SequraHelper::validateServiceEndDate( $_POST['sequra_service_end_date'] ) ) {
			update_post_meta( $post_id, 'sequra_service_end_date', $_POST['sequra_service_end_date'] );
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
