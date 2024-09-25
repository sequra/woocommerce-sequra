<?php
/**
 * Notice template
 *
 * @package    SeQura/WC
 * @var string $args The arguments. Must contains:
 * - 'notice' with the message. 
 * - 'type' key (use "error", "warning", "success", "info" ).
 * - 'dismissible' (use true or false).
 */

defined( 'WPINC' ) || die;
if ( ! isset( $args['notice'], $args['type'], $args['dismissible'] ) ) {
	return;
}
?>
<div class="notice notice-<?php echo esc_html( $args['type'] ) . ( ! empty( $args['dismissible'] ) ? ' is-dismissible' : '' ); ?>"><p><?php echo wp_kses_post( $args['notice'] ); ?></p></div>